<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCategoryArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryArticles = [
            [
                'category_id' => 1,
                'name_category' => 'Kesehatan Bayi',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category_id' => 2,
                'name_category' => 'Nutrisi',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category_id' => 3,
                'name_category' => 'Parenting',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'category_id' => 4,
                'name_category' => 'Balita',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($categoryArticles as $cat) {
            DB::table('master_category_articles')->updateOrInsert(
                ['category_id' => $cat['category_id']],
                $cat
            );
        }
    }
}
