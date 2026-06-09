<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterItemBillOfMaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing BOM rows first to avoid duplicates
        DB::table('master_items_bill_of_materials')->truncate();

        // Get all master items
        $items = DB::table('master_items')->get();

        $bomRecords = [];

        // Brand-to-essence mapping
        $essenceMap = [
            'DS'  => 7,  // Essence Oil DS 5ml
            'JOY' => 11, // Essence Oil JOY 15ml
            'CNF' => 6,  // Essence Oil CNF 15ml
            'BB'  => 6,  // Essence Oil CNF 15ml (Proxy)
            'GF'  => 8,  // Essence Oil GF 15ml
            'TC'  => 9,  // Essence Oil TC 15ml
            'LDR' => 12, // Essence Oil LDR 15ml
            'MYB' => 11, // Essence Oil JOY 15ml (Proxy)
            'IMB' => 10, // Essence Oil IM 15ml
        ];

        // Brand-to-sticker mapping
        $stickerMap = [
            'CNF' => [10 => 14, 15 => 15, 30 => 16, 100 => 17, 250 => 18],
            'DS'  => [10 => 19, 15 => 20, 30 => 21, 100 => 22, 250 => 23],
            'GF'  => [10 => 24, 15 => 25, 30 => 26, 100 => 27, 250 => 28],
            'TC'  => [10 => 29, 15 => 30, 30 => 31, 100 => 32, 250 => 33],
            'IMB' => [10 => 34, 15 => 35, 30 => 36, 100 => 37, 250 => 38],
            'JOY' => [10 => 39, 15 => 40, 30 => 41, 100 => 42, 250 => 43],
            'LDR' => [10 => 44, 15 => 45, 30 => 46, 100 => 47, 250 => 48],
            'MYB' => [10 => 49, 15 => 50, 30 => 51, 100 => 52, 250 => 53],
            'BB'  => [10 => 54, 15 => 55, 30 => 56, 100 => 57, 250 => 58],
        ];

        // Size configuration details
        $sizeConfig = [
            10 => [
                'bottle_id' => 1,  // Botol 10 ml
                'pump_id'   => 59, // Pump 10ml
                'carrier'   => 8.0,
                'essence'   => 2.0,
            ],
            15 => [
                'bottle_id' => 2,  // Botol 15 ml
                'pump_id'   => 60, // Pump 15ml
                'carrier'   => 12.0,
                'essence'   => 3.0,
            ],
            30 => [
                'bottle_id' => 3,  // Botol 30 ml
                'pump_id'   => 61, // Pump 30ml
                'carrier'   => 24.0,
                'essence'   => 6.0,
            ],
            100 => [
                'bottle_id' => 4,  // Botol 100 ml
                'pump_id'   => 62, // Pump 100ml
                'carrier'   => 80.0,
                'essence'   => 20.0,
            ],
            250 => [
                'bottle_id' => 5,  // Botol 250 ml
                'pump_id'   => 63, // Pump 200ml (Proxy)
                'carrier'   => 200.0,
                'essence'   => 50.0,
            ]
        ];

        $now = now();

        foreach ($items as $item) {
            $code = $item->code_item; // e.g., "GB-DS-10" or "GB-TP-CC"

            // Parse pattern "GB-{BRAND}-{SIZE}"
            if (preg_match('/^GB-([A-Z]+)-([0-9]+)$/', $code, $matches)) {
                $brand = $matches[1];
                $size  = (int) $matches[2];

                if (isset($sizeConfig[$size])) {
                    $config = $sizeConfig[$size];

                    // 1. Bottle
                    $bomRecords[] = [
                        'item_id'           => $item->item_id,
                        'item_raw_id'       => $config['bottle_id'],
                        'quantity_required' => 1.0,
                        'yield_percentage'  => 100.00,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    // // 2. Pump
                    // $bomRecords[] = [
                    //     'item_id'           => $item->item_id,
                    //     'item_raw_id'       => $config['pump_id'],
                    //     'quantity_required' => 1.0,
                    //     'yield_percentage'  => 100.00,
                    //     'created_at'        => $now,
                    //     'updated_at'        => $now,
                    // ];

                    // 3. Sticker (if mapping exists)
                    if (isset($stickerMap[$brand][$size])) {
                        $bomRecords[] = [
                            'item_id'           => $item->item_id,
                            'item_raw_id'       => $stickerMap[$brand][$size],
                            'quantity_required' => 1.0,
                            'yield_percentage'  => 100.00,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ];
                    }

                    // 4. Carrier Oil (Sunflower Oil ID 13)
                    $bomRecords[] = [
                        'item_id'           => $item->item_id,
                        'item_raw_id'       => 13, // Sunflower Oil
                        'quantity_required' => $config['carrier'],
                        'yield_percentage'  => 98.00, // 2% wastage
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    // 5. Essence Oil
                    if (isset($essenceMap[$brand])) {
                        $bomRecords[] = [
                            'item_id'           => $item->item_id,
                            'item_raw_id'       => $essenceMap[$brand],
                            'quantity_required' => $config['essence'],
                            'yield_percentage'  => 95.00, // 5% wastage
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ];
                    }
                }
            } else {
                // Fallback / other items (like twin packs, Healo, Mamina)
                // Let's seed simple generic BOMs so they have some raw materials
                // Seed a bottle and a sticker based on name or code keywords
                $bottleId = 1; // Default Botol 10ml
                $stickerId = 14; // Default Stiker CNF 10ml
                $oilId = 13; // Sunflower Oil

                if (str_contains($code, '30')) {
                    $bottleId = 3;
                    $stickerId = 16;
                } elseif (str_contains($code, '100')) {
                    $bottleId = 4;
                    $stickerId = 17;
                }

                $bomRecords[] = [
                    'item_id'           => $item->item_id,
                    'item_raw_id'       => $bottleId,
                    'quantity_required' => 1.0,
                    'yield_percentage'  => 100.00,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                $bomRecords[] = [
                    'item_id'           => $item->item_id,
                    'item_raw_id'       => $stickerId,
                    'quantity_required' => 1.0,
                    'yield_percentage'  => 100.00,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                $bomRecords[] = [
                    'item_id'           => $item->item_id,
                    'item_raw_id'       => $oilId,
                    'quantity_required' => 10.0,
                    'yield_percentage'  => 98.00,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }
        }

        // Insert BOM records in chunks
        foreach (array_chunk($bomRecords, 100) as $chunk) {
            DB::table('master_items_bill_of_materials')->insert($chunk);
        }
    }
}
