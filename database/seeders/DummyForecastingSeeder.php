<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterItem;
use App\Models\MasterItemRawMaterial;
use App\Models\MasterItemBillOfMaterials;
use App\Models\FinishedGoodsIn;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DummyForecastingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();
        try {
            // Get some real products from the database (e.g. GB-DS-10 and GB-CNF-10)
            $products = MasterItem::whereIn('code_item', ['GB-DS-10', 'GB-CNF-10'])->get();

            if ($products->isEmpty()) {
                // Fallback to any active products if specific codes aren't found
                $products = MasterItem::where('status_item', 'active')->limit(3)->get();
            }

            if ($products->isEmpty()) {
                $this->command->warn("No products found in MasterItem. Cannot run DummyForecastingSeeder.");
                DB::rollBack();
                return;
            }

            // Make sure we have some Raw Materials for BOM
            $rawKain = MasterItemRawMaterial::firstOrCreate(
                ['material_name' => 'Kain Lembut Premium (Dummy)'],
                [
                    'unit' => 'meter',
                    'purchase_price' => 35000,
                    'current_stock' => 500,
                    'lead_time_days' => 3,
                    'buffer_stock' => 100,
                    'supplier_name' => 'PT Tekstil Jaya'
                ]
            );

            $rawBenang = MasterItemRawMaterial::firstOrCreate(
                ['material_name' => 'Minyak Esensial (Dummy)'],
                [
                    'unit' => 'liter',
                    'purchase_price' => 120000,
                    'current_stock' => 50,
                    'lead_time_days' => 2,
                    'buffer_stock' => 10,
                    'supplier_name' => 'CV Minyak Maju'
                ]
            );

            foreach ($products as $item) {
                // Ensure BOM exists
                MasterItemBillOfMaterials::firstOrCreate(
                    ['item_id' => $item->item_id, 'item_raw_id' => $rawKain->item_raw_id],
                    ['quantity_required' => 0.5, 'yield_percentage' => 100]
                );

                MasterItemBillOfMaterials::firstOrCreate(
                    ['item_id' => $item->item_id, 'item_raw_id' => $rawBenang->item_raw_id],
                    ['quantity_required' => 0.05, 'yield_percentage' => 100]
                );

                // Delete old dummy data for this item if any
                FinishedGoodsIn::where('item_id', $item->item_id)->delete();
                DB::table('finished_goods_out')->where('item_id', $item->item_id)->delete();

                // 60 Days of Dummy Production Data for Forecasting (ARIMA needs > 14 days)
                $startDate = Carbon::now()->subDays(60);
                
                // Base trend with random noise
                $baseProduction = rand(30, 80);
                
                for ($i = 0; $i < 60; $i++) {
                    $currentDate = $startDate->copy()->addDays($i);
                    
                    // Add seasonality (weekends have +20)
                    $dayOfWeek = $currentDate->dayOfWeek;
                    $seasonality = ($dayOfWeek == 0 || $dayOfWeek == 6) ? 20 : 0;
                    
                    // Add trend
                    $trend = $i * 0.5;
                    
                    // Add random noise (-15 to +15)
                    $noise = rand(-15, 15);
                    
                    $qty = max(10, round($baseProduction + $seasonality + $trend + $noise));
                    
                    FinishedGoodsIn::create([
                        'item_id' => $item->item_id,
                        'inventory_id' => 1,
                        'branch_id' => 1,
                        'received_by' => 1,
                        'qty_received' => $qty,
                        'unit' => 'pcs',
                        'unit_cost' => 25000,
                        'total_cost' => $qty * 25000,
                        'stock_before' => 0,
                        'stock_after' => $qty,
                        'received_date' => $currentDate,
                        'production_date' => $currentDate,
                        'document_number' => 'DUMMY-FGI-' . $item->code_item . '-' . $currentDate->format('Ymd'),
                        'qc_status' => 'passed'
                    ]);

                    DB::table('finished_goods_out')->insert([
                        'item_id' => $item->item_id,
                        'inventory_id' => 1,
                        'branch_id' => 1,
                        'issued_by' => 1,
                        'qty_out' => $qty,
                        'unit' => 'pcs',
                        'unit_cost' => 25000,
                        'total_cost' => $qty * 25000,
                        'stock_before' => $qty,
                        'stock_after' => 0,
                        'type' => 'sale',
                        'out_date' => $currentDate,
                        'document_number' => 'DUMMY-FGO-' . $item->code_item . '-' . $currentDate->format('Ymd'),
                        'notes' => 'Dummy out',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
                
                $this->command->info("Dummy Forecasting data for '{$item->name_item}' created successfully!");
            }

            DB::commit();
            $this->command->info("Dummy Forecasting Seeder completed successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Seeder failed", ['error' => $e->getMessage()]);
            $this->command->error("Failed to seed: " . $e->getMessage());
        }
    }
}
