<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert ke master_items
        $items = [
            // ========== GENTLE BABY PRODUCTS ==========
            
            // Deep Sleep - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-DS-10',
                'name_item' => 'Gentle Baby Deep Sleep 10ml',
                'description_item' => 'Minyak bayi untuk membantu bayi tidur nyenyak',
                'ingredient_item' => 'Minyak Lavender, Minyak Chamomile, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "deep-sleep-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-DS-30',
                'name_item' => 'Gentle Baby Deep Sleep 30ml',
                'description_item' => 'Minyak bayi untuk membantu bayi tidur nyenyak',
                'ingredient_item' => 'Minyak Lavender, Minyak Chamomile, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "deep-sleep-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-DS-100',
                'name_item' => 'Gentle Baby Deep Sleep 100ml',
                'description_item' => 'Minyak bayi untuk membantu bayi tidur nyenyak',
                'ingredient_item' => 'Minyak Lavender, Minyak Chamomile, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "deep-sleep-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-DS-250',
                'name_item' => 'Gentle Baby Deep Sleep 250ml',
                'description_item' => 'Minyak bayi untuk membantu bayi tidur nyenyak',
                'ingredient_item' => 'Minyak Lavender, Minyak Chamomile, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "deep-sleep-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Joy - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-JOY-10',
                'name_item' => 'Gentle Baby Joy 10ml',
                'description_item' => 'Minyak bayi untuk meningkatkan mood anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "joy-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-JOY-30',
                'name_item' => 'Gentle Baby Joy 30ml',
                'description_item' => 'Minyak bayi untuk meningkatkan mood anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "joy-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-JOY-100',
                'name_item' => 'Gentle Baby Joy 100ml',
                'description_item' => 'Minyak bayi untuk meningkatkan mood anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "joy-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-JOY-250',
                'name_item' => 'Gentle Baby Joy 250ml',
                'description_item' => 'Minyak bayi untuk meningkatkan mood anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "joy-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Cough n Flu - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-CNF-10',
                'name_item' => 'Gentle Baby Cough n Flu 10ml',
                'description_item' => 'Minyak bayi yang membantu meredakan batuk dan flu pada anak bayi',
                'ingredient_item' => 'Minyak Eucalyptus, Minyak Lavender, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "cough-flu-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-CNF-30',
                'name_item' => 'Gentle Baby Cough n Flu 30ml',
                'description_item' => 'Minyak bayi yang membantu meredakan batuk dan flu pada anak bayi',
                'ingredient_item' => 'Minyak Eucalyptus, Minyak Lavender, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "cough-flu-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-CNF-100',
                'name_item' => 'Gentle Baby Cough n Flu 100ml',
                'description_item' => 'Minyak bayi yang membantu meredakan batuk dan flu pada anak bayi',
                'ingredient_item' => 'Minyak Eucalyptus, Minyak Lavender, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "cough-flu-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-CNF-250',
                'name_item' => 'Gentle Baby Cough n Flu 250ml',
                'description_item' => 'Minyak bayi yang membantu meredakan batuk dan flu pada anak bayi',
                'ingredient_item' => 'Minyak Eucalyptus, Minyak Lavender, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "cough-flu-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Bye Bugs - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-BB-10',
                'name_item' => 'Gentle Baby Bye Bugs 10ml',
                'description_item' => 'Minyak bayi yang mengusir nyamuk dan serangga dengan aman',
                'ingredient_item' => 'Minyak Citronella, Minyak Lemongrass, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "bye-bugs.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-BB-30',
                'name_item' => 'Gentle Baby Bye Bugs 30ml',
                'description_item' => 'Minyak bayi untuk meningkatkan mood anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "bye-bugs-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-BB-100',
                'name_item' => 'Gentle Baby Bye Bugs 100ml',
                'description_item' => 'Minyak bayi untuk meningkatkan mood anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "bye-bugs-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Gimme Food - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-GF-10',
                'name_item' => 'Gentle Baby Gimme Food 10ml',
                'description_item' => 'Minyak untuk meningkatkan nafsu makan pada bayi',
                'ingredient_item' => 'Minyak Chamomile, Minyak Zinc Oxide, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "gimme-food-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-GF-30',
                'name_item' => 'Gentle Baby Gimme Food 30ml',
                'description_item' => 'Minyak untuk meningkatkan nafsu makan pada bayi',
                'ingredient_item' => 'Minyak Chamomile, Minyak Zinc Oxide, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "gimme-food-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-GF-100',
                'name_item' => 'Gentle Baby Gimme Food 100ml',
                'description_item' => 'Minyak untuk meningkatkan nafsu makan pada bayi',
                'ingredient_item' => 'Minyak Chamomile, Minyak Zinc Oxide, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "gimme-food-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-GF-250',
                'name_item' => 'Gentle Baby Gimme Food 250ml',
                'description_item' => 'Minyak untuk meningkatkan nafsu makan pada bayi',
                'ingredient_item' => 'Minyak Chamomile, Minyak Zinc Oxide, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "gimme-food-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Tummy Calmer - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-TC-10',
                'name_item' => 'Gentle Baby Tummy Calmer 10ml',
                'description_item' => 'Minyak bayi untuk perut kembung dan kolik',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "tummy-calmer-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-TC-30',
                'name_item' => 'Gentle Baby Tummy Calmer 30ml',
                'description_item' => 'Minyak bayi untuk perut kembung dan kolik',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "tummy-calmer-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-TC-100',
                'name_item' => 'Gentle Baby Tummy Calmer 100ml',
                'description_item' => 'Minyak bayi untuk perut kembung dan kolik',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "tummy-calmer-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-TC-250',
                'name_item' => 'Gentle Baby Tummy Calmer 250ml',
                'description_item' => 'Minyak bayi untuk perut kembung dan kolik',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "tummy-calmer-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // LDR Booster - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-LDR-10',
                'name_item' => 'Gentle Baby LDR Booster 10ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "ldr-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-LDR-30',
                'name_item' => 'Gentle Baby LDR Booster 30ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "ldr-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-LDR-100',
                'name_item' => 'Gentle Baby LDR Booster 100ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "ldr-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-LDR-250',
                'name_item' => 'Gentle Baby LDR Booster 250ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "ldr-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Massage Your Baby - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-MYB-10',
                'name_item' => 'Gentle Baby Massage Your Baby 10ml',
                'description_item' => 'Minyak bayi untuk pijat bayi',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "massage-your-baby-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-MYB-30',
                'name_item' => 'Gentle Baby Massage Your Baby 30ml',
                'description_item' => 'Minyak bayi untuk pijat bayi',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "massage-your-baby-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-MYB-100',
                'name_item' => 'Gentle Baby Massage Your Baby 100ml',
                'description_item' => 'Minyak bayi untuk pijat bayi',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "massage-your-baby-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-MYB-250',
                'name_item' => 'Gentle Baby Massage Your Baby 250ml',
                'description_item' => 'Minyak bayi untuk pijat bayi',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "massage-your-baby-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Immboost - 10ml, 30ml, 100ml, 250ml
            [
                'company_id' => 3,
                'code_item' => 'GB-IMB-10',
                'name_item' => 'Gentle Baby Immboost 10ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '10ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 12000,
                'picture_item' => "immboost-10ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-IMB-30',
                'name_item' => 'Gentle Baby Immboost 30ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 25000,
                'picture_item' => "immboost-30ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-IMB-100',
                'name_item' => 'Gentle Baby Immboost 100ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '100ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 30000,
                'picture_item' => "immboost-100ml.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-IMB-250',
                'name_item' => 'Gentle Baby Immboost 250ml',
                'description_item' => 'Minyak bayi untuk meningkatkan daya tahan tubuh anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '250ml',
                'contain_item' => '1 botol minyak bayi',
                'costprice_item' => 100000,
                'picture_item' => "immboost-250ml.jpg",
                'status_item' => 'active',
                'is_reseller_babyspa' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // ========== GENTLE BABY TWIN PACKS ==========
            [
                'company_id' => 3,
                'code_item' => 'GB-TP-CC',
                'name_item' => 'Gentle Twin Pack Common Cold',
                'description_item' => 'Minyak bayi untuk meningkatkan menghilangkan kedinginan anak',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 pack minyak bayi',
                'costprice_item' => 29000,
                'picture_item' => "twin-pack-common-cold.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'company_id' => 3,
                'code_item' => 'GB-TP-NB',
                'name_item' => 'Gentle Twin Pack New Born',
                'description_item' => 'Minyak bayi untuk bayi baru lahir',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 pack minyak bayi',
                'costprice_item' => 29000,
                'picture_item' => "twin-pack-newborn.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],            
            [
                'company_id' => 3,
                'code_item' => 'GB-TP-TV',
                'name_item' => 'Gentle Twin Pack Travel Pack',
                'description_item' => 'Minyak bayi untuk berpergian jauh',
                'ingredient_item' => 'Minyak Peppermint, Minyak Fennel, Minyak Kelapa',
                'netweight_item' => '30ml',
                'contain_item' => '1 pack minyak bayi',
                'costprice_item' => 29000,
                'picture_item' => "twin-pack-travelpack.jpg",
                'status_item' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            

            
        ];

        foreach ($items as $item) {
            DB::table('master_items')->updateOrInsert(
                ['code_item' => $item['code_item']],
                $item
            );
        }

        // Insert ke master_items_details untuk harga jual berdasarkan customer type
        $itemDetails = [

            // deep sleep
            ['item_id' => 1, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 1, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 2, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 2, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 3, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 3, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 4, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],

            // joy 
            ['item_id' => 5, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 5, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 6, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 6, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 7, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 7, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 8, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],
            
            // cough
            ['item_id' => 9, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 9, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 10, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 10, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 11, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 11, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 12, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],

            // bye bugs
            ['item_id' => 13, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 13, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 14, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 14, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 15, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 15, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            
            //gimme food
            ['item_id' => 16, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 16, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 17, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 17, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 18, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 18, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 19, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],
            
            // tummy calmer
            ['item_id' => 20, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 20, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 21, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 21, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 22, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 22, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 23, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],

            // ldr booster
            ['item_id' => 24, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 24, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 25, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 25, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 26, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 26, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 27, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],

            // massage your baby
            ['item_id' => 28, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 28, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 29, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 29, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 30, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 30, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 31, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],

            // immboost
            ['item_id' => 32, 'customer_type_id' => 1, 'cost_price' => 12000, 'sell_price' => 38500],
            ['item_id' => 32, 'customer_type_id' => 3, 'cost_price' => 12000, 'sell_price' => 27000],
            ['item_id' => 33, 'customer_type_id' => 1, 'cost_price' => 25000, 'sell_price' => 90000],
            ['item_id' => 33, 'customer_type_id' => 3, 'cost_price' => 25000, 'sell_price' => 63000],
            ['item_id' => 34, 'customer_type_id' => 1, 'cost_price' => 30000, 'sell_price' => 275000],
            ['item_id' => 34, 'customer_type_id' => 3, 'cost_price' => 30000, 'sell_price' => 192000],
            ['item_id' => 35, 'customer_type_id' => 4, 'cost_price' => 100000, 'sell_price' => 180000],

     ];

        foreach ($itemDetails as $detail) {
            DB::table('master_items_details')->updateOrInsert(
                [
                    'item_id' => $detail['item_id'],
                    'customer_type_id' => $detail['customer_type_id']
                ],
                array_merge($detail, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        // Insert ke master_items_stock untuk stok
        $stockData = [
            // Gentle Baby Stock
            ['item_id' => 1, 'inventory_id' => 1, 'stock' => 15],
            ['item_id' => 2, 'inventory_id' => 1, 'stock' => 8],
            ['item_id' => 3, 'inventory_id' => 1, 'stock' => 20],
            ['item_id' => 4, 'inventory_id' => 1, 'stock' => 12],
            ['item_id' => 5, 'inventory_id' => 1, 'stock' => 18],
            ['item_id' => 6, 'inventory_id' => 1, 'stock' => 15],
            ['item_id' => 7, 'inventory_id' => 1, 'stock' => 16],
            ['item_id' => 8, 'inventory_id' => 1, 'stock' => 13],
            ['item_id' => 9, 'inventory_id' => 1, 'stock' => 11],
            ['item_id' => 10, 'inventory_id' => 1, 'stock' => 11],
            ['item_id' => 11, 'inventory_id' => 1, 'stock' => 11],
            ['item_id' => 12, 'inventory_id' => 1, 'stock' => 11],
            ['item_id' => 13, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 14, 'inventory_id' => 1, 'stock' => 10],
            ['item_id' => 15, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 16, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 17, 'inventory_id' => 1, 'stock' => 10],
            ['item_id' => 18, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 19, 'inventory_id' => 1, 'stock' => 10],
            ['item_id' => 20, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 21, 'inventory_id' => 1, 'stock' => 10],
            ['item_id' => 22, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 23, 'inventory_id' => 1, 'stock' => 10],
            ['item_id' => 24, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 25, 'inventory_id' => 1, 'stock' => 10],
            ['item_id' => 26, 'inventory_id' => 1, 'stock' => 25],
            ['item_id' => 27, 'inventory_id' => 1, 'stock' => 30],
            ['item_id' => 28, 'inventory_id' => 1, 'stock' => 20],
            ['item_id' => 29, 'inventory_id' => 1, 'stock' => 26],
            ['item_id' => 30, 'inventory_id' => 1, 'stock' => 35],
            ['item_id' => 31, 'inventory_id' => 1, 'stock' => 40],
            ['item_id' => 32, 'inventory_id' => 1, 'stock' => 28],
            ['item_id' => 33, 'inventory_id' => 1, 'stock' => 32],
            ['item_id' => 34, 'inventory_id' => 1, 'stock' => 30],
            ['item_id' => 35, 'inventory_id' => 1, 'stock' => 27],
        ];

        foreach ($stockData as $stock) {
            DB::table('master_items_stock')->updateOrInsert(
                [
                    'item_id' => $stock['item_id'],
                    'inventory_id' => $stock['inventory_id']
                ],
                array_merge($stock, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        // Insert ke master_items_categories untuk kategori produk
        $categoryData = [
            // Gentle Baby - Category ID 1 (gentle-baby)
            ['categories_id' => 1, 'item_id' => '1'],
            ['categories_id' => 1, 'item_id' => '2'],
            ['categories_id' => 1, 'item_id' => '3'],
            ['categories_id' => 1, 'item_id' => '4'],
            ['categories_id' => 1, 'item_id' => '5'],
            ['categories_id' => 1, 'item_id' => '6'],
            ['categories_id' => 1, 'item_id' => '7'],
            ['categories_id' => 1, 'item_id' => '8'],
            ['categories_id' => 1, 'item_id' => '9'],
            ['categories_id' => 1, 'item_id' => '10'],
            ['categories_id' => 1, 'item_id' => '11'],
            ['categories_id' => 1, 'item_id' => '12'],
            ['categories_id' => 1, 'item_id' => '12'],
            ['categories_id' => 1, 'item_id' => '13'],
            ['categories_id' => 1, 'item_id' => '14'],
            ['categories_id' => 1, 'item_id' => '15'],
            ['categories_id' => 1, 'item_id' => '16'],
            ['categories_id' => 1, 'item_id' => '17'],
            ['categories_id' => 1, 'item_id' => '18'],
            ['categories_id' => 1, 'item_id' => '19'],
            ['categories_id' => 1, 'item_id' => '20'],
            ['categories_id' => 1, 'item_id' => '21'],
            ['categories_id' => 1, 'item_id' => '22'],
            ['categories_id' => 1, 'item_id' => '23'],
            ['categories_id' => 1, 'item_id' => '24'],
            ['categories_id' => 1, 'item_id' => '25'],
            ['categories_id' => 1, 'item_id' => '26'],
            ['categories_id' => 1, 'item_id' => '26'],
            ['categories_id' => 1, 'item_id' => '28'],
            ['categories_id' => 1, 'item_id' => '29'],
            ['categories_id' => 1, 'item_id' => '30'],
            ['categories_id' => 1, 'item_id' => '31'],
            ['categories_id' => 1, 'item_id' => '32'],
            ['categories_id' => 1, 'item_id' => '33'],
            ['categories_id' => 1, 'item_id' => '34'],
            ['categories_id' => 1, 'item_id' => '35'],
            
            ['categories_id' => 1, 'item_id' => '36'],
            ['categories_id' => 1, 'item_id' => '37'],
            ['categories_id' => 1, 'item_id' => '38'],

            // bundling             
            ['categories_id' => 3, 'item_id' => '36'],
            ['categories_id' => 3, 'item_id' => '37'],
            ['categories_id' => 3, 'item_id' => '38'],
            ['categories_id' => 3, 'item_id' => '43'],

            
        
        ];

        foreach ($categoryData as $category) {
            DB::table('master_items_categories')->updateOrInsert(
                [
                    'categories_id' => $category['categories_id'],
                    'item_id' => $category['item_id']
                ],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        // Update sellingprice_item in master_items table using customer_type_id = 1 (Retail) from master_items_details
        $details = DB::table('master_items_details')->where('customer_type_id', 1)->get();
        foreach ($details as $detail) {
            DB::table('master_items')
                ->where('item_id', $detail->item_id)
                ->update([
                    'sellingprice_item' => $detail->sell_price,
                    'updated_at' => now()
                ]);
        }
    }
}
