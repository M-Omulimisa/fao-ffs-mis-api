<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductCategory;

class AnalyzeProductsCommand extends Command
{
    protected $signature = 'products:analyze 
                            {--batch-size=100 : Number of products to process per batch}
                            {--start-id=1 : Starting product ID}
                            {--dry-run : Run without making changes}';

    protected $description = 'Analyze products and generate intelligent tags and categorization';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        $startId = $this->option('start-id');
        $dryRun = $this->option('dry-run');
        
        $this->info("Starting product analysis...");
        $this->info("Batch size: {$batchSize}");
        $this->info("Starting from ID: {$startId}");
        $this->info("Dry run: " . ($dryRun ? 'Yes' : 'No'));
        
        // Get total products count
        $totalProducts = Product::where('id', '>=', $startId)->count();
        $this->info("Total products to process: {$totalProducts}");
        
        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->start();
        
        $processedCount = 0;
        $errorCount = 0;
        
        // Process in batches
        Product::where('id', '>=', $startId)
            ->orderBy('id')
            ->chunk($batchSize, function ($products) use (&$processedCount, &$errorCount, $dryRun, $progressBar) {
                
                foreach ($products as $product) {
                    try {
                        $tags = $this->generateProductTags($product);
                        $suggestedCategory = $this->suggestCategory($product);
                        
                        if (!$dryRun) {
                            // Update product with generated tags
                            DB::table('products')
                                ->where('id', $product->id)
                                ->update([
                                    'tags' => implode(',', $tags),
                                    'updated_at' => now()
                                ]);
                        }
                        
                        // Log analysis
                        $this->logProductAnalysis($product, $tags, $suggestedCategory, $dryRun);
                        
                        $processedCount++;
                        
                    } catch (\Exception $e) {
                        $this->error("Error processing product {$product->id}: " . $e->getMessage());
                        $errorCount++;
                    }
                    
                    $progressBar->advance();
                    
                    // Memory management
                    if ($processedCount % 50 === 0) {
                        gc_collect_cycles();
                    }
                }
            });
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("Analysis completed!");
        $this->info("Processed: {$processedCount} products");
        $this->info("Errors: {$errorCount}");
        
        return 0;
    }
    
    private function generateProductTags($product)
    {
        $tags = [];
        $name = strtolower($product->name ?? '');
        $description = strtolower($product->description ?? '');
        $text = $name . ' ' . $description;
        
        // Category-based tags
        $category = ProductCategory::find($product->category);
        if ($category) {
            $categoryName = strtolower($category->category ?? '');
            $tags[] = str_replace(' ', '-', trim($categoryName));
        }
        
        // Brand detection
        $brands = ['apple', 'samsung', 'huawei', 'xiaomi', 'oneplus', 'google', 'sony', 'lg', 'nokia', 'motorola'];
        foreach ($brands as $brand) {
            if (strpos($text, $brand) !== false) {
                $tags[] = $brand;
                break;
            }
        }
        
        // Product type detection
        $types = [
            'smartphone' => ['phone', 'smartphone', 'mobile'],
            'laptop' => ['laptop', 'notebook', 'computer'],
            'tablet' => ['tablet', 'ipad'],
            'headphones' => ['headphone', 'earphone', 'headset', 'earbuds'],
            'watch' => ['watch', 'smartwatch'],
            'camera' => ['camera', 'webcam'],
            'speaker' => ['speaker', 'bluetooth'],
            'charger' => ['charger', 'adapter', 'cable'],
            'case' => ['case', 'cover', 'protector'],
            'accessory' => ['accessory', 'stand', 'holder']
        ];
        
        foreach ($types as $type => $keywords) {
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
        $features = [
            'wireless' => ['wireless', 'bluetooth', 'wifi'],
            '4g' => ['4g', 'lte'],
            '5g' => ['5g'],
            'fast-charging' => ['fast', 'quick', 'rapid'],
            'waterproof' => ['waterproof', 'water', 'resistant'],
            'gaming' => ['gaming', 'game'],
            'professional' => ['pro', 'professional'],
            'premium' => ['premium', 'luxury', 'high-end']
        ];
        
        foreach ($features as $feature => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $tags[] = $feature;
                    break 2;
                }
            }
        }
        
        // Ensure minimum 5 tags
        $genericTags = ['electronics', 'technology', 'gadget', 'device', 'digital'];
        while (count($tags) < 5) {
            $randomTag = $genericTags[array_rand($genericTags)];
            if (!in_array($randomTag, $tags)) {
                $tags[] = $randomTag;
            }
        }
        
        return array_unique(array_slice($tags, 0, 8)); // Max 8 tags
    }
    
    private function suggestCategory($product)
    {
        $name = strtolower($product->name ?? '');
        $description = strtolower($product->description ?? '');
        $text = $name . ' ' . $description;
        
        // Get all categories
        $categories = ProductCategory::all();
        $scores = [];
        
        foreach ($categories as $category) {
            $categoryName = strtolower($category->category ?? '');
            $score = 0;
            
            // Direct name match
            if (strpos($text, $categoryName) !== false) {
                $score += 10;
            }
            
            // Keyword matching based on category
            $keywords = $this->getCategoryKeywords($categoryName);
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $score += 2;
                }
            }
            
            $scores[$category->id] = $score;
        }
        
        // Return category with highest score
        arsort($scores);
        $suggestedCategoryId = array_key_first($scores);
        
        return $categories->find($suggestedCategoryId);
    }
    
    private function getCategoryKeywords($categoryName)
    {
        $keywordMap = [
            'phone' => ['phone', 'smartphone', 'mobile', 'cell', 'android', 'ios'],
            'computer' => ['laptop', 'computer', 'pc', 'notebook', 'desktop'],
            'tablet' => ['tablet', 'ipad', 'tab'],
            'audio' => ['headphone', 'speaker', 'earphone', 'headset', 'music', 'sound'],
            'watch' => ['watch', 'smartwatch', 'wearable'],
            'camera' => ['camera', 'photo', 'video', 'lens'],
            'accessory' => ['case', 'cover', 'charger', 'cable', 'adapter', 'stand'],
            'game' => ['gaming', 'console', 'controller', 'game'],
            'home' => ['smart', 'home', 'automation', 'iot'],
            'health' => ['fitness', 'health', 'medical', 'sport']
        ];
        
        foreach ($keywordMap as $key => $keywords) {
            if (strpos($categoryName, $key) !== false) {
                return $keywords;
            }
        }
        
        return [];
    }
    
    private function logProductAnalysis($product, $tags, $suggestedCategory, $dryRun)
    {
        $logEntry = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'current_category' => $product->category,
            'suggested_category' => $suggestedCategory ? $suggestedCategory->id : null,
            'suggested_category_name' => $suggestedCategory ? $suggestedCategory->category : null,
            'generated_tags' => implode(',', $tags),
            'processed_at' => now(),
            'dry_run' => $dryRun
        ];
        
        // Log to file for review
        $logFile = storage_path('logs/product_analysis.json');
        $existingLog = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        $existingLog[] = $logEntry;
        file_put_contents($logFile, json_encode($existingLog, JSON_PRETTY_PRINT));
    }
}
