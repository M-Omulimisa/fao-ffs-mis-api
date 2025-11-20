<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductTagsTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get our test product (ID: 2)
        $product = Product::find(2);
        
        if (!$product) {
            echo "Test product (ID: 2) not found! Creating a new test product...\n";
            
            $product = Product::create([
                'name' => 'Test Smartphone for Tags',
                'description' => 'A sample smartphone product for testing the tags system',
                'price' => 599.99,
                'local_id' => 'TEST-PHONE-' . time(),
                'category' => 2, // Assuming category 2 exists
                'tags' => 'electronics,smartphone,android,mobile,communication,technology',
                'quantity' => 50,
                'min_quantity_alert' => 10
            ]);
            
            echo "Created new test product: {$product->name} (ID: {$product->id})\n";
        } else {
            // Update existing product with tags
            $product->update([
                'tags' => 'electronics,smartphone,android,mobile,communication,technology'
            ]);
            echo "Updated existing test product: {$product->name} (ID: {$product->id})\n";
        }
        
        // Create additional test products with different tag combinations
        $testProducts = [
            [
                'name' => 'Gaming Laptop',
                'description' => 'High-performance gaming laptop',
                'price' => 1299.99,
                'local_id' => 'LAPTOP-GAMING-' . time(),
                'tags' => 'electronics,laptop,gaming,computer,technology,performance'
            ],
            [
                'name' => 'Wireless Headphones',
                'description' => 'Premium wireless headphones with noise cancellation',
                'price' => 299.99,
                'local_id' => 'HEADPHONES-' . time(),
                'tags' => 'electronics,audio,wireless,headphones,music,technology'
            ],
            [
                'name' => 'Smart Watch',
                'description' => 'Feature-rich smartwatch with health tracking',
                'price' => 399.99,
                'local_id' => 'SMARTWATCH-' . time(),
                'tags' => 'electronics,wearable,smartwatch,health,fitness,technology'
            ]
        ];
        
        foreach ($testProducts as $productData) {
            // Check if product with same local_id exists
            $existing = Product::where('local_id', $productData['local_id'])->first();
            if (!$existing) {
                $productData['category'] = 2; // Default category
                $productData['quantity'] = 25;
                $productData['min_quantity_alert'] = 5;
                
                $newProduct = Product::create($productData);
                echo "Created test product: {$newProduct->name} (ID: {$newProduct->id})\n";
            }
        }
        
        echo "\nTag seeding completed!\n";
        echo "Products now have various tags for testing search and filtering functionality.\n";
    }
}
