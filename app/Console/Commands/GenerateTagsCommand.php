<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTagsCommand extends Command
{
    protected $signature = 'products:generate-tags 
                            {--batch-size=50 : Number of products to process per batch}
                            {--limit=100 : Maximum number of products to process}
                            {--start-id=1 : Starting product ID}';

    protected $description = 'Generate tags for products using raw SQL for performance';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        $limit = $this->option('limit');
        $startId = $this->option('start-id');
        
        $this->info("Starting tag generation...");
        $this->info("Batch size: {$batchSize}");
        $this->info("Limit: {$limit}");
        $this->info("Starting from ID: {$startId}");
        
        // Get products using raw SQL to avoid memory issues
        $products = DB::select("
            SELECT id, name, description, category 
            FROM products 
            WHERE id >= ? 
            ORDER BY id 
            LIMIT ?
        ", [$startId, $limit]);
        
        $this->info("Processing " . count($products) . " products...");
        
        $progressBar = $this->output->createProgressBar(count($products));
        $progressBar->start();
        
        $processedCount = 0;
        $batch = [];
        
        foreach ($products as $product) {
            $tags = $this->generateTagsForProduct($product);
            
            $batch[] = [
                'id' => $product->id,
                'tags' => implode(',', $tags)
            ];
            
            if (count($batch) >= $batchSize) {
                $this->processBatch($batch);
                $batch = [];
                gc_collect_cycles(); // Memory management
            }
            
            $processedCount++;
            $progressBar->advance();
        }
        
        // Process remaining batch
        if (!empty($batch)) {
            $this->processBatch($batch);
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("Tag generation completed!");
        $this->info("Processed: {$processedCount} products");
        
        return 0;
    }
    
    private function generateTagsForProduct($product)
    {
        $tags = [];
        $name = strtolower($product->name ?? '');
        $description = strtolower($product->description ?? '');
        $text = $name . ' ' . $description;
        
        // Get category name
        $category = DB::selectOne("SELECT category FROM product_categories WHERE id = ?", [$product->category]);
        if ($category) {
            $categoryName = strtolower($category->category);
            $tags[] = str_replace([' ', '&'], ['-', 'and'], trim($categoryName));
        }
        
        // Brand detection
        $brands = ['apple', 'samsung', 'huawei', 'xiaomi', 'oneplus', 'google', 'sony', 'lg', 'nokia', 'motorola', 'oppo', 'vivo', 'realme'];
        foreach ($brands as $brand) {
            if (strpos($text, $brand) !== false) {
                $tags[] = $brand;
                break;
            }
        }
        
        // Product type detection
        if (strpos($text, 'phone') !== false || strpos($text, 'smartphone') !== false) {
            $tags[] = 'smartphone';
        }
        if (strpos($text, 'laptop') !== false || strpos($text, 'computer') !== false) {
            $tags[] = 'laptop';
        }
        if (strpos($text, 'tablet') !== false || strpos($text, 'ipad') !== false) {
            $tags[] = 'tablet';
        }
        if (strpos($text, 'headphone') !== false || strpos($text, 'earphone') !== false) {
            $tags[] = 'headphones';
        }
        if (strpos($text, 'watch') !== false) {
            $tags[] = 'smartwatch';
        }
        if (strpos($text, 'case') !== false || strpos($text, 'cover') !== false) {
            $tags[] = 'case';
        }
        if (strpos($text, 'charger') !== false || strpos($text, 'cable') !== false) {
            $tags[] = 'charger';
        }
        if (strpos($text, 'power') !== false && strpos($text, 'bank') !== false) {
            $tags[] = 'power-bank';
        }
        if (strpos($text, 'wireless') !== false) {
            $tags[] = 'wireless';
        }
        if (strpos($text, 'bluetooth') !== false) {
            $tags[] = 'bluetooth';
        }
        
        // Color detection
        $colors = ['black', 'white', 'red', 'blue', 'green', 'yellow', 'pink', 'purple', 'gray', 'silver', 'gold'];
        foreach ($colors as $color) {
            if (strpos($text, $color) !== false) {
                $tags[] = $color;
                break;
            }
        }
        
        // Technology features
        if (strpos($text, '5g') !== false) {
            $tags[] = '5g';
        }
        if (strpos($text, '4g') !== false) {
            $tags[] = '4g';
        }
        if (strpos($text, 'fast') !== false && strpos($text, 'charg') !== false) {
            $tags[] = 'fast-charging';
        }
        if (strpos($text, 'gaming') !== false) {
            $tags[] = 'gaming';
        }
        if (strpos($text, 'waterproof') !== false || strpos($text, 'water') !== false) {
            $tags[] = 'waterproof';
        }
        
        // Ensure minimum 5 tags
        $genericTags = ['electronics', 'technology', 'gadget', 'device', 'digital', 'mobile', 'portable'];
        while (count($tags) < 5) {
            $randomTag = $genericTags[array_rand($genericTags)];
            if (!in_array($randomTag, $tags)) {
                $tags[] = $randomTag;
            }
        }
        
        return array_unique(array_slice($tags, 0, 8)); // Max 8 tags
    }
    
    private function processBatch($batch)
    {
        foreach ($batch as $item) {
            DB::update("UPDATE products SET tags = ?, updated_at = NOW() WHERE id = ?", [
                $item['tags'],
                $item['id']
            ]);
        }
        
        $this->line("Processed batch of " . count($batch) . " products");
    }
}
