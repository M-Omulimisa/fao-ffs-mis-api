<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategorySpecification;
use App\Models\ProductHasSpecification;
use Exception;

class ProcessProductBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The product IDs to process in this batch
     */
    public array $productIds;
    
    /**
     * Processing options
     */
    public array $options;
    
    /**
     * Batch metadata
     */
    public array $batchMeta;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(array $productIds, array $options = [], array $batchMeta = [])
    {
        $this->productIds = $productIds;
        $this->options = array_merge([
            'generate_tags' => true,
            'populate_attributes' => true,
            'suggest_categories' => false,
            'cache_results' => true,
            'update_search_index' => false,
        ], $options);
        
        $this->batchMeta = array_merge([
            'batch_id' => uniqid('batch_'),
            'total_products' => count($productIds),
            'initiated_by' => 'queue',
            'initiated_at' => now(),
        ], $batchMeta);
        
        // Set queue configuration
        $this->onQueue('product-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $stats = [
            'processed' => 0,
            'tags_generated' => 0,
            'attributes_created' => 0,
            'categories_suggested' => 0,
            'errors' => [],
        ];
        
        Log::info("ProcessProductBatchJob started", [
            'batch_id' => $this->batchMeta['batch_id'],
            'product_count' => count($this->productIds),
            'options' => $this->options
        ]);

        try {
            // Fetch products efficiently
            $products = $this->fetchProductsForProcessing();
            
            foreach ($products as $product) {
                try {
                    $productStats = $this->processProduct($product);
                    
                    $stats['processed']++;
                    $stats['tags_generated'] += $productStats['tags_count'] ?? 0;
                    $stats['attributes_created'] += $productStats['attributes_count'] ?? 0;
                    $stats['categories_suggested'] += $productStats['categories_suggested'] ?? 0;
                    
                } catch (Exception $e) {
                    $stats['errors'][] = "Product {$product->id}: " . $e->getMessage();
                    Log::error("Error processing product in batch", [
                        'product_id' => $product->id,
                        'batch_id' => $this->batchMeta['batch_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Cache results if enabled
            if ($this->options['cache_results']) {
                $this->cacheProcessingResults($stats);
            }
            
            $endTime = microtime(true);
            $stats['processing_time'] = round($endTime - $startTime, 2);
            
            Log::info("ProcessProductBatchJob completed", [
                'batch_id' => $this->batchMeta['batch_id'],
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            Log::error("ProcessProductBatchJob failed", [
                'batch_id' => $this->batchMeta['batch_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Fetch products for processing with optimized query
     */
    private function fetchProductsForProcessing(): array
    {
        $placeholders = str_repeat('?,', count($this->productIds) - 1) . '?';
        
        return DB::select("
            SELECT p.id, p.name, p.description, p.category, p.tags, p.keywords, p.summary,
                   pc.category as category_name, pc.parent_id
            FROM products p
            LEFT JOIN product_categories pc ON p.category = pc.id
            WHERE p.id IN ({$placeholders})
            ORDER BY p.id
        ", $this->productIds);
    }

    /**
     * Process a single product
     */
    private function processProduct($product): array
    {
        $stats = [
            'tags_count' => 0,
            'attributes_count' => 0,
            'categories_suggested' => 0,
        ];
        
        // Generate and update tags
        if ($this->options['generate_tags']) {
            $stats['tags_count'] = $this->generateAndUpdateTags($product);
        }
        
        // Populate attributes
        if ($this->options['populate_attributes']) {
            $stats['attributes_count'] = $this->populateProductAttributes($product);
        }
        
        // Suggest categories (advanced feature)
        if ($this->options['suggest_categories']) {
            $stats['categories_suggested'] = $this->suggestCategories($product);
        }
        
        return $stats;
    }

    /**
     * Generate and update product tags
     */
    private function generateAndUpdateTags($product): int
    {
        $tags = $this->extractTags($product);
        $tagsString = implode(',', $tags);
        
        DB::update("UPDATE products SET tags = ? WHERE id = ?", [$tagsString, $product->id]);
        
        return count($tags);
    }

    /**
     * Extract tags from product content
     */
    private function extractTags($product): array
    {
        $content = strtolower(trim($product->name . ' ' . $product->description . ' ' . $product->keywords . ' ' . $product->summary));
        $tags = [];
        
        if (empty($content)) {
            return $tags;
        }
        
        // Enhanced tag extraction patterns
        $patterns = [
            // Material patterns
            '/\b(cotton|silk|wool|polyester|leather|denim|linen|bamboo|organic|synthetic)\b/i',
            // Color patterns  
            '/\b(red|blue|green|yellow|black|white|brown|gray|grey|pink|purple|orange|navy|beige|cream|gold|silver)\b/i',
            // Size patterns
            '/\b(small|medium|large|xl|xxl|xs|size\s*\d+|plus\s*size)\b/i',
            // Brand/quality indicators
            '/\b(premium|luxury|designer|eco|sustainable|handmade|artisan|vintage|modern|classic)\b/i',
            // Style patterns
            '/\b(casual|formal|business|sporty|elegant|minimalist|bohemian|retro|contemporary)\b/i',
            // Season patterns
            '/\b(summer|winter|spring|fall|autumn|seasonal|holiday)\b/i',
            // Feature patterns
            '/\b(waterproof|breathable|lightweight|durable|comfortable|soft|warm|cool|stretchy)\b/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $tags = array_merge($tags, array_map('strtolower', $matches[0]));
            }
        }
        
        // Clean and deduplicate tags
        $tags = array_unique(array_filter(array_map('trim', $tags)));
        
        // Limit tags to reasonable number
        return array_slice($tags, 0, 15);
    }

    /**
     * Populate product attributes based on category
     */
    private function populateProductAttributes($product): int
    {
        if (!$product->category) {
            return 0;
        }
        
        // Get category attributes
        $categoryAttributes = DB::select("
            SELECT name, attribute_type, possible_values 
            FROM product_category_specifications 
            WHERE product_category_id = ?
        ", [$product->category]);
        
        if (empty($categoryAttributes)) {
            return 0;
        }
        
        // Clear existing attributes
        DB::delete("DELETE FROM product_has_specifications WHERE product_id = ?", [$product->id]);
        
        $attributesCreated = 0;
        $content = strtolower($product->name . ' ' . $product->description . ' ' . $product->keywords . ' ' . $product->summary);
        
        foreach ($categoryAttributes as $attribute) {
            $value = $this->extractAttributeValue($content, $attribute);
            
            if ($value) {
                DB::insert("
                    INSERT INTO product_has_specifications (product_id, name, value, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ", [$product->id, $attribute->name, $value]);
                
                $attributesCreated++;
            }
        }
        
        return $attributesCreated;
    }

    /**
     * Extract attribute value from content
     */
    private function extractAttributeValue($content, $attribute): ?string
    {
        $attributeName = strtolower($attribute->name);
        
        // Define extraction patterns based on attribute type
        $patterns = [
            'color' => '/\b(red|blue|green|yellow|black|white|brown|gray|grey|pink|purple|orange|navy|beige|cream|gold|silver|multicolor|multi-color)\b/i',
            'size' => '/\b(xs|small|medium|large|xl|xxl|xxxl|\d+\s*(?:inch|cm|mm)|plus\s*size)\b/i',
            'material' => '/\b(cotton|silk|wool|polyester|leather|denim|linen|bamboo|organic|synthetic|plastic|metal|wood|glass|ceramic)\b/i',
            'brand' => '/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\b/',
            'style' => '/\b(casual|formal|business|sporty|elegant|minimalist|bohemian|retro|contemporary|modern|classic|vintage)\b/i',
            'season' => '/\b(summer|winter|spring|fall|autumn|all-season|year-round)\b/i',
        ];
        
        // Check if attribute name matches known patterns
        foreach ($patterns as $type => $pattern) {
            if (strpos($attributeName, $type) !== false) {
                if (preg_match($pattern, $content, $matches)) {
                    return ucfirst(strtolower(trim($matches[0])));
                }
            }
        }
        
        // For other attributes, try to find value in possible_values
        if ($attribute->possible_values) {
            $possibleValues = explode(',', $attribute->possible_values);
            foreach ($possibleValues as $value) {
                $value = trim($value);
                if (stripos($content, strtolower($value)) !== false) {
                    return $value;
                }
            }
        }
        
        // Fallback: extract first meaningful word after attribute name
        $pattern = '/\b' . preg_quote($attributeName, '/') . '\s*:?\s*([a-zA-Z0-9\-]+)/i';
        if (preg_match($pattern, $content, $matches)) {
            return ucfirst(strtolower(trim($matches[1])));
        }
        
        return null;
    }

    /**
     * Suggest better categories for products (advanced feature)
     */
    private function suggestCategories($product): int
    {
        // This is an advanced ML-style feature for category suggestion
        // For now, we'll implement a simple keyword-based approach
        
        $content = strtolower($product->name . ' ' . $product->description);
        $suggestions = [];
        
        // Get all categories with their keywords
        $categories = DB::select("
            SELECT id, category, parent_id 
            FROM product_categories 
            WHERE id != ?
        ", [$product->category]);
        
        foreach ($categories as $category) {
            $categoryKeywords = strtolower($category->category);
            $score = $this->calculateCategorySimilarity($content, $categoryKeywords);
            
            if ($score > 0.3) { // Threshold for suggestion
                $suggestions[] = [
                    'category_id' => $category->id,
                    'score' => $score,
                    'reason' => "Content similarity: {$score}"
                ];
            }
        }
        
        // Log suggestions for review
        if (!empty($suggestions)) {
            Log::info("Category suggestions for product", [
                'product_id' => $product->id,
                'current_category' => $product->category,
                'suggestions' => array_slice($suggestions, 0, 3) // Top 3 suggestions
            ]);
        }
        
        return count($suggestions);
    }

    /**
     * Calculate similarity between content and category
     */
    private function calculateCategorySimilarity($content, $categoryKeywords): float
    {
        $contentWords = array_unique(str_word_count($content, 1));
        $categoryWords = array_unique(str_word_count($categoryKeywords, 1));
        
        $intersection = array_intersect($contentWords, $categoryWords);
        $union = array_unique(array_merge($contentWords, $categoryWords));
        
        return count($union) > 0 ? count($intersection) / count($union) : 0;
    }

    /**
     * Cache processing results for monitoring
     */
    private function cacheProcessingResults(array $stats): void
    {
        $cacheKey = "product_batch_result:{$this->batchMeta['batch_id']}";
        $data = [
            'batch_meta' => $this->batchMeta,
            'options' => $this->options,
            'stats' => $stats,
            'completed_at' => now(),
        ];
        
        Cache::put($cacheKey, $data, 3600); // Cache for 1 hour
        
        // Also update global processing statistics
        $this->updateGlobalStats($stats);
    }

    /**
     * Update global processing statistics
     */
    private function updateGlobalStats(array $stats): void
    {
        $globalStatsKey = 'product_processing_global_stats';
        $globalStats = Cache::get($globalStatsKey, [
            'total_batches' => 0,
            'total_products_processed' => 0,
            'total_tags_generated' => 0,
            'total_attributes_created' => 0,
            'last_updated' => null,
        ]);
        
        $globalStats['total_batches']++;
        $globalStats['total_products_processed'] += $stats['processed'];
        $globalStats['total_tags_generated'] += $stats['tags_generated'];
        $globalStats['total_attributes_created'] += $stats['attributes_created'];
        $globalStats['last_updated'] = now();
        
        Cache::put($globalStatsKey, $globalStats, 86400); // Cache for 24 hours
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error("ProcessProductBatchJob failed permanently", [
            'batch_id' => $this->batchMeta['batch_id'],
            'product_ids' => $this->productIds,
            'options' => $this->options,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Optionally notify administrators or create alerts
        // Event::dispatch(new ProductBatchProcessingFailed($this->batchMeta, $exception));
    }
}
