<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ProductCategory;
use App\Models\ProductCategorySpecification;

class OptimizeCategoriesCommand extends Command
{
    protected $signature = 'categories:optimize 
                            {--dry-run : Run without making changes}
                            {--clear-attributes : Clear existing category attributes}';

    protected $description = 'Optimize category structure and create comprehensive attributes';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $clearAttributes = $this->option('clear-attributes');
        
        $this->info("Starting category optimization...");
        $this->info("Dry run: " . ($dryRun ? 'Yes' : 'No'));
        
        // Step 1: Analyze current categories
        $this->analyzeCurrentCategories();
        
        // Step 2: Clear attributes if requested
        if ($clearAttributes && !$dryRun) {
            $this->clearCategoryAttributes();
        }
        
        // Step 3: Create optimized category structure
        $this->createOptimizedCategories($dryRun);
        
        // Step 4: Generate category attributes
        $this->generateCategoryAttributes($dryRun);
        
        $this->info("Category optimization completed!");
        
        return 0;
    }
    
    private function analyzeCurrentCategories()
    {
        $this->info("\n=== Current Category Analysis ===");
        
        $categories = ProductCategory::withCount('products')->get();
        
        $table = [];
        foreach ($categories as $category) {
            $table[] = [
                'ID' => $category->id,
                'Category' => $category->category,
                'Parent' => $category->parent_category ?? 'Root',
                'Products' => $category->products_count,
                'Status' => $category->products_count > 0 ? 'Active' : 'Empty'
            ];
        }
        
        $this->table(['ID', 'Category', 'Parent', 'Products', 'Status'], $table);
    }
    
    private function clearCategoryAttributes()
    {
        $this->info("Clearing existing category attributes...");
        DB::table('product_category_specifications')->delete();
        $this->info("Category attributes cleared.");
    }
    
    private function createOptimizedCategories($dryRun)
    {
        $this->info("\n=== Creating Optimized Category Structure ===");
        
        $optimizedCategories = [
            // Main Category 1: Mobile & Communication
            ['id' => 1, 'category' => 'Mobile & Communication', 'parent_id' => null, 'description' => 'Smartphones, tablets, and communication devices'],
            ['id' => 2, 'category' => 'Smartphones', 'parent_id' => 1, 'description' => 'All types of smartphones and mobile phones'],
            ['id' => 3, 'category' => 'Tablets & E-readers', 'parent_id' => 1, 'description' => 'Tablets, iPads, and electronic reading devices'],
            ['id' => 4, 'category' => 'Mobile Accessories', 'parent_id' => 1, 'description' => 'Cases, chargers, and mobile accessories'],
            ['id' => 5, 'category' => 'Wearable Tech', 'parent_id' => 1, 'description' => 'Smartwatches, fitness trackers, and wearables'],
            ['id' => 6, 'category' => 'Communication Devices', 'parent_id' => 1, 'description' => 'Walkie-talkies, radios, and communication tools'],
            
            // Main Category 2: Computing & Electronics
            ['id' => 7, 'category' => 'Computing & Electronics', 'parent_id' => null, 'description' => 'Computers, laptops, and electronic devices'],
            ['id' => 8, 'category' => 'Laptops & Computers', 'parent_id' => 7, 'description' => 'Laptops, desktops, and computing devices'],
            ['id' => 9, 'category' => 'Computer Accessories', 'parent_id' => 7, 'description' => 'Keyboards, mice, and computer peripherals'],
            ['id' => 10, 'category' => 'Storage & Memory', 'parent_id' => 7, 'description' => 'Hard drives, SSDs, and memory cards'],
            ['id' => 11, 'category' => 'Networking Equipment', 'parent_id' => 7, 'description' => 'Routers, modems, and networking devices'],
            ['id' => 12, 'category' => 'Gaming Devices', 'parent_id' => 7, 'description' => 'Gaming laptops, consoles, and gaming accessories'],
            
            // Main Category 3: Audio, Visual & Entertainment
            ['id' => 13, 'category' => 'Audio, Visual & Entertainment', 'parent_id' => null, 'description' => 'Audio, video, and entertainment devices'],
            ['id' => 14, 'category' => 'Audio Equipment', 'parent_id' => 13, 'description' => 'Headphones, speakers, and audio devices'],
            ['id' => 15, 'category' => 'Cameras & Photography', 'parent_id' => 13, 'description' => 'Cameras, lenses, and photography equipment'],
            ['id' => 16, 'category' => 'Home Entertainment', 'parent_id' => 13, 'description' => 'TVs, projectors, and home theater systems'],
            ['id' => 17, 'category' => 'Streaming Devices', 'parent_id' => 13, 'description' => 'Media players, streaming sticks, and set-top boxes'],
            ['id' => 18, 'category' => 'Professional AV Equipment', 'parent_id' => 13, 'description' => 'Professional audio and video equipment']
        ];
        
        foreach ($optimizedCategories as $categoryData) {
            if (!$dryRun) {
                DB::table('product_categories')
                    ->updateOrInsert(
                        ['id' => $categoryData['id']], 
                        [
                            'category' => $categoryData['category'],
                            'parent_id' => $categoryData['parent_id'],
                            'is_parent' => $categoryData['parent_id'] === null ? 'Yes' : 'No',
                            'updated_at' => now()
                        ]
                    );
            }
            
            $this->line("Category {$categoryData['id']}: {$categoryData['category']} (Parent: {$categoryData['parent_id']})");
        }
    }
    
    private function generateCategoryAttributes($dryRun)
    {
        $this->info("\n=== Generating Category Attributes ===");
        
        $categoryAttributes = [
            // Smartphones (ID: 2)
            2 => [
                ['name' => 'Brand', 'is_required' => 'Yes'],
                ['name' => 'Model', 'is_required' => 'Yes'],
                ['name' => 'Operating System', 'is_required' => 'Yes'],
                ['name' => 'Screen Size', 'is_required' => 'Yes'],
                ['name' => 'Storage Capacity', 'is_required' => 'No'],
                ['name' => 'RAM', 'is_required' => 'No'],
                ['name' => 'Camera Resolution', 'is_required' => 'No'],
                ['name' => 'Battery Capacity', 'is_required' => 'No'],
                ['name' => 'Color', 'is_required' => 'No'],
                ['name' => '5G Support', 'is_required' => 'No']
            ],
            
            // Tablets & E-readers (ID: 3)
            3 => [
                ['name' => 'Brand', 'is_required' => 'Yes'],
                ['name' => 'Model', 'is_required' => 'Yes'],
                ['name' => 'Screen Size', 'is_required' => 'Yes'],
                ['name' => 'Operating System', 'is_required' => 'Yes'],
                ['name' => 'Storage Capacity', 'is_required' => 'No'],
                ['name' => 'Connectivity', 'is_required' => 'No'],
                ['name' => 'Weight', 'is_required' => 'No'],
                ['name' => 'Battery Life', 'is_required' => 'No'],
                ['name' => 'Stylus Support', 'is_required' => 'No'],
                ['name' => 'Resolution', 'is_required' => 'No']
            ],
            
            // Mobile Accessories (ID: 4)
            4 => [
                ['name' => 'Product Type', 'is_required' => 'Yes'],
                ['name' => 'Compatible Brand', 'is_required' => 'Yes'],
                ['name' => 'Compatible Model', 'is_required' => 'Yes'],
                ['name' => 'Material', 'is_required' => 'Yes'],
                ['name' => 'Color', 'is_required' => 'No'],
                ['name' => 'Features', 'is_required' => 'No'],
                ['name' => 'Warranty', 'is_required' => 'No'],
                ['name' => 'Dimensions', 'is_required' => 'No'],
                ['name' => 'Weight', 'is_required' => 'No'],
                ['name' => 'Price Range', 'is_required' => 'No']
            ],
            
            // Laptops & Computers (ID: 8)
            8 => [
                ['name' => 'Brand', 'is_required' => 'Yes'],
                ['name' => 'Processor', 'is_required' => 'Yes'],
                ['name' => 'RAM', 'is_required' => 'Yes'],
                ['name' => 'Storage Type', 'is_required' => 'Yes'],
                ['name' => 'Storage Capacity', 'is_required' => 'No'],
                ['name' => 'Graphics Card', 'is_required' => 'No'],
                ['name' => 'Screen Size', 'is_required' => 'No'],
                ['name' => 'Operating System', 'is_required' => 'No'],
                ['name' => 'Weight', 'is_required' => 'No'],
                ['name' => 'Battery Life', 'is_required' => 'No']
            ],
            
            // Audio Equipment (ID: 14)
            14 => [
                ['name' => 'Product Type', 'is_required' => 'Yes'],
                ['name' => 'Brand', 'is_required' => 'Yes'],
                ['name' => 'Connectivity', 'is_required' => 'Yes'],
                ['name' => 'Driver Size', 'is_required' => 'Yes'],
                ['name' => 'Frequency Response', 'is_required' => 'No'],
                ['name' => 'Impedance', 'is_required' => 'No'],
                ['name' => 'Noise Cancellation', 'is_required' => 'No'],
                ['name' => 'Battery Life', 'is_required' => 'No'],
                ['name' => 'Color', 'is_required' => 'No'],
                ['name' => 'Warranty', 'is_required' => 'No']
            ]
        ];
        
        $totalAttributes = 0;
        
        foreach ($categoryAttributes as $categoryId => $attributes) {
            $this->info("Generating attributes for category ID: {$categoryId}");
            
            foreach ($attributes as $attribute) {
                if (!$dryRun) {
                    DB::table('product_category_specifications')->insert([
                        'product_category_id' => $categoryId,
                        'name' => $attribute['name'],
                        'is_required' => $attribute['is_required'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                $totalAttributes++;
                $this->line("  - {$attribute['name']} (" . ($attribute['is_required'] === 'Yes' ? 'Required' : 'Optional') . ")");
            }
        }
        
        $this->info("Total attributes created: {$totalAttributes}");
    }
}
