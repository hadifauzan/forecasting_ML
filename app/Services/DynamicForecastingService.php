<?php

namespace App\Services;

use App\Models\FinishedGoodsIn;
use App\Models\MasterItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DynamicForecastingService
{
    public function runDynamicForecast()
    {
        // Increase maximum execution time to 5 minutes to allow Python ARIMA optimization for multiple products
        set_time_limit(300);

        try {
            Log::info("Starting dynamic forecast based on FinishedGoodsIn...");
            
            // 1. Export Data to CSV
            $query = DB::table('finished_goods_in')
                ->join('master_items', 'finished_goods_in.item_id', '=', 'master_items.item_id')
                ->select(
                    DB::raw('DATE(finished_goods_in.received_date) as date'),
                    'master_items.code_item as produk',
                    DB::raw('SUM(finished_goods_in.qty_received) as qty')
                )
                ->whereNotNull('finished_goods_in.received_date')
                ->groupBy(DB::raw('DATE(finished_goods_in.received_date)'), 'master_items.code_item')
                ->get();
            
            if ($query->isEmpty()) {
                Log::warning("No data found in FinishedGoodsIn for forecasting.");
                return [
                    'success' => false,
                    'message' => 'Tidak ada data histori produksi untuk peramalan.'
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
            
            // Clear old data (using delete() instead of truncate() for database transaction safety and rollback capability)
            DB::table('arima_forecast_summaries')->delete();
            DB::table('arima_forecast_details')->delete();
            
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
