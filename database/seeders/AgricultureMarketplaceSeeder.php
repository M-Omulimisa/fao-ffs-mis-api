<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AgricultureMarketplaceSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🌾 Starting Agriculture & Livestock Marketplace Seeder...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Product::truncate();
        ProductCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $categories = $this->createCategories();
        $this->createProducts($categories);
        
        $this->command->info('✅ Seeding completed!');
    }
    
    private function createCategories()
    {
        $categories = [];
        
        // 1. LIVESTOCK
        $livestock = ProductCategory::create([
            'category' => 'Livestock',
            'icon' => 'bi-heart',
            'is_parent' => 'Yes',
            'parent_id' => null,
            'show_in_categories' => 'Yes',
            'show_in_banner' => 'Yes',
            'image' => 'blank.png',
        ]);
        
        $categories['livestock'] = ['main' => $livestock, 'subs' => []];
        
        $livestockSubs = [
            ['name' => 'Cattle & Dairy', 'icon' => 'bi-badge-tm'],
            ['name' => 'Poultry & Birds', 'icon' => 'bi-egg'],
            ['name' => 'Goats & Sheep', 'icon' => 'bi-flower1'],
            ['name' => 'Pigs & Swine', 'icon' => 'bi-piggy-bank'],
        ];
        
        foreach ($livestockSubs as $sub) {
            $categories['livestock']['subs'][] = ProductCategory::create([
                'category' => $sub['name'],
                'icon' => $sub['icon'],
                'is_parent' => 'No',
                'parent_id' => $livestock->id,
                'show_in_categories' => 'Yes',
                'show_in_banner' => 'Yes',
                'image' => 'blank.png',
            ]);
        }
        
        // 2. AGRICULTURE
        $agriculture = ProductCategory::create([
            'category' => 'Agriculture',
            'icon' => 'bi-flower2',
            'is_parent' => 'Yes',
            'parent_id' => null,
            'show_in_categories' => 'Yes',
            'show_in_banner' => 'Yes',
            'image' => 'blank.png',
        ]);
        
        $categories['agriculture'] = ['main' => $agriculture, 'subs' => []];
        
        $agricultureSubs = [
            ['name' => 'Crops & Seeds', 'icon' => 'bi-flower3'],
            ['name' => 'Farm Equipment', 'icon' => 'bi-tools'],
            ['name' => 'Fertilizers & Chemicals', 'icon' => 'bi-droplet'],
            ['name' => 'Farm Services', 'icon' => 'bi-truck'],
        ];
        
        foreach ($agricultureSubs as $sub) {
            $categories['agriculture']['subs'][] = ProductCategory::create([
                'category' => $sub['name'],
                'icon' => $sub['icon'],
                'is_parent' => 'No',
                'parent_id' => $agriculture->id,
                'show_in_categories' => 'Yes',
                'show_in_banner' => 'Yes',
                'image' => 'blank.png',
            ]);
        }
        
        return $categories;
    }
