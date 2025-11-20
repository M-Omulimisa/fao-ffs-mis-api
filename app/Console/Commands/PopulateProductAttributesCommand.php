<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateProductAttributesCommand extends Command
{
    protected $signature = 'products:populate-attributes 
                            {--batch-size=50 : Number of products to process per batch}
                            {--limit=100 : Maximum number of products to process}
                            {--category= : Specific category ID to process}
                            {--start-id=1 : Starting product ID}';

    protected $description = 'Populate product attributes based on category attributes and product analysis';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        $limit = $this->option('limit');
        $categoryId = $this->option('category');
        $startId = $this->option('start-id');
        
        $this->info("Starting product attribute population...");
        $this->info("Batch size: {$batchSize}");
        $this->info("Limit: {$limit}");
        
        // Build query conditions
        $whereConditions = ["p.id >= ?"];
        $params = [$startId];
        
        if ($categoryId) {
            $whereConditions[] = "p.category = ?";
            $params[] = $categoryId;
            $this->info("Category filter: {$categoryId}");
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get products with their category information
        $products = DB::select("
            SELECT p.id, p.name, p.description, p.category, p.tags, c.category as category_name
            FROM products p
            LEFT JOIN product_categories c ON p.category = c.id
            WHERE {$whereClause}
            ORDER BY p.id 
            LIMIT ?
        ", array_merge($params, [$limit]));
        
        $this->info("Processing " . count($products) . " products...");
        
        $progressBar = $this->output->createProgressBar(count($products));
        $progressBar->start();
        
        $processedCount = 0;
        $batch = [];
        
        foreach ($products as $product) {
            $attributes = $this->generateProductAttributes($product);
            
            foreach ($attributes as $attribute) {
                $batch[] = [
                    'product_id' => $product->id,
                    'name' => $attribute['name'],
                    'value' => $attribute['value'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            
            if (count($batch) >= $batchSize) {
                $this->processBatch($batch);
                $batch = [];
                gc_collect_cycles();
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
        
        $this->info("Attribute population completed!");
        $this->info("Processed: {$processedCount} products");
        
        return 0;
    }
    
    private function generateProductAttributes($product)
    {
        $attributes = [];
        $name = strtolower($product->name ?? '');
        $description = strtolower($product->description ?? '');
        $tags = strtolower($product->tags ?? '');
        $text = $name . ' ' . $description . ' ' . $tags;
        
        // Get category attributes for this product's category
        $categoryAttributes = DB::select("
            SELECT name, is_required 
            FROM product_category_specifications 
            WHERE product_category_id = ?
        ", [$product->category]);
        
        // Generate attributes based on category requirements
        foreach ($categoryAttributes as $categoryAttr) {
            $value = $this->extractAttributeValue($categoryAttr->name, $text, $product);
            
            if ($value || $categoryAttr->is_required === 'Yes') {
                $attributes[] = [
                    'name' => $categoryAttr->name,
                    'value' => $value ?: 'Not specified'
                ];
            }
        }
        
        // Add some common attributes if not already covered
        $commonAttributes = $this->generateCommonAttributes($text, $product);
        foreach ($commonAttributes as $attr) {
            // Check if attribute name already exists
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
            // Extract model from product name
            $nameParts = explode(' ', $product->name);
            for ($i = 1; $i < count($nameParts) && $i < 4; $i++) {
                if (preg_match('/^[A-Z0-9]+/', $nameParts[$i])) {
                    return implode(' ', array_slice($nameParts, 1, $i));
                }
            }
            return 'Standard Model';
        }
        
        // Operating System
        if (strpos($attrLower, 'operating') !== false || strpos($attrLower, 'os') !== false) {
            if (strpos($text, 'android') !== false) return 'Android';
            if (strpos($text, 'ios') !== false) return 'iOS';
            if (strpos($text, 'windows') !== false) return 'Windows';
            return 'Android';
        }
        
        // Screen Size extraction
        if (strpos($attrLower, 'screen') !== false || strpos($attrLower, 'display') !== false) {
            if (preg_match('/(\d+\.?\d*)"/', $text, $matches)) {
                return $matches[1] . '"';
            }
            if (preg_match('/(\d+\.?\d*)\s*inch/', $text, $matches)) {
                return $matches[1] . '"';
            }
        }
        
        // Storage/Memory extraction
        if (strpos($attrLower, 'storage') !== false || strpos($attrLower, 'memory') !== false) {
            if (preg_match('/(\d+)\s*(gb|tb)/i', $text, $matches)) {
                return $matches[1] . strtoupper($matches[2]);
            }
        }
        
        // RAM extraction
        if (strpos($attrLower, 'ram') !== false) {
            if (preg_match('/(\d+)\s*gb.*ram/i', $text, $matches)) {
                return $matches[1] . 'GB';
            }
            if (preg_match('/(\d+)\s*gb/i', $text, $matches)) {
                return $matches[1] . 'GB';
            }
        }
        
        // Battery extraction
        if (strpos($attrLower, 'battery') !== false) {
            if (preg_match('/(\d+)\s*mah/i', $text, $matches)) {
                return $matches[1] . 'mAh';
            }
        }
        
        // Camera extraction
        if (strpos($attrLower, 'camera') !== false) {
            if (preg_match('/(\d+)\s*mp/i', $text, $matches)) {
                return $matches[1] . 'MP';
            }
        }
        
        // Color extraction
        if (strpos($attrLower, 'color') !== false || strpos($attrLower, 'colour') !== false) {
            $colors = ['black', 'white', 'red', 'blue', 'green', 'yellow', 'pink', 'purple', 'gray', 'grey', 'silver', 'gold', 'rose', 'space'];
            foreach ($colors as $color) {
                if (strpos($text, $color) !== false) {
                    return ucfirst($color);
                }
            }
        }
        
        // Connectivity
        if (strpos($attrLower, 'connectivity') !== false) {
            $connections = [];
            if (strpos($text, '5g') !== false) $connections[] = '5G';
            if (strpos($text, '4g') !== false) $connections[] = '4G';
            if (strpos($text, 'wifi') !== false) $connections[] = 'WiFi';
            if (strpos($text, 'bluetooth') !== false) $connections[] = 'Bluetooth';
            return !empty($connections) ? implode(', ', $connections) : 'Standard';
        }
        
        // Product Type
        if (strpos($attrLower, 'type') !== false || strpos($attrLower, 'product') !== false) {
            if (strpos($text, 'smartphone') !== false || strpos($text, 'phone') !== false) return 'Smartphone';
            if (strpos($text, 'laptop') !== false) return 'Laptop';
            if (strpos($text, 'tablet') !== false) return 'Tablet';
            if (strpos($text, 'headphone') !== false) return 'Headphones';
            if (strpos($text, 'charger') !== false) return 'Charger';
            if (strpos($text, 'case') !== false) return 'Protective Case';
            if (strpos($text, 'power bank') !== false) return 'Power Bank';
        }
        
        return null;
    }
    
    private function generateCommonAttributes($text, $product)
    {
        $attributes = [];
        
        // Add warranty information
        $attributes[] = [
            'name' => 'Warranty',
            'value' => '1 Year Manufacturer Warranty'
        ];
        
        // Add condition
        $attributes[] = [
            'name' => 'Condition',
            'value' => strpos($text, 'refurbished') !== false ? 'Refurbished' : 'New'
        ];
        
        // Add price range based on category
        $priceRange = 'Mid-range';
        if (strpos($text, 'premium') !== false || strpos($text, 'pro') !== false) {
            $priceRange = 'Premium';
        } elseif (strpos($text, 'budget') !== false || strpos($text, 'basic') !== false) {
            $priceRange = 'Budget';
        }
        
        $attributes[] = [
            'name' => 'Price Range',
            'value' => $priceRange
        ];
        
        return $attributes;
    }
    
    private function processBatch($batch)
    {
        // Remove existing attributes for these products first to avoid duplicates
        $productIds = array_unique(array_column($batch, 'product_id'));
        
        if (!empty($productIds)) {
            foreach ($productIds as $productId) {
                DB::delete("DELETE FROM product_has_specifications WHERE product_id = ?", [$productId]);
            }
        }
        
        // Insert new attributes
        foreach (array_chunk($batch, 25) as $chunk) {
            DB::table('product_has_specifications')->insert($chunk);
        }
        
        $this->line("Processed batch: " . count($productIds) . " products, " . count($batch) . " attributes");
    }
}
