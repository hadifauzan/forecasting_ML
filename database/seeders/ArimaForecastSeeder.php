<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ArimaForecastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $summaryPath = base_path('python/arima_forecast_summary_per_produk.csv');
        $categoryPath = base_path('python/arima_forecast_mae_kategori_ringkas.csv');
        $detailPath = base_path('python/arima_forecast_detailed_per_produk.csv');

        if (!File::exists($summaryPath) || !File::exists($categoryPath) || !File::exists($detailPath)) {
            $this->command?->error("File CSV ARIMA tidak ditemukan di folder python/!");
            return;
        }

        $now = now();

        // 1. Import Category MAE Summaries
        $categoryRows = $this->readCsv($categoryPath);
        $categoryPayload = [];
        foreach ($categoryRows as $row) {
            $kategoriMae = trim((string)($row['Kategori MAE'] ?? $row['kategori_mae'] ?? ''));
            if ($kategoriMae === '') continue;

            $categoryPayload[] = [
                'kategori_mae' => $kategoriMae,
                'jumlah_produk' => (int)($row['jumlah_produk'] ?? 0),
                'mae_rata_rata' => (float)($row['mae_rata_rata'] ?? 0),
                'rmse_rata_rata' => (float)($row['rmse_rata_rata'] ?? 0),
                'mape_rata_rata' => (float)($row['mape_rata_rata'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // 2. Import ARIMA Summaries
        $summaryRows = $this->readCsv($summaryPath);
        $summaryPayload = [];
        foreach ($summaryRows as $row) {
            $produk = trim((string)($row['Produk'] ?? $row['produk'] ?? ''));
            if ($produk === '') continue;

            $stationaryRaw = $row['Stationary'] ?? $row['stationary'] ?? null;
            $stationary = true;
            if ($stationaryRaw !== null && $stationaryRaw !== '') {
                $normalized = mb_strtolower(trim((string)$stationaryRaw));
                if (in_array($normalized, ['tidak', 'no', 'false', '0'], true)) {
                    $stationary = false;
                }
            }

            $summaryPayload[] = [
                'produk' => $produk,
                'arima_order' => trim((string)($row['ARIMA Order'] ?? $row['arima_order'] ?? '(1, 1, 1)')),
                'mae' => (float)($row['MAE'] ?? $row['mae'] ?? 0),
                'rmse' => (float)($row['RMSE'] ?? $row['rmse'] ?? 0),
                'mape_percentage' => (float)($row['MAPE (%)'] ?? $row['mape_percentage'] ?? 0),
                'stationary' => $stationary,
                'adf_p_value' => $this->toNullableFloat($row['ADF p-value'] ?? $row['adf_p_value'] ?? 0.01),
                'kategori_mae' => trim((string)($row['Kategori MAE'] ?? $row['kategori_mae'] ?? 'rendah')),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // 3. Import Detailed Forecasts and Seed matching finished_goods_in
        $detailRows = $this->readCsv($detailPath);
        $detailPayload = [];
        $fgInPayload = [];

        $itemsMap = DB::table('master_items')->pluck('item_id', 'code_item')->toArray();
        $itemsCostMap = DB::table('master_items')->pluck('costprice_item', 'code_item')->toArray();

        // Bersihkan finished_goods_in lama untuk item yang ada di CSV agar tidak menumpuk/bloat
        $uniqueSkus = [];
        foreach ($detailRows as $row) {
            $sku = trim((string)($row['Produk'] ?? $row['produk'] ?? ''));
            if ($sku !== '') {
                $uniqueSkus[$sku] = true;
            }
        }
        foreach (array_keys($uniqueSkus) as $sku) {
            if (isset($itemsMap[$sku])) {
                DB::table('finished_goods_in')->where('item_id', $itemsMap[$sku])->delete();
            }
        }

        foreach ($detailRows as $row) {
            $sku = trim((string)($row['Produk'] ?? $row['produk'] ?? ''));
            if ($sku === '') continue;

            $actualVal = (float)($row['Actual_Sales'] ?? $row['actual_sales'] ?? 0);
            $predictedVal = (float)($row['Predicted_Sales'] ?? $row['predicted_sales'] ?? 0);

            $error = round($actualVal - $predictedVal, 4);
            $absError = abs($error);
            $dateStr = trim((string)($row['Date'] ?? $row['date'] ?? ''));
            $kategoriMae = trim((string)($row['Kategori_MAE'] ?? $row['kategori_mae'] ?? 'rendah'));

            $detailPayload[] = [
                'date' => $dateStr,
                'produk' => $sku,
                'kategori_mae' => $kategoriMae,
                'actual_sales' => $actualVal,
                'predicted_sales' => $predictedVal,
                'error' => $error,
                'absolute_error' => $absError,
                'data_type' => 'actual',
                'created_at' => $now,
                'updated_at' => $now
            ];

            // Seed matching finished_goods_in record
            if (isset($itemsMap[$sku])) {
                $itemId = $itemsMap[$sku];
                $cost = $itemsCostMap[$sku] ?? 12000.0;

                $fgInPayload[] = [
                    'item_id' => $itemId,
                    'inventory_id' => 1,
                    'branch_id' => 1,
                    'received_by' => 1,
                    'qty_received' => $actualVal,
                    'unit' => 'pcs',
                    'unit_cost' => $cost,
                    'total_cost' => $actualVal * $cost,
                    'stock_before' => 0.0,
                    'stock_after' => $actualVal,
                    'received_date' => $dateStr,
                    'production_date' => $dateStr,
                    'document_number' => 'FGI-SEEDED-' . $sku . '-' . str_replace('-', '', $dateStr),
                    'batch_number' => 'B-' . substr(str_replace('-', '', $dateStr), 2),
                    'qc_status' => 'passed',
                    'notes' => 'Seeded matching production from CSV details',
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }

        // Bulk insert finished_goods_in records
        if (!empty($fgInPayload)) {
            foreach (array_chunk($fgInPayload, 100) as $chunk) {
                DB::table('finished_goods_in')->insert($chunk);
            }
        }

        // Database transaction safety for summaries and details
        DB::transaction(function () use ($summaryPayload, $categoryPayload, $detailPayload) {
            if (!empty($summaryPayload)) {
                DB::table('arima_forecast_summaries')->upsert(
                    $summaryPayload,
                    ['produk'],
                    ['arima_order', 'mae', 'rmse', 'mape_percentage', 'stationary', 'adf_p_value', 'kategori_mae', 'updated_at']
                );
            }

            if (!empty($categoryPayload)) {
                DB::table('arima_forecast_mae_category_summaries')->upsert(
                    $categoryPayload,
                    ['kategori_mae'],
                    ['jumlah_produk', 'mae_rata_rata', 'rmse_rata_rata', 'mape_rata_rata', 'updated_at']
                );
            }

            if (!empty($detailPayload)) {
                DB::table('arima_forecast_details')->delete();
                foreach (array_chunk($detailPayload, 200) as $chunk) {
                    DB::table('arima_forecast_details')->insert($chunk);
                }
            }
        });

        $this->command?->info("ArimaForecastSeeder berhasil dijalankan dari berkas CSV bawaan.");
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) === false) {
            return $rows;
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $rows;
        }

        // Clean UTF-8 BOM
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }
            $row = array_combine($headers, $data);
            if ($row !== false) {
                $rows[] = $row;
            }
        }
        fclose($handle);
        return $rows;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null) return null;
        $stringValue = trim((string)$value);
        if ($stringValue === '') return null;
        return (float)$stringValue;
    }
}
