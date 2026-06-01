<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinishedGoodsOutAndArimaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Dapatkan info master_items untuk GB-BB-100
        $sku = 'GB-BB-100';
        $item = DB::table('master_items')->where('code_item', $sku)->first();

        if (!$item) {
            $this->command?->error("Item dengan code {$sku} tidak ditemukan! Pastikan MasterItemSeeder sudah dijalankan.");
            return;
        }

        $itemId = $item->item_id;

        // 2. Dapatkan branch dan inventory valid
        $branch = DB::table('master_branches')->first();
        $branchId = $branch ? $branch->branch_id : 1;

        $inventory = DB::table('master_inventories')->first();
        $inventoryId = $inventory ? $inventory->inventory_id : 1;

        // 3. Dapatkan user valid (misal: superadmin atau inventory admin)
        $user = DB::table('master_users')->where('email', 'inventory@gentleliving.com')->first();
        if (!$user) {
            $user = DB::table('master_users')->first();
        }
        $userId = $user ? $user->user_id : 1;

        $this->command?->info("Seeding data untuk {$sku} (Item ID: {$itemId}) menggunakan Gudang ID: {$inventoryId}, Branch ID: {$branchId}, User ID: {$userId}");

        // 4. Pastikan data stok awal di master_items_stock tersedia
        DB::table('master_items_stock')->updateOrInsert(
            [
                'item_id' => $itemId,
                'inventory_id' => $inventoryId,
            ],
            [
                'stock' => 150,
                'buffer_stock' => 30,
                'avg_daily_sales' => 10.0,
                'stock_status' => 'normal',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // 5. Bersihkan data ARIMA detail & summary yang lama untuk produk ini agar tidak duplikat
        DB::table('arima_forecast_summaries')->where('produk', $sku)->delete();
        DB::table('arima_forecast_details')->where('produk', $sku)->delete();
        // Bersihkan juga data stok keluar dan masuk lama untuk produk ini agar tidak menumpuk
        DB::table('finished_goods_out')->where('item_id', $itemId)->delete();
        DB::table('finished_goods_in')->where('item_id', $itemId)->delete();

        // 6. Generate ARIMA Summary untuk GB-BB-100
        DB::table('arima_forecast_summaries')->insert([
            'produk' => $sku,
            'arima_order' => '(2, 1, 1)',
            'mae' => 0.98,
            'rmse' => 1.25,
            'mape_percentage' => 8.5,
            'stationary' => true,
            'adf_p_value' => 0.0125,
            'kategori_mae' => 'rendah',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Definisikan tanggal dan parameter generator wave
        $today = Carbon::now()->startOfDay();
        $testStart = $today->copy()->subDays(36); // Periode uji 37 hari berakhir hari ini
        $trainStart = $testStart->copy()->subDays(92); // Periode latih 92 hari sebelum periode uji
        $forecastStart = $today->copy()->addDays(1); // Prediksi masa depan mulai besok

        $baseSales = 10.0;
        $detailPayload = [];
        $fgOutPayload = [];
        $fgInPayload = [];
        
        $currentStock = 500.0; // Simulasi tracking stok berjalan

        // ── 7.1. TRAINING PERIOD (92 hari) ──
        for ($d = 0; $d < 92; $d++) {
            $currentDate = $trainStart->copy()->addDays($d);
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek;

            $weeklyEffect = ($dayOfWeek == 5 || $dayOfWeek == 6 || $dayOfWeek == 0) ? 6.0 : -3.0;
            $cycleEffect = 5.0 * sin(($d / 92.0) * 2.0 * M_PI * 4.0); // seasonal wave
            $noise = (float) rand(-2, 2);
            $actualVal = round(max(2.0, $baseSales + $weeklyEffect + $cycleEffect + $noise));

            // Kurva prediksi ARIMA dibuat mulus (tanpa random noise harian, weekly effect diperhalus)
            // Ini membuat garis hijau menjadi trendline meliuk yang indah di tengah fluktuasi orange
            $smoothWeekly = ($dayOfWeek == 5 || $dayOfWeek == 6 || $dayOfWeek == 0) ? 4.0 : -2.0;
            $predictedVal = round(max(2.0, $baseSales + $smoothWeekly + $cycleEffect), 2);

            $error = round($actualVal - $predictedVal, 4);
            $absError = abs($error);

            // ARIMA detail record
            $detailPayload[] = [
                'date'            => $dateStr,
                'produk'          => $sku,
                'kategori_mae'    => 'rendah',
                'actual_sales'    => $actualVal,
                'predicted_sales' => $predictedVal,
                'error'           => $error,
                'absolute_error'  => $absError,
                'data_type'       => 'actual',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            // Transaction (finished_goods_out) record
            $stockBefore = $currentStock;
            $stockAfter = $currentStock - $actualVal;
            $currentStock = $stockAfter; // Update running stock

            $fgOutPayload[] = [
                'item_id' => $itemId,
                'inventory_id' => $inventoryId,
                'branch_id' => $branchId,
                'transaction_sales_detail_id' => null,
                'issued_by' => $userId,
                'document_number' => 'FGO-SEEDED-' . $currentDate->format('Ymd') . '-' . str_pad($d, 3, '0', STR_PAD_LEFT),
                'qty_out' => $actualVal,
                'unit' => 'pcs',
                'unit_cost' => $item->costprice_item ?? 30000.0,
                'total_cost' => $actualVal * ($item->costprice_item ?? 30000.0),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'type' => 'sale',
                'out_date' => $dateStr,
                'notes' => 'Generated historical transaction for ARIMA testing',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Transaction (finished_goods_in) record
            $fgInPayload[] = [
                'item_id' => $itemId,
                'inventory_id' => $inventoryId,
                'branch_id' => $branchId,
                'received_by' => $userId,
                'document_number' => 'FGI-SEEDED-' . $currentDate->format('Ymd') . '-' . str_pad($d, 3, '0', STR_PAD_LEFT),
                'batch_number' => 'B-' . $currentDate->format('ymd'),
                'qty_received' => $actualVal,
                'unit' => 'pcs',
                'unit_cost' => $item->costprice_item ?? 30000.0,
                'total_cost' => $actualVal * ($item->costprice_item ?? 30000.0),
                'stock_before' => 0.0,
                'stock_after' => $actualVal,
                'qc_status' => 'passed',
                'received_date' => $dateStr,
                'production_date' => $dateStr,
                'notes' => 'Generated historical production for ARIMA testing',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ── 7.2. TESTING/ACTUAL PERIOD (37 hari) ──
        for ($d = 0; $d < 37; $d++) {
            $currentDate = $testStart->copy()->addDays($d);
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek;

            $weeklyEffect = ($dayOfWeek == 5 || $dayOfWeek == 6 || $dayOfWeek == 0) ? 6.0 : -3.0;
            $cycleEffect = 5.0 * sin(($d / 37.0) * 2.0 * M_PI * 2.0); // 2 complete waves
            $actualVal = round(max(2.0, $baseSales + $weeklyEffect + $cycleEffect + rand(-2, 2)));

            // Kurva prediksi dibuat mulus dan mengalir rapi di tengah fluktuasi aktual, dibatasi agar tidak melebihi aktual
            $smoothWeekly = ($dayOfWeek == 5 || $dayOfWeek == 6 || $dayOfWeek == 0) ? 4.0 : -2.0;
            $predictedVal = min($actualVal, round(max(2.0, $baseSales + $smoothWeekly + $cycleEffect), 2));

            $error = round($actualVal - $predictedVal, 4);
            $absError = abs($error);

            // ARIMA detail record
            $detailPayload[] = [
                'date'            => $dateStr,
                'produk'          => $sku,
                'kategori_mae'    => 'rendah',
                'actual_sales'    => $actualVal,
                'predicted_sales' => $predictedVal,
                'error'           => $error,
                'absolute_error'  => $absError,
                'data_type'       => 'actual',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            // Transaction (finished_goods_out) record
            $stockBefore = $currentStock;
            $stockAfter = $currentStock - $actualVal;
            $currentStock = $stockAfter; // Update running stock

            $fgOutPayload[] = [
                'item_id' => $itemId,
                'inventory_id' => $inventoryId,
                'branch_id' => $branchId,
                'transaction_sales_detail_id' => null,
                'issued_by' => $userId,
                'document_number' => 'FGO-SEEDED-' . $currentDate->format('Ymd') . '-' . str_pad($d + 100, 3, '0', STR_PAD_LEFT),
                'qty_out' => $actualVal,
                'unit' => 'pcs',
                'unit_cost' => $item->costprice_item ?? 30000.0,
                'total_cost' => $actualVal * ($item->costprice_item ?? 30000.0),
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'type' => 'sale',
                'out_date' => $dateStr,
                'notes' => 'Generated testing period transaction for ARIMA testing',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Transaction (finished_goods_in) record
            $fgInPayload[] = [
                'item_id' => $itemId,
                'inventory_id' => $inventoryId,
                'branch_id' => $branchId,
                'received_by' => $userId,
                'document_number' => 'FGI-SEEDED-' . $currentDate->format('Ymd') . '-' . str_pad($d + 100, 3, '0', STR_PAD_LEFT),
                'batch_number' => 'B-' . $currentDate->format('ymd'),
                'qty_received' => $actualVal,
                'unit' => 'pcs',
                'unit_cost' => $item->costprice_item ?? 30000.0,
                'total_cost' => $actualVal * ($item->costprice_item ?? 30000.0),
                'stock_before' => 0.0,
                'stock_after' => $actualVal,
                'qc_status' => 'passed',
                'received_date' => $dateStr,
                'production_date' => $dateStr,
                'notes' => 'Generated testing production for ARIMA testing',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // ── 7.3. FORECAST PERIOD (90 hari) ──
        for ($d = 0; $d < 90; $d++) {
            $currentDate = $forecastStart->copy()->addDays($d);
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = $currentDate->dayOfWeek;

            $weeklyEffect = ($dayOfWeek == 5 || $dayOfWeek == 6 || $dayOfWeek == 0) ? 6.0 : -3.0;
            $cycleEffect = 5.0 * sin(($d / 90.0) * 2.0 * M_PI * 4.5); // 4.5 waves
            $predictedVal = round(max(2.0, $baseSales + $weeklyEffect + $cycleEffect + (rand(-5, 5) / 10)), 2);

            // ARIMA detail record (forecasting period has NO transaction records yet)
            $detailPayload[] = [
                'date'            => $dateStr,
                'produk'          => $sku,
                'kategori_mae'    => 'rendah',
                'actual_sales'    => 0.0,
                'predicted_sales' => $predictedVal,
                'error'           => 0.0,
                'absolute_error'  => 0.0,
                'data_type'       => 'forecast',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        // 8. Bulk Insert into DB
        DB::transaction(function () use ($detailPayload, $fgOutPayload, $fgInPayload) {
            // Seed finished_goods_out
            foreach (array_chunk($fgOutPayload, 50) as $chunk) {
                DB::table('finished_goods_out')->insert($chunk);
            }

            // Seed finished_goods_in
            foreach (array_chunk($fgInPayload, 50) as $chunk) {
                DB::table('finished_goods_in')->insert($chunk);
            }

            // Seed arima_forecast_details
            foreach (array_chunk($detailPayload, 100) as $chunk) {
                DB::table('arima_forecast_details')->insert($chunk);
            }
        });

        $this->command?->info("Sukses! Berhasil menanam:");
        $this->command?->line(" - " . count($fgOutPayload) . " transaksi di finished_goods_out untuk " . $sku);
        $this->command?->line(" - " . count($detailPayload) . " detail ARIMA forecast di arima_forecast_details");
    }
}
