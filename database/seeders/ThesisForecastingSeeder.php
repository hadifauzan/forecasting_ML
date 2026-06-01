<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\MasterCategory;
use App\Models\MasterItemRawMaterial;
use App\Models\MasterItem;
use App\Models\MasterItemBillOfMaterials;
use App\Models\MasterItemStock;
use App\Models\MasterInventory;
use Illuminate\Support\Str;

class ThesisForecastingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Pastikan ada Inventory gudang utama
        $inventory = MasterInventory::firstOrCreate(
            ['name_inventory' => 'Gudang Utama Gentle Baby'],
            ['branch_id' => null]
        );

        // 2. Buat Kategori
        $category = MasterCategory::firstOrCreate(
            ['name_category' => 'Baby Care Products']
        );

        // 3. Buat Bahan Baku (Raw Materials)
        $rawMaterials = [
            [
                'material_name' => 'Essential Oil Peppermint',
                'unit' => 'ml',
                'purchase_price' => 5000,
                'current_stock' => 15000,
                'lead_time_days' => 5,
                'avg_daily_usage' => 200,
                'supplier_name' => 'PT Natural Indo',
            ],
            [
                'material_name' => 'Essential Oil Lavender',
                'unit' => 'ml',
                'purchase_price' => 6500,
                'current_stock' => 12000,
                'lead_time_days' => 7,
                'avg_daily_usage' => 150,
                'supplier_name' => 'PT Herbal Alami',
            ],
            [
                'material_name' => 'Carrier Oil (Minyak Kelapa)',
                'unit' => 'ml',
                'purchase_price' => 500,
                'current_stock' => 50000,
                'lead_time_days' => 3,
                'avg_daily_usage' => 1000,
                'supplier_name' => 'CV Sumber Alam',
            ],
            [
                'material_name' => 'Botol Roll-On Kaca 10ml',
                'unit' => 'pcs',
                'purchase_price' => 2500,
                'current_stock' => 5000,
                'lead_time_days' => 14,
                'avg_daily_usage' => 100,
                'supplier_name' => 'PT Glass Indo',
            ],
            [
                'material_name' => 'Label Stiker Produk',
                'unit' => 'pcs',
                'purchase_price' => 500,
                'current_stock' => 10000,
                'lead_time_days' => 3,
                'avg_daily_usage' => 100,
                'supplier_name' => 'Percetakan Berkah',
            ]
        ];

        $rawMaterialIds = [];
        foreach ($rawMaterials as $rm) {
            // Kalkulasi awal buffer dan ROP
            $rm['buffer_stock'] = ($rm['avg_daily_usage'] * $rm['lead_time_days']) * 0.2;
            $rm['reorder_point'] = ($rm['avg_daily_usage'] * $rm['lead_time_days']) + $rm['buffer_stock'];
            $rm['stock_status'] = 'normal';

            $model = MasterItemRawMaterial::updateOrCreate(
                ['material_name' => $rm['material_name']],
                $rm
            );
            
            $rawMaterialIds[$rm['material_name']] = $model->item_raw_id;
        }

        // 4. Buat Master Item (Gentle Baby Products)
        $products = [
            [
                'code_item' => 'GB-CF01',
                'name_item' => 'Gentle Baby Cough & Flu 10ml',
                'costprice_item' => 15000,
                'sellingprice_item' => 65000,
                'category_id' => $category->category_id,
                'bom' => [
                    'Essential Oil Peppermint' => 1.5,
                    'Carrier Oil (Minyak Kelapa)' => 8.5,
                    'Botol Roll-On Kaca 10ml' => 1,
                    'Label Stiker Produk' => 1
                ],
                'base_demand' => 50 // base demand for daily variance
            ],
            [
                'code_item' => 'GB-DS01',
                'name_item' => 'Gentle Baby Deep Sleep 10ml',
                'costprice_item' => 18000,
                'sellingprice_item' => 70000,
                'category_id' => $category->category_id,
                'bom' => [
                    'Essential Oil Lavender' => 2.0,
                    'Carrier Oil (Minyak Kelapa)' => 8.0,
                    'Botol Roll-On Kaca 10ml' => 1,
                    'Label Stiker Produk' => 1
                ],
                'base_demand' => 35
            ]
        ];

        foreach ($products as $prodData) {
            $item = MasterItem::updateOrCreate(
                ['code_item' => $prodData['code_item']],
                [
                    'company_id' => 1,
                    'name_item' => $prodData['name_item'],
                    'costprice_item' => $prodData['costprice_item'],
                    'sellingprice_item' => $prodData['sellingprice_item'],
                    'status_item' => 'active',
                    'current_inventory' => 0 
                ]
            );

            // Pasang Kategori via Pivot
            $item->categories()->sync([$prodData['category_id']]);

            // Buat BOM
            MasterItemBillOfMaterials::where('item_id', $item->item_id)->delete();
            foreach ($prodData['bom'] as $rmName => $qty) {
                MasterItemBillOfMaterials::create([
                    'item_id' => $item->item_id,
                    'item_raw_id' => $rawMaterialIds[$rmName],
                    'quantity_required' => $qty,
                    'yield_percentage' => 100
                ]);
            }

            // Buat Stok Master
            $stock = MasterItemStock::updateOrCreate(
                [
                    'item_id' => $item->item_id,
                    'inventory_id' => $inventory->inventory_id
                ],
                [
                    'stock' => 0,
                    'buffer_stock' => 0
                ]
            );

            // 5. Generate Histori Penjualan 60 hari terakhir (FinishedGoodsOut) & Produksi (FinishedGoodsIn)
            $today = Carbon::today();
            $totalSales = 0;
            $maxSales = 0;

            // Hapus data dummy sebelumnya untuk item ini agar clean
            DB::table('finished_goods_out')->where('item_id', $item->item_id)->delete();
            DB::table('finished_goods_in')->where('item_id', $item->item_id)->delete();

            for ($i = 60; $i >= 1; $i--) {
                $date = $today->copy()->subDays($i);
                
                // Simulate daily demand with some noise
                $base = $prodData['base_demand'];
                
                // Add some trend (increase slightly over time)
                $trend = (60 - $i) * 0.5;
                
                // Add some seasonality (weekend spike)
                $seasonality = ($date->isWeekend()) ? $base * 0.4 : 0;
                
                // Random noise
                $noise = rand(-10, 15);
                
                $dailyDemand = max(5, intval($base + $trend + $seasonality + $noise));
                
                $totalSales += $dailyDemand;
                if ($dailyDemand > $maxSales) {
                    $maxSales = $dailyDemand;
                }

                // Insert into out transaction
                DB::table('finished_goods_out')->insert([
                    'item_id' => $item->item_id,
                    'inventory_id' => $inventory->inventory_id,
                    'branch_id' => 1,
                    'issued_by' => 1,
                    'qty_out' => $dailyDemand,
                    'out_date' => $date->format('Y-m-d'),
                    'type' => 'sale',
                    'notes' => 'Simulated sales for ML',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                // Seed finished_goods_in daily to match daily sales demand
                DB::table('finished_goods_in')->insert([
                    'item_id' => $item->item_id,
                    'inventory_id' => $inventory->inventory_id,
                    'branch_id' => 1,
                    'received_by' => 1,
                    'qty_received' => $dailyDemand,
                    'received_date' => $date->format('Y-m-d'),
                    'production_date' => $date->format('Y-m-d'),
                    'document_number' => 'FGI-SEEDED-' . $item->code_item . '-' . $date->format('Ymd'),
                    'batch_number' => 'B-' . $date->format('ymd'),
                    'qc_status' => 'passed',
                    'unit' => 'pcs',
                    'unit_cost' => $item->costprice_item ?? 15000.0,
                    'total_cost' => $dailyDemand * ($item->costprice_item ?? 15000.0),
                    'stock_before' => 0.0,
                    'stock_after' => $dailyDemand,
                    'production_order_id' => null, // Manual entry
                    'notes' => 'Simulated production matching daily sales demand',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            // Hitung aktual buffer stock di MasterItemStock (Simulasi)
            // Rumus: (Max * 7) - (Avg * 5.4)
            $avgSales = $totalSales / 60;
            $bufferStockCalc = ($maxSales * 7) - ($avgSales * 5.4);
            
            // Beri stok sisa
            $stock->stock = intval($bufferStockCalc * 1.5); // Aman
            $stock->buffer_stock = intval($bufferStockCalc);
            $stock->save();

            // Update item current_inventory
            $item->current_inventory = $stock->stock;
            // 6. Generate Dummy ARIMA Forecast Data
            DB::table('arima_forecast_summaries')->where('produk', $prodData['code_item'])->delete();
            DB::table('arima_forecast_details')->where('produk', $prodData['code_item'])->delete();

            // Insert Summary
            DB::table('arima_forecast_summaries')->insert([
                'produk' => $prodData['code_item'],
                'arima_order' => '(1, 1, 1)',
                'mae' => 2.5,
                'rmse' => 3.1,
                'mape_percentage' => 12.5,
                'stationary' => 1,
                'adf_p_value' => 0.01,
                'kategori_mae' => 'rendah',
                'created_at' => $today,
                'updated_at' => $today,
            ]);

            // Re-fetch the actual sales to generate matching training and testing data
            $actualSalesData = DB::table('finished_goods_out')
                ->where('item_id', $item->item_id)
                ->orderBy('out_date', 'asc')
                ->get();
            
            $totalData = $actualSalesData->count();
            $testPeriod = 14; // Last 14 days for testing (actual vs predicted)
            $trainPeriod = $totalData - $testPeriod;

            foreach ($actualSalesData as $index => $sale) {
                $isTest = $index >= $trainPeriod;
                $actualVal = $sale->qty_out;
                
                // Simulate predicted value (close to actual to simulate a good ML model, capped so it never exceeds actual)
                $noise = ($isTest) ? rand(-3, -1) : rand(-2, 0);
                $predictedVal = min($actualVal, max(0, $actualVal + $noise));
                
                $error = $actualVal - $predictedVal;
                
                DB::table('arima_forecast_details')->insert([
                    'date' => $sale->out_date,
                    'produk' => $prodData['code_item'],
                    'kategori_mae' => 'rendah',
                    'actual_sales' => $actualVal,
                    'predicted_sales' => $predictedVal,
                    'error' => $error,
                    'absolute_error' => abs($error),
                    'data_type' => $isTest ? 'actual' : 'training',
                    'created_at' => $today,
                    'updated_at' => $today,
                ]);
            }

            // Insert Forecast Details (Next 30 days)
            $forecastBase = $avgSales;
            for ($j = 1; $j <= 30; $j++) {
                $forecastDate = $today->copy()->addDays($j);
                
                // Add some trend and seasonality for forecast to make it go up and down
                $f_trend = $j * 0.2;
                $base = $prodData['base_demand'];
                $f_seasonality = ($forecastDate->isWeekend()) ? $base * 0.4 : -($base * 0.1);
                $f_noise = rand(-6, 9);
                
                $predictedSales = max(5, $forecastBase + $f_trend + $f_seasonality + $f_noise);

                DB::table('arima_forecast_details')->insert([
                    'date' => $forecastDate->format('Y-m-d'),
                    'produk' => $prodData['code_item'],
                    'kategori_mae' => 'rendah',
                    'actual_sales' => null,
                    'predicted_sales' => $predictedSales,
                    'error' => null,
                    'absolute_error' => null,
                    'data_type' => 'forecast',
                    'created_at' => $today,
                    'updated_at' => $today,
                ]);
            }
        }

        $this->command->info('Seeder demonstrasi (Gentle Baby) untuk Forecasting berhasil dijalankan!');
    }
}
