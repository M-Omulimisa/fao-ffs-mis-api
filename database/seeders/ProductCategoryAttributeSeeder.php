<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\ProductCategorySpecification;

class ProductCategoryAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get some existing categories to add attributes to
        $categories = ProductCategory::take(5)->get();
        
        if ($categories->isEmpty()) {
            $this->command->info('No product categories found. Please create some categories first.');
            return;
        }

        foreach ($categories as $category) {
            // Add some common attributes based on category type
            $this->addAttributesToCategory($category);
        }
    }

    private function addAttributesToCategory(ProductCategory $category)
    {
        $commonAttributes = [
            ['name' => 'Brand', 'is_required' => 'Yes'],
            ['name' => 'Color', 'is_required' => 'No'],
            ['name' => 'Size', 'is_required' => 'No'],
        ];

        // Add category-specific attributes based on category name
        $categoryName = strtolower($category->category);
        
        if (strpos($categoryName, 'electronics') !== false || strpos($categoryName, 'phone') !== false) {
            $specificAttributes = [
                ['name' => 'Model Number', 'is_required' => 'Yes'],
                ['name' => 'Storage Capacity', 'is_required' => 'No'],
                ['name' => 'Operating System', 'is_required' => 'No'],
            ];
        } elseif (strpos($categoryName, 'clothing') !== false || strpos($categoryName, 'fashion') !== false) {
            $specificAttributes = [
                ['name' => 'Material', 'is_required' => 'Yes'],
                ['name' => 'Care Instructions', 'is_required' => 'No'],
                ['name' => 'Fit Type', 'is_required' => 'No'],
            ];
        } elseif (strpos($categoryName, 'food') !== false || strpos($categoryName, 'grocery') !== false) {
            $specificAttributes = [
                ['name' => 'Expiry Date', 'is_required' => 'Yes'],
                ['name' => 'Weight', 'is_required' => 'Yes'],
                ['name' => 'Ingredients', 'is_required' => 'No'],
            ];
        } else {
            $specificAttributes = [
                ['name' => 'Warranty Period', 'is_required' => 'No'],
                ['name' => 'Country of Origin', 'is_required' => 'No'],
            ];
        }

        $allAttributes = array_merge($commonAttributes, $specificAttributes);

        foreach ($allAttributes as $attributeData) {
            ProductCategorySpecification::create([
                'product_category_id' => $category->id,
                'name' => $attributeData['name'],
                'is_required' => $attributeData['is_required'],
            ]);
        }

        $this->command->info("Added " . count($allAttributes) . " attributes to category: {$category->category}");
    }
}
