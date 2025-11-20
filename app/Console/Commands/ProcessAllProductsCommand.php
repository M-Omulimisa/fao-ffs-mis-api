<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAllProductsCommand extends Command
{
    protected $signature = 'products:process-all 
                            {--batch-size=100 : Number of products to process per batch}
                            {--start-id=1 : Starting product ID}
                            {--end-id= : Ending product ID (optional)}
                            {--skip-tags : Skip tag generation}
                            {--skip-attributes : Skip attribute population}
                            {--report : Generate processing report}';

    protected $description = 'Process all products: generate tags, populate attributes, and create reports';

    private $statistics = [
        'total_products' => 0,
        'processed_products' => 0,
        'generated_tags' => 0,
        'created_attributes' => 0,
        'processing_time' => 0,
        'errors' => []
    ];

    public function handle()
    {
        $startTime = microtime(true);
        
        $batchSize = $this->option('batch-size');
        $startId = $this->option('start-id');
        $endId = $this->option('end-id');
        $skipTags = $this->option('skip-tags');
        $skipAttributes = $this->option('skip-attributes');
        $generateReport = $this->option('report');
        
        $this->info("=== E-commerce Database Optimization Process ===");
        $this->info("Batch size: {$batchSize}");
        $this->info("Starting from ID: {$startId}");
        $this->info("Skip tags: " . ($skipTags ? 'Yes' : 'No'));
        $this->info("Skip attributes: " . ($skipAttributes ? 'Yes' : 'No'));
        
        // Build query conditions
        $whereConditions = ["id >= ?"];
        $params = [$startId];
        
        if ($endId) {
            $whereConditions[] = "id <= ?";
            $params[] = $endId;
            $this->info("Ending at ID: {$endId}");
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $totalCount = DB::selectOne("SELECT COUNT(*) as count FROM products WHERE {$whereClause}", $params)->count;
        $this->statistics['total_products'] = $totalCount;
        
        $this->info("Total products to process: {$totalCount}");
        $this->newLine();
        
        // Create main progress bar
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();
        
        // Process in batches
        $offset = 0;
        $processedCount = 0;
        
        while ($offset < $totalCount) {
            try {
                // Get batch of products
                $products = DB::select("
                    SELECT id, name, description, category, tags 
                    FROM products 
                    WHERE {$whereClause}
                    ORDER BY id 
                    LIMIT ? OFFSET ?
                ", array_merge($params, [$batchSize, $offset]));
                
                if (empty($products)) {
                    break;
                }
                
                // Process tags
                if (!$skipTags) {
                    $this->processBatchTags($products);
                }
                
                // Process attributes  
                if (!$skipAttributes) {
                    $this->processBatchAttributes($products);
                }
                
                $processedCount += count($products);
                $this->statistics['processed_products'] = $processedCount;
                
                // Update progress
                $progressBar->advance(count($products));
                
                // Memory management
                if ($processedCount % 200 === 0) {
                    gc_collect_cycles();
                }
                
                $offset += $batchSize;
                
            } catch (\Exception $e) {
                $this->statistics['errors'][] = "Batch offset {$offset}: " . $e->getMessage();
                $this->error("Error processing batch at offset {$offset}: " . $e->getMessage());
                $offset += $batchSize; // Skip problematic batch
            }
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $endTime = microtime(true);
        $this->statistics['processing_time'] = round($endTime - $startTime, 2);
        
        // Display results
        $this->displayResults();
        
        // Generate report if requested
        if ($generateReport) {
            $this->generateReport();
        }
        
        return 0;
    }
    
    private function processBatchTags($products)
    {
        $updates = [];
        
        foreach ($products as $product) {
            $tags = $this->generateProductTags($product);
            $tagsString = implode(',', $tags);
            
            $updates[] = [
                'id' => $product->id,
                'tags' => $tagsString
            ];
            
            $this->statistics['generated_tags'] += count($tags);
        }
        
        // Batch update tags
        foreach ($updates as $update) {
            DB::update("UPDATE products SET tags = ?, updated_at = NOW() WHERE id = ?", [
                $update['tags'],
                $update['id']
            ]);
        }
    }
    
    private function processBatchAttributes($products)
    {
        $allAttributes = [];
        
        foreach ($products as $product) {
            // Remove existing attributes
            DB::delete("DELETE FROM product_has_specifications WHERE product_id = ?", [$product->id]);
            
            // Generate new attributes
            $attributes = $this->generateProductAttributes($product);
            
            foreach ($attributes as $attribute) {
                $allAttributes[] = [
                    'product_id' => $product->id,
                    'name' => $attribute['name'],
                    'value' => $attribute['value'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            $this->statistics['created_attributes'] += count($attributes);
        }
        
        // Batch insert attributes
        if (!empty($allAttributes)) {
            foreach (array_chunk($allAttributes, 100) as $chunk) {
                DB::table('product_has_specifications')->insert($chunk);
            }
        }
    }
    
    private function generateProductTags($product)
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
        $brands = ['apple', 'samsung', 'huawei', 'xiaomi', 'oneplus', 'google', 'sony', 'lg', 'nokia', 'motorola', 'oppo', 'vivo', 'realme', 'tecno', 'infinix'];
        foreach ($brands as $brand) {
            if (strpos($text, $brand) !== false) {
                $tags[] = $brand;
                break;
            }
        }
        
        // Product type detection
        $productTypes = [
            'smartphone' => ['phone', 'smartphone', 'mobile'],
            'laptop' => ['laptop', 'computer'],
            'tablet' => ['tablet', 'ipad'],
            'headphones' => ['headphone', 'earphone', 'headset'],
            'smartwatch' => ['watch', 'smartwatch'],
            'power-bank' => ['power', 'bank'],
            'charger' => ['charger', 'adapter'],
            'case' => ['case', 'cover'],
            'bluetooth' => ['bluetooth'],
            'wireless' => ['wireless']
        ];
        
        foreach ($productTypes as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $tags[] = $type;
                    break 2;
                }
            }
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
        if (strpos($text, '5g') !== false) $tags[] = '5g';
        if (strpos($text, '4g') !== false) $tags[] = '4g';
        if (strpos($text, 'gaming') !== false) $tags[] = 'gaming';
        if (strpos($text, 'fast') !== false && strpos($text, 'charg') !== false) $tags[] = 'fast-charging';
        
        // Ensure minimum 5 tags
        $genericTags = ['electronics', 'technology', 'gadget', 'device', 'digital', 'mobile'];
        while (count($tags) < 5) {
            $randomTag = $genericTags[array_rand($genericTags)];
            if (!in_array($randomTag, $tags)) {
                $tags[] = $randomTag;
            }
        }
        
        return array_unique(array_slice($tags, 0, 8));
    }
    
    private function generateProductAttributes($product)
    {
        $attributes = [];
        $name = strtolower($product->name ?? '');
        $description = strtolower($product->description ?? '');
        $text = $name . ' ' . $description;
        
        // Get category attributes
        $categoryAttributes = DB::select("
            SELECT name, is_required 
            FROM product_category_specifications 
            WHERE product_category_id = ?
        ", [$product->category]);
        
        // Generate category-specific attributes
        foreach ($categoryAttributes as $categoryAttr) {
            $value = $this->extractAttributeValue($categoryAttr->name, $text, $product);
            
            if ($value || $categoryAttr->is_required === 'Yes') {
                $attributes[] = [
                    'name' => $categoryAttr->name,
                    'value' => $value ?: 'Not specified'
                ];
            }
        }
        
        // Add common attributes
        $commonAttributes = [
            ['name' => 'Warranty', 'value' => '1 Year Manufacturer Warranty'],
            ['name' => 'Condition', 'value' => strpos($text, 'refurbished') !== false ? 'Refurbished' : 'New'],
            ['name' => 'Availability', 'value' => 'In Stock']
        ];
        
        foreach ($commonAttributes as $attr) {
            $exists = false;
            foreach ($attributes as $existing) {
                if ($existing['name'] === $attr['name']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $attributes[] = $attr;
            }
        }
        
        return $attributes;
    }
    
    private function extractAttributeValue($attributeName, $text, $product)
    {
        $attrLower = strtolower($attributeName);
        
        // Brand extraction
        if (strpos($attrLower, 'brand') !== false) {
            $brands = ['apple', 'samsung', 'huawei', 'xiaomi', 'oneplus', 'google', 'sony', 'lg', 'nokia', 'motorola', 'oppo', 'vivo', 'realme', 'tecno', 'infinix'];
            foreach ($brands as $brand) {
                if (strpos($text, $brand) !== false) {
                    return ucfirst($brand);
                }
            }
        }
        
        // Model extraction
        if (strpos($attrLower, 'model') !== false) {
            $nameParts = explode(' ', $product->name);
            if (count($nameParts) > 1) {
                return $nameParts[1];
            }
        }
        
        // Screen Size
        if (strpos($attrLower, 'screen') !== false) {
            if (preg_match('/(\d+\.?\d*)"/', $text, $matches)) {
                return $matches[1] . '"';
            }
        }
        
        // Storage/RAM
        if (strpos($attrLower, 'storage') !== false || strpos($attrLower, 'ram') !== false) {
            if (preg_match('/(\d+)\s*gb/i', $text, $matches)) {
                return $matches[1] . 'GB';
            }
        }
        
        // Battery
        if (strpos($attrLower, 'battery') !== false) {
            if (preg_match('/(\d+)\s*mah/i', $text, $matches)) {
                return $matches[1] . 'mAh';
            }
        }
        
        // Camera
        if (strpos($attrLower, 'camera') !== false) {
            if (preg_match('/(\d+)\s*mp/i', $text, $matches)) {
                return $matches[1] . 'MP';
            }
        }
        
        // Operating System
        if (strpos($attrLower, 'operating') !== false) {
            if (strpos($text, 'ios') !== false) return 'iOS';
            if (strpos($text, 'windows') !== false) return 'Windows';
            return 'Android';
        }
        
        return null;
    }
    
    private function displayResults()
    {
        $this->info("=== Processing Complete ===");
        $this->table(['Metric', 'Value'], [
            ['Total Products', $this->statistics['total_products']],
            ['Processed Products', $this->statistics['processed_products']],
            ['Generated Tags', $this->statistics['generated_tags']],
            ['Created Attributes', $this->statistics['created_attributes']],
            ['Processing Time', $this->statistics['processing_time'] . 's'],
            ['Errors', count($this->statistics['errors'])],
            ['Success Rate', round(($this->statistics['processed_products'] / $this->statistics['total_products']) * 100, 2) . '%']
        ]);
        
        if (!empty($this->statistics['errors'])) {
            $this->warn("Errors encountered:");
            foreach ($this->statistics['errors'] as $error) {
                $this->line("- " . $error);
            }
        }
    }
    
    private function generateReport()
    {
        $reportData = [
            'timestamp' => now(),
            'statistics' => $this->statistics,
            'system_info' => [
                'memory_usage' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'execution_time' => $this->statistics['processing_time']
            ]
        ];
        
        $reportFile = storage_path('logs/processing_report_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
        
        $this->info("Processing report saved to: {$reportFile}");
    }
}
