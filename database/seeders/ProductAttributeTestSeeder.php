<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategorySpecification;
use App\Models\ProductHasSpecification;
use Illuminate\Support\Facades\DB;

class ProductAttributeTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Use the product we found (ID: 2, Category: 2)
        $productId = 2;
        $categoryId = 2;
        
        echo "Setting up test data for Product ID: {$productId}, Category ID: {$categoryId}" . PHP_EOL;
        
        // First, ensure the category has some specifications
        $this->ensureCategoryHasSpecifications($categoryId);
        
        // Get the specifications for this category
        $categorySpecifications = ProductCategorySpecification::where('product_category_id', $categoryId)->get();
        
        if ($categorySpecifications->isEmpty()) {
            echo "No specifications found for category {$categoryId}. Creating sample specifications..." . PHP_EOL;
            $this->createSampleSpecifications($categoryId);
            $categorySpecifications = ProductCategorySpecification::where('product_category_id', $categoryId)->get();
        }
        
        // Clear existing specifications for this product
        ProductHasSpecification::where('product_id', $productId)->delete();
        
        // Create specification values for the product
        foreach ($categorySpecifications as $categorySpecification) {
            $value = $this->generateSpecificationValue($categorySpecification->name);
            
            ProductHasSpecification::create([
                'product_id' => $productId,
                'name' => $categorySpecification->name,
                'value' => $value,
            ]);
            
            echo "Added specification: {$categorySpecification->name} = {$value}" . PHP_EOL;
        }
        
        echo "Test data setup completed successfully!" . PHP_EOL;
    }
    
    private function ensureCategoryHasSpecifications($categoryId)
    {
        $count = ProductCategorySpecification::where('product_category_id', $categoryId)->count();
        if ($count === 0) {
            $this->createSampleSpecifications($categoryId);
        }
    }
    
    private function createSampleSpecifications($categoryId)
    {
        $specifications = [
            ['name' => 'Brand', 'is_required' => 'Yes'],
            ['name' => 'Model Number', 'is_required' => 'Yes'],
            ['name' => 'Color', 'is_required' => 'No'],
            ['name' => 'Storage Capacity', 'is_required' => 'No'],
            ['name' => 'RAM', 'is_required' => 'No'],
            ['name' => 'Operating System', 'is_required' => 'No'],
        ];
        
        foreach ($specifications as $spec) {
            ProductCategorySpecification::create([
                'product_category_id' => $categoryId,
                'name' => $spec['name'],
                'is_required' => $spec['is_required'],
            ]);
        }
        
        echo "Created sample specifications for category {$categoryId}" . PHP_EOL;
    }
    
    private function generateSpecificationValue($specificationName)
    {
        switch (strtolower($specificationName)) {
            case 'brand':
                return 'Tecno';
            case 'model number':
                return 'POP 9';
            case 'color':
                return 'Midnight Black';
            case 'storage capacity':
                return '64GB';
            case 'ram':
                return '6GB';
            case 'operating system':
                return 'Android 14';
            default:
                return 'Sample Value';
        }
    }
}
