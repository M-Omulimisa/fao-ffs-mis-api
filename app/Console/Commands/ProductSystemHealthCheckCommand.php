<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ProductSystemHealthCheckCommand extends Command
{
    protected $signature = 'products:health-check 
                            {--detailed : Show detailed analysis}
                            {--fix : Attempt to fix issues automatically}';

    protected $description = 'Comprehensive health check for the product optimization system';

    private $issues = [];
    private $recommendations = [];
    private $stats = [];

    public function handle()
    {
        $detailed = $this->option('detailed');
        $autoFix = $this->option('fix');
        
        $this->info("=== Product System Health Check ===");
        $this->info("Running comprehensive system analysis...");
        $this->newLine();
        
        // Run all health checks
        $this->checkDatabaseStructure();
        $this->checkDataIntegrity();
        $this->checkPerformanceMetrics();
        $this->checkSystemConfiguration();
        
        if ($detailed) {
            $this->checkDataQuality();
            $this->checkOptimizationOpportunities();
        }
        
        // Display results
        $this->displayResults($detailed);
        
        // Attempt fixes if requested
        if ($autoFix && !empty($this->issues)) {
            $this->attemptFixes();
        }
        
        return empty($this->issues) ? 0 : 1;
    }
    
    private function checkDatabaseStructure()
    {
        $this->line("🔍 Checking database structure...");
        
        // Check required tables exist
        $requiredTables = [
            'products',
            'product_categories', 
            'product_category_attributes',
            'product_has_attributes'
        ];
        
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->issues[] = "Missing required table: {$table}";
            }
        }
        
        // Check required columns
        $requiredColumns = [
            'products' => ['id', 'name', 'description', 'category', 'tags'],
            'product_categories' => ['id', 'category', 'parent_id'],
            'product_category_attributes' => ['id', 'product_category_id', 'name', 'attribute_type'],
            'product_has_attributes' => ['id', 'product_id', 'name', 'value']
        ];
        
        foreach ($requiredColumns as $table => $columns) {
            if (Schema::hasTable($table)) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        $this->issues[] = "Missing column {$column} in table {$table}";
                    }
                }
            }
        }
        
        // Check indexes for performance
        $this->checkIndexes();
        
        $this->line("   ✅ Database structure check completed");
    }
    
    private function checkIndexes()
    {
        // Check if important indexes exist
        $indexChecks = [
            'products' => ['category', 'tags'],
            'product_has_attributes' => ['product_id', 'name'],
            'product_category_attributes' => ['product_category_id']
        ];
        
        foreach ($indexChecks as $table => $columns) {
            foreach ($columns as $column) {
                // This is a simplified check - in production you'd query INFORMATION_SCHEMA
                // For now, we'll note it as a recommendation
                $this->recommendations[] = "Consider adding index on {$table}.{$column} for better performance";
            }
        }
    }
    
    private function checkDataIntegrity()
    {
        $this->line("🔍 Checking data integrity...");
        
        try {
            // Check for orphaned product attributes
            $orphanedAttributes = DB::select("
                SELECT COUNT(*) as count 
                FROM product_has_attributes pha
                LEFT JOIN products p ON pha.product_id = p.id
                WHERE p.id IS NULL
            ")[0]->count;
            
            if ($orphanedAttributes > 0) {
                $this->issues[] = "Found {$orphanedAttributes} orphaned product attributes";
            }
            
            // Check for products without categories
            $productsWithoutCategory = DB::table('products')
                ->whereNull('category')
                ->orWhere('category', 0)
                ->count();
            
            if ($productsWithoutCategory > 0) {
                $this->issues[] = "Found {$productsWithoutCategory} products without valid category";
            }
            
            // Check for category attributes without valid categories
            $invalidCategoryAttributes = DB::select("
                SELECT COUNT(*) as count 
                FROM product_category_attributes pca
                LEFT JOIN product_categories pc ON pca.product_category_id = pc.id
                WHERE pc.id IS NULL
            ")[0]->count;
            
            if ($invalidCategoryAttributes > 0) {
                $this->issues[] = "Found {$invalidCategoryAttributes} category attributes with invalid category references";
            }
            
            $this->stats['orphaned_attributes'] = $orphanedAttributes;
            $this->stats['products_without_category'] = $productsWithoutCategory;
            $this->stats['invalid_category_attributes'] = $invalidCategoryAttributes;
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking data integrity: " . $e->getMessage();
        }
        
        $this->line("   ✅ Data integrity check completed");
    }
    
    private function checkPerformanceMetrics()
    {
        $this->line("🔍 Checking performance metrics...");
        
        try {
            // Check table sizes
            $tableStats = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE()
                AND table_name IN ('products', 'product_categories', 'product_category_attributes', 'product_has_attributes')
            ");
            
            foreach ($tableStats as $stat) {
                $this->stats["table_{$stat->table_name}_rows"] = $stat->table_rows;
                $this->stats["table_{$stat->table_name}_size_mb"] = $stat->size_mb;
                
                // Flag large tables for optimization consideration
                if ($stat->size_mb > 100) {
                    $this->recommendations[] = "Table {$stat->table_name} is {$stat->size_mb}MB - consider partitioning or archiving";
                }
            }
            
            // Check for slow queries (if query log is enabled)
            $this->checkSlowQueries();
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking performance metrics: " . $e->getMessage();
        }
        
        $this->line("   ✅ Performance metrics check completed");
    }
    
    private function checkSlowQueries()
    {
        // Check if slow query log is enabled
        try {
            $slowQueryLog = DB::select("SHOW VARIABLES LIKE 'slow_query_log'")[0];
            if ($slowQueryLog->Value !== 'ON') {
                $this->recommendations[] = "Enable slow query log for performance monitoring";
            }
        } catch (\Exception $e) {
            // Ignore if we can't check slow query log
        }
    }
    
    private function checkSystemConfiguration()
    {
        $this->line("🔍 Checking system configuration...");
        
        // Check queue configuration
        $queueDriver = config('queue.default');
        $this->stats['queue_driver'] = $queueDriver;
        
        if ($queueDriver === 'sync') {
            $this->recommendations[] = "Consider using async queue driver (database, redis) for better performance";
        }
        
        // Check cache configuration
        $cacheDriver = config('cache.default');
        $this->stats['cache_driver'] = $cacheDriver;
        
        if ($cacheDriver === 'file') {
            $this->recommendations[] = "Consider using Redis or Memcached for better cache performance";
        }
        
        // Check memory limits
        $memoryLimit = ini_get('memory_limit');
        $this->stats['php_memory_limit'] = $memoryLimit;
        
        if (preg_match('/(\d+)M/', $memoryLimit, $matches) && $matches[1] < 256) {
            $this->recommendations[] = "Consider increasing PHP memory limit for processing large datasets";
        }
        
        $this->line("   ✅ System configuration check completed");
    }
    
    private function checkDataQuality()
    {
        $this->line("🔍 Checking data quality...");
        
        try {
            // Check products without tags
            $productsWithoutTags = DB::table('products')
                ->where(function($query) {
                    $query->whereNull('tags')
                          ->orWhere('tags', '')
                          ->orWhere('tags', 'N/A');
                })
                ->count();
            
            // Check products without attributes
            $productsWithoutAttributes = DB::select("
                SELECT COUNT(DISTINCT p.id) as count
                FROM products p
                LEFT JOIN product_has_attributes pha ON p.id = pha.product_id
                WHERE pha.product_id IS NULL
            ")[0]->count;
            
            // Check empty or low-quality descriptions
            $poorDescriptions = DB::table('products')
                ->where(function($query) {
                    $query->whereNull('description')
                          ->orWhere('description', '')
                          ->orWhereRaw('LENGTH(description) < 20');
                })
                ->count();
            
            $this->stats['products_without_tags'] = $productsWithoutTags;
            $this->stats['products_without_attributes'] = $productsWithoutAttributes;
            $this->stats['products_poor_descriptions'] = $poorDescriptions;
            
            $totalProducts = DB::table('products')->count();
            
            if ($productsWithoutTags > ($totalProducts * 0.1)) {
                $this->issues[] = "High percentage of products without tags ({$productsWithoutTags}/{$totalProducts})";
            }
            
            if ($productsWithoutAttributes > ($totalProducts * 0.2)) {
                $this->issues[] = "High percentage of products without attributes ({$productsWithoutAttributes}/{$totalProducts})";
            }
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking data quality: " . $e->getMessage();
        }
        
        $this->line("   ✅ Data quality check completed");
    }
    
    private function checkOptimizationOpportunities()
    {
        $this->line("🔍 Checking optimization opportunities...");
        
        try {
            // Check for categories without attributes
            $categoriesWithoutAttributes = DB::select("
                SELECT COUNT(DISTINCT pc.id) as count
                FROM product_categories pc
                LEFT JOIN product_category_attributes pca ON pc.id = pca.product_category_id
                WHERE pca.product_category_id IS NULL
            ")[0]->count;
            
            if ($categoriesWithoutAttributes > 0) {
                $this->recommendations[] = "Add attributes to {$categoriesWithoutAttributes} categories without attributes";
            }
            
            // Check for underutilized attributes
            $underutilizedAttributes = DB::select("
                SELECT pca.name, COUNT(pha.id) as usage_count
                FROM product_category_attributes pca
                LEFT JOIN product_has_attributes pha ON pca.name = pha.name
                GROUP BY pca.name
                HAVING usage_count < 10
                ORDER BY usage_count
                LIMIT 5
            ");
            
            foreach ($underutilizedAttributes as $attr) {
                $this->recommendations[] = "Attribute '{$attr->name}' is underutilized (only {$attr->usage_count} products)";
            }
            
            // Check for categories with very few products
            $sparseCateories = DB::select("
                SELECT pc.category, COUNT(p.id) as product_count
                FROM product_categories pc
                LEFT JOIN products p ON pc.id = p.category
                GROUP BY pc.id, pc.category
                HAVING product_count < 5
                ORDER BY product_count
                LIMIT 5
            ");
            
            foreach ($sparseCateories as $cat) {
                $this->recommendations[] = "Category '{$cat->category}' has very few products ({$cat->product_count})";
            }
            
        } catch (\Exception $e) {
            $this->issues[] = "Error checking optimization opportunities: " . $e->getMessage();
        }
        
        $this->line("   ✅ Optimization opportunities check completed");
    }
    
    private function displayResults($detailed)
    {
        $this->newLine();
        $this->info("=== Health Check Results ===");
        
        // Overall status
        if (empty($this->issues)) {
            $this->line("<fg=green>✅ System Status: HEALTHY</>");
        } else {
            $this->line("<fg=red>⚠️  System Status: ISSUES FOUND</>");
        }
        
        $this->newLine();
        
        // Issues
        if (!empty($this->issues)) {
            $this->line("<fg=red>🚨 Issues Found:</>");
            foreach ($this->issues as $issue) {
                $this->line("  • {$issue}");
            }
            $this->newLine();
        }
        
        // Recommendations
        if (!empty($this->recommendations)) {
            $this->line("<fg=yellow>💡 Recommendations:</>");
            foreach ($this->recommendations as $rec) {
                $this->line("  • {$rec}");
            }
            $this->newLine();
        }
        
        // Statistics
        if ($detailed && !empty($this->stats)) {
            $this->line("<fg=blue>📊 System Statistics:</>");
            foreach ($this->stats as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
            $this->newLine();
        }
        
        // System summary
        $this->displaySystemSummary();
    }
    
    private function displaySystemSummary()
    {
        $this->line("<fg=cyan>📋 System Summary:</>");
        
        try {
            $totalProducts = DB::table('products')->count();
            $totalCategories = DB::table('product_categories')->count();
            $totalAttributes = DB::table('product_has_attributes')->count();
            $totalCategoryAttributes = DB::table('product_category_attributes')->count();
            
            $this->line("  Products: {$totalProducts}");
            $this->line("  Categories: {$totalCategories}");
            $this->line("  Product Attributes: {$totalAttributes}");
            $this->line("  Category Attribute Definitions: {$totalCategoryAttributes}");
            
            $completion = 0;
            if ($totalProducts > 0) {
                $productsWithTags = DB::table('products')->whereNotNull('tags')->where('tags', '!=', '')->count();
                $completion = round(($productsWithTags / $totalProducts) * 100, 1);
            }
            
            $this->line("  Tag Completion: {$completion}%");
            
        } catch (\Exception $e) {
            $this->line("  Unable to generate summary: " . $e->getMessage());
        }
    }
    
    private function attemptFixes()
    {
        $this->newLine();
        $this->info("🔧 Attempting to fix issues...");
        
        $fixedCount = 0;
        
        foreach ($this->issues as $issue) {
            if (strpos($issue, 'orphaned product attributes') !== false) {
                if ($this->confirm("Remove orphaned product attributes?")) {
                    $deleted = DB::delete("
                        DELETE pha FROM product_has_attributes pha
                        LEFT JOIN products p ON pha.product_id = p.id
                        WHERE p.id IS NULL
                    ");
                    $this->line("  ✅ Removed {$deleted} orphaned attributes");
                    $fixedCount++;
                }
            }
            
            if (strpos($issue, 'category attributes with invalid category') !== false) {
                if ($this->confirm("Remove invalid category attributes?")) {
                    $deleted = DB::delete("
                        DELETE pca FROM product_category_attributes pca
                        LEFT JOIN product_categories pc ON pca.product_category_id = pc.id
                        WHERE pc.id IS NULL
                    ");
                    $this->line("  ✅ Removed {$deleted} invalid category attributes");
                    $fixedCount++;
                }
            }
        }
        
        if ($fixedCount > 0) {
            $this->line("<fg=green>✅ Fixed {$fixedCount} issues</>");
        } else {
            $this->line("<fg=yellow>No automatic fixes applied</>");
        }
    }
}
