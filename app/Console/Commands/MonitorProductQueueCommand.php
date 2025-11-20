<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class MonitorProductQueueCommand extends Command
{
    protected $signature = 'products:monitor-queue 
                            {--queue=product-processing : Queue name to monitor}
                            {--refresh=5 : Refresh interval in seconds}
                            {--continuous : Run continuously}';

    protected $description = 'Monitor product processing queue status and statistics';

    public function handle()
    {
        $queueName = $this->option('queue');
        $refreshInterval = $this->option('refresh');
        $continuous = $this->option('continuous');
        
        if ($continuous) {
            $this->info("Monitoring queue '{$queueName}' - Press Ctrl+C to stop");
            $this->newLine();
            
            while (true) {
                $this->clearConsole();
                $this->displayQueueStatus($queueName);
                sleep($refreshInterval);
            }
        } else {
            $this->displayQueueStatus($queueName);
        }
        
        return 0;
    }
    
    private function displayQueueStatus($queueName)
    {
        $this->info("=== Product Processing Queue Monitor ===");
        $this->info("Queue: {$queueName}");
        $this->info("Updated: " . now()->format('Y-m-d H:i:s'));
        $this->newLine();
        
        // Queue statistics
        $this->displayQueueStats($queueName);
        
        // Global processing statistics
        $this->displayGlobalStats();
        
        // Recent completed batches
        $this->displayRecentBatches();
        
        // Database statistics
        $this->displayDatabaseStats();
    }
    
    private function displayQueueStats($queueName)
    {
        $this->line("<fg=cyan>📊 Queue Statistics</>");
        
        try {
            // This would depend on your queue driver
            // For database queue, you can query the jobs table
            $pending = DB::table('jobs')->where('queue', $queueName)->count();
            $failed = DB::table('failed_jobs')->count();
            
            $this->line("  Pending jobs: <fg=yellow>{$pending}</>");
            $this->line("  Failed jobs: <fg=red>{$failed}</>");
            
            if ($pending > 0) {
                $oldestJob = DB::table('jobs')
                    ->where('queue', $queueName)
                    ->orderBy('created_at')
                    ->first();
                
                if ($oldestJob) {
                    $waitTime = now()->diffInMinutes($oldestJob->created_at);
                    $this->line("  Oldest job waiting: <fg=blue>{$waitTime} minutes</>");
                }
            }
            
        } catch (\Exception $e) {
            $this->line("  <fg=red>Unable to fetch queue stats: " . $e->getMessage() . "</>");
        }
        
        $this->newLine();
    }
    
    private function displayGlobalStats()
    {
        $this->line("<fg=green>🚀 Processing Statistics</>");
        
        $globalStats = Cache::get('product_processing_global_stats', [
            'total_batches' => 0,
            'total_products_processed' => 0,
            'total_tags_generated' => 0,
            'total_attributes_created' => 0,
            'last_updated' => null,
        ]);
        
        $this->line("  Total batches completed: <fg=green>{$globalStats['total_batches']}</>");
        $this->line("  Products processed: <fg=green>{$globalStats['total_products_processed']}</>");
        $this->line("  Tags generated: <fg=green>{$globalStats['total_tags_generated']}</>");
        $this->line("  Attributes created: <fg=green>{$globalStats['total_attributes_created']}</>");
        
        if ($globalStats['last_updated']) {
            $this->line("  Last updated: <fg=blue>{$globalStats['last_updated']}</>");
        }
        
        $this->newLine();
    }
    
    private function displayRecentBatches()
    {
        $this->line("<fg=magenta>📦 Recent Completed Batches</>");
        
        // Get recent batch results from cache
        $cacheKeys = [];
        $pattern = 'product_batch_result:*';
        
        // This would depend on your cache driver
        // For demonstration, we'll show a simple approach
        $recentBatches = [];
        
        // Try to get some sample batch results
        for ($i = 0; $i < 5; $i++) {
            $cacheKey = "product_batch_result:batch_" . (time() - ($i * 100));
            $batch = Cache::get($cacheKey);
            if ($batch) {
                $recentBatches[] = $batch;
            }
        }
        
        if (empty($recentBatches)) {
            $this->line("  <fg=gray>No recent batch data available</>");
        } else {
            foreach ($recentBatches as $batch) {
                $this->line("  Batch {$batch['batch_meta']['batch_id']}: " .
                          "{$batch['stats']['processed']} products, " .
                          "{$batch['stats']['tags_generated']} tags, " .
                          "{$batch['stats']['attributes_created']} attributes");
            }
        }
        
        $this->newLine();
    }
    
    private function displayDatabaseStats()
    {
        $this->line("<fg=blue>📊 Database Statistics</>");
        
        try {
            // Product statistics
            $totalProducts = DB::table('products')->count();
            $productsWithTags = DB::table('products')->whereNotNull('tags')->where('tags', '!=', '')->count();
            $productsWithSpecifications = DB::table('product_has_specifications')->distinct('product_id')->count('product_id');
            
            $this->line("  Total products: <fg=blue>{$totalProducts}</>");
            $this->line("  Products with tags: <fg=green>{$productsWithTags}</> (" . 
                       round(($productsWithTags / max($totalProducts, 1)) * 100, 1) . "%)");            $this->line("  Products with specifications: <fg=green>{$productsWithSpecifications}</> (" .
                       round(($productsWithSpecifications / max($totalProducts, 1)) * 100, 1) . "%)");
            
            // Attribute statistics
            $totalSpecifications = DB::table('product_has_specifications')->count();
            $uniqueSpecificationNames = DB::table('product_has_specifications')->distinct('name')->count('name');
            
            $this->line("  Total specification values: <fg=blue>{$totalSpecifications}</>");
            $this->line("  Unique specification types: <fg=blue>{$uniqueSpecificationNames}</>");
            
            // Category statistics
            $totalCategories = DB::table('product_categories')->count();
            $categoriesWithSpecifications = DB::table('product_category_specifications')->distinct('product_category_id')->count('product_category_id');
            
            $this->line("  Total categories: <fg=blue>{$totalCategories}</>");
            $this->line("  Categories with specifications: <fg=green>{$categoriesWithSpecifications}</>");
            
        } catch (\Exception $e) {
            $this->line("  <fg=red>Unable to fetch database stats: " . $e->getMessage() . "</>");
        }
        
        $this->newLine();
    }
    
    private function clearConsole()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
}
