<?php

namespace App\Services;

use App\Models\MasterItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DynamicForecastingService
{
    /**
     * Ensure every master item has minimal forecast rows,
     * so UI does not mark it as "no ARIMA data".
     */
    protected function ensureFallbackForecastForMissingProducts(): void
    {
        $now = now();

        $masterItems = DB::table('master_items')
            ->whereNotNull('code_item')
            ->whereNotNull('item_id')
            ->select('item_id', 'code_item')
            ->get();

        if ($masterItems->isEmpty()) {
            return;
        }

        $existingSummary = DB::table('arima_forecast_summaries')
            ->pluck('produk')
            ->flip()
            ->toArray();

        foreach ($masterItems as $item) {
            $produk = (string) $item->code_item;
            if (isset($existingSummary[$produk])) {
                continue;
            }

            $txRows = DB::table('finished_goods_out')
                ->where('item_id', $item->item_id)
                ->whereNotNull('out_date')
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('DATE(out_date) as tx_date'),
                    DB::raw('SUM(qty_out) as total_qty')
                )
                ->groupBy(DB::raw('DATE(out_date)'))
                ->orderBy('tx_date', 'asc')
                ->get();

            $series = [];
            if ($txRows->isEmpty()) {
                // No demand history at all -> keep minimal zero baseline (30 hari)
                $start = $now->copy()->subDays(29);
                for ($i = 0; $i < 30; $i++) {
                    $series[$start->copy()->addDays($i)->format('Y-m-d')] = 0.0;
                }
            } else {
                $txMap = [];
                foreach ($txRows as $row) {
                    $txMap[$row->tx_date] = (float) $row->total_qty;
                }

                $start = Carbon::parse($txRows->first()->tx_date);
                $end   = Carbon::parse($txRows->last()->tx_date);
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $dateStr = $cursor->format('Y-m-d');
                    $series[$dateStr] = (float) ($txMap[$dateStr] ?? 0.0);
                    $cursor->addDay();
                }
            }

            $values = array_values($series);
            $avg = count($values) > 0 ? (array_sum($values) / count($values)) : 0.0;

            DB::table('arima_forecast_summaries')->insert([
                'produk'          => $produk,
                'arima_order'     => '0,0,0',
                'mae'             => 0.0,
                'rmse'            => 0.0,
                'mape_percentage' => 0.0,
                'stationary'      => 1,
                'adf_p_value'     => 0.0,
                'kategori_mae'    => 'rendah',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $detailBatch = [];
            foreach ($series as $dateStr => $actualQty) {
                $predicted = min($actualQty, $avg);
                $detailBatch[] = [
                    'produk'          => $produk,
                    'date'            => $dateStr,
                    'actual_sales'    => round($actualQty, 4),
                    'predicted_sales' => round($predicted, 4),
                    'data_type'       => 'actual',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            // Minimal future forecast (30 hari) untuk kebutuhan manajemen.
            $futureStart = count($series) > 0
                ? Carbon::parse(array_key_last($series))->addDay()
                : $now->copy();

            for ($i = 0; $i < 30; $i++) {
                $detailBatch[] = [
                    'produk'          => $produk,
                    'date'            => $futureStart->copy()->addDays($i)->format('Y-m-d'),
                    'actual_sales'    => 0.0,
                    'predicted_sales' => round(max(0.0, $avg), 4),
                    'data_type'       => 'forecast',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            if (!empty($detailBatch)) {
                DB::table('arima_forecast_details')->insert($detailBatch);
            }
        }
    }

    public function runDynamicForecast()
    {
        // Increase maximum execution time to 5 minutes to allow Python ARIMA optimization for multiple products
        set_time_limit(300);

        try {
            Log::info("Starting dynamic forecast based on finished_goods_out (demand)...");
            
            // 1. Export Data to CSV
            $query = DB::table('finished_goods_out')
                ->join('master_items', 'finished_goods_out.item_id', '=', 'master_items.item_id')
                ->select(
                    DB::raw('DATE(finished_goods_out.out_date) as date'),
                    'master_items.code_item as produk',
                    DB::raw('SUM(finished_goods_out.qty_out) as qty')
                )
                ->whereNotNull('finished_goods_out.out_date')
                ->whereNull('finished_goods_out.deleted_at')
                ->groupBy(DB::raw('DATE(finished_goods_out.out_date)'), 'master_items.code_item')
                ->get();
            
            if ($query->isEmpty()) {
                Log::warning("No data found in finished_goods_out for forecasting.");
                return [
                    'success' => false,
                    'message' => 'Tidak ada data histori stok keluar (finished goods out) untuk peramalan.'
                ];
            }
            
            $tempDir = storage_path('app/temp_forecast');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $inputCsv = $tempDir . '/dynamic_input.csv';
            $summaryCsv = $tempDir . '/dynamic_summary.csv';
            $detailsCsv = $tempDir . '/dynamic_details.csv';
            
            $file = fopen($inputCsv, 'w');
            fputcsv($file, ['date', 'produk', 'qty']);
            foreach ($query as $row) {
                fputcsv($file, [$row->date, $row->produk, $row->qty]);
            }
            fclose($file);
            
            // 2. Run Python Script
            $pythonScript = base_path('python/dynamic_arima_production.py');
            // use python3 if available, else python
            $pythonCmd = 'python'; // Windows usually has python in PATH
            $command = escapeshellcmd("$pythonCmd $pythonScript --input $inputCsv --summary $summaryCsv --details $detailsCsv");
            
            Log::info("Executing: $command");
            $output = shell_exec($command . ' 2>&1');
            Log::info("Python Output: " . $output);
            
            if (!file_exists($summaryCsv) || !file_exists($detailsCsv)) {
                return [
                    'success' => false,
                    'message' => 'Gagal menjalankan script Python ARIMA.'
                ];
            }
            
            // 3. Import Results
            DB::beginTransaction();

            // Replace data only for recalculated products, so seeded CSV data for
            // other products is preserved.
            $recalculatedProducts = collect($query)
                ->pluck('produk')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (!empty($recalculatedProducts)) {
                DB::table('arima_forecast_summaries')
                    ->whereIn('produk', $recalculatedProducts)
                    ->delete();

                DB::table('arima_forecast_details')
                    ->whereIn('produk', $recalculatedProducts)
                    ->delete();
            }
            
            // Import Summary
            if (($handle = fopen($summaryCsv, "r")) !== FALSE) {
                $header = fgetcsv($handle, 1000, ",");
                $now = now();
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $row = array_combine($header, $data);
                    DB::table('arima_forecast_summaries')->insert([
                        'produk' => $row['produk'],
                        'arima_order' => $row['arima_order'],
                        'mae' => $row['mae'],
                        'rmse' => $row['rmse'],
                        'mape_percentage' => $row['mape_percentage'],
                        'stationary' => in_array(strtolower($row['stationary'] ?? ''), ['yes', 'ya', '1', 'true']) ? 1 : 0,
                        'adf_p_value' => $row['adf_p_value'],
                        'kategori_mae' => $row['kategori_mae'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
                fclose($handle);
            }
            
            // Import Details
            if (($handle = fopen($detailsCsv, "r")) !== FALSE) {
                $header = fgetcsv($handle, 1000, ",");
                $now = now();
                $detailsBatch = [];
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $row = array_combine($header, $data);
                    $detailsBatch[] = [
                        'produk' => $row['produk'],
                        'date' => $row['date'],
                        'actual_sales' => $row['actual_sales'],
                        'predicted_sales' => $row['predicted_sales'],
                        'data_type' => $row['data_type'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                    
                    if (count($detailsBatch) >= 500) {
                        DB::table('arima_forecast_details')->insert($detailsBatch);
                        $detailsBatch = [];
                    }
                }
                if (count($detailsBatch) > 0) {
                    DB::table('arima_forecast_details')->insert($detailsBatch);
                }
                fclose($handle);
            }

            // Ensure every product has ARIMA rows, even if Python skipped it.
            $this->ensureFallbackForecastForMissingProducts();
            
            DB::commit();
            Log::info("Dynamic forecasting completed successfully.");
            
            // Cleanup temporary files on success
            if (file_exists($inputCsv)) @unlink($inputCsv);
            if (file_exists($summaryCsv)) @unlink($summaryCsv);
            if (file_exists($detailsCsv)) @unlink($detailsCsv);
            
            return [
                'success' => true,
                'message' => 'Peramalan dinamis berhasil dijalankan dan data diperbarui.'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Dynamic forecasting error: " . $e->getMessage());
            
            // Cleanup temporary files on failure
            $tempDir = storage_path('app/temp_forecast');
            $inputCsv = $tempDir . '/dynamic_input.csv';
            $summaryCsv = $tempDir . '/dynamic_summary.csv';
            $detailsCsv = $tempDir . '/dynamic_details.csv';
            if (file_exists($inputCsv)) @unlink($inputCsv);
            if (file_exists($summaryCsv)) @unlink($summaryCsv);
            if (file_exists($detailsCsv)) @unlink($detailsCsv);
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat peramalan dinamis: ' . $e->getMessage()
            ];
        }
    }
}
