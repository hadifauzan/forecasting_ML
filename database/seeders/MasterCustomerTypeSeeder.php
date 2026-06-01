<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'customer_type_id' => 1,
                'name_customer_type' => 'Regular Customer',
                'reseller' => 'No',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_type_id' => 2,
                'name_customer_type' => 'Agent',
                'reseller' => 'Yes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_type_id' => 3,
                'name_customer_type' => 'Reseller',
                'reseller' => 'Yes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'customer_type_id' => 4,
                'name_customer_type' => 'Reseller Baby Spa',
                'reseller' => 'Yes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($types as $type) {
            DB::table('master_customers_types')->updateOrInsert(
                ['customer_type_id' => $type['customer_type_id']],
                $type
            );
        }
    }
}
