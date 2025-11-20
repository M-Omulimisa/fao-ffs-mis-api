<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessProductBatchJob;

class QueueProductProcessingCommand extends Command
{
    protected $signature = 'products:queue-processing 
                            {--batch-size=50 : Number of products per job}
                            {--start-id=1 : Starting product ID}
                            {--end-id= : Ending product ID (optional)}
                            {--queue=product-processing : Queue name to use}
                            {--delay=0 : Delay between jobs in seconds}
                            {--tags : Generate tags}
                            {--attributes : Populate attributes}
                            {--categories : Suggest categories}
                            {--dry-run : Show what would be queued without actually doing it}';

    protected $description = 'Queue product processing jobs for background execution';

    public function handle()
    {
        $batchSize = $this->option('batch-size');
        $startId = $this->option('start-id');
        $endId = $this->option('end-id');
        $queueName = $this->option('queue');
        $delay = $this->option('delay');
        $dryRun = $this->option('dry-run');
        
        // Build processing options
        $options = [
            'generate_tags' => $this->option('tags'),
            'populate_attributes' => $this->option('attributes'),
            'suggest_categories' => $this->option('categories'),
            'cache_results' => true,
            'update_search_index' => false,
        ];
        
        $this->info("=== Product Processing Queue Manager ===");
        $this->info("Batch size: {$batchSize}");
        $this->info("Queue: {$queueName}");
        $this->info("Processing options: " . json_encode($options));
        $this->info("Dry run: " . ($dryRun ? 'Yes' : 'No'));
        
        // Build query conditions
        $whereConditions = ["id >= ?"];
        $params = [$startId];
        
        if ($endId) {
            $whereConditions[] = "id <= ?";
            $params[] = $endId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count and IDs
        $totalCount = DB::selectOne("SELECT COUNT(*) as count FROM products WHERE {$whereClause}", $params)->count;
        $this->info("Total products to process: {$totalCount}");
        
        if ($totalCount === 0) {
            $this->info("No products found to process.");
            return 0;
        }
        
        // Calculate number of jobs
        $numberOfJobs = ceil($totalCount / $batchSize);
        $this->info("Will create {$numberOfJobs} jobs");
        
        if ($dryRun) {
            $this->info("\n=== DRY RUN - Would queue the following jobs: ===");
            $offset = 0;
            $jobNumber = 1;
            
            while ($offset < $totalCount) {
                $productIds = DB::select("
                    SELECT id FROM products 
                    WHERE {$whereClause}
                    ORDER BY id 
                    LIMIT ? OFFSET ?
                ", array_merge($params, [$batchSize, $offset]));
                
                $batchProductIds = array_column($productIds, 'id');
                $this->info("Job {$jobNumber}: Process products " . implode(',', $batchProductIds));
                
                $offset += $batchSize;
                $jobNumber++;
            }
            
            $this->info("\nTo actually queue these jobs, run without --dry-run flag");
            return 0;
        }
        
        // Confirm before proceeding
        if (!$this->confirm("Queue {$numberOfJobs} processing jobs for {$totalCount} products?")) {
            $this->info("Operation cancelled.");
            return 0;
        }
        
        // Create jobs
        $offset = 0;
        $jobsCreated = 0;
        $progressBar = $this->output->createProgressBar($numberOfJobs);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% Creating jobs...');
        $progressBar->start();
        
        while ($offset < $totalCount) {
            try {
                // Get batch of product IDs
                $productIds = DB::select("
                    SELECT id FROM products 
                    WHERE {$whereClause}
                    ORDER BY id 
                    LIMIT ? OFFSET ?
                ", array_merge($params, [$batchSize, $offset]));
                
                if (empty($productIds)) {
                    break;
                }
                
                $batchProductIds = array_column($productIds, 'id');
                
                // Create job metadata
                $batchMeta = [
                    'batch_id' => uniqid('queue_batch_'),
                    'job_number' => $jobsCreated + 1,
                    'total_jobs' => $numberOfJobs,
                    'initiated_by' => 'queue_command',
                    'initiated_at' => now(),
                ];
                
                // Create and dispatch job
                $job = new ProcessProductBatchJob($batchProductIds, $options, $batchMeta);
                
                if ($delay > 0 && $jobsCreated > 0) {
                    $job->delay(now()->addSeconds($delay * $jobsCreated));
                }
                
                Queue::pushOn($queueName, $job);
                
                $jobsCreated++;
                $progressBar->advance();
                
                $offset += $batchSize;
                
            } catch (\Exception $e) {
                $this->error("Error creating job for offset {$offset}: " . $e->getMessage());
                $offset += $batchSize;
            }
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✅ Successfully queued {$jobsCreated} jobs for processing {$totalCount} products");
        $this->info("Queue: {$queueName}");
        $this->info("Estimated processing time: " . $this->estimateProcessingTime($totalCount, $batchSize));
        
        $this->newLine();
        $this->info("Monitor progress with:");
        $this->line("  php artisan queue:work --queue={$queueName}");
        $this->line("  php artisan products:monitor-queue");
        
        // Log the queue operation
        Log::info("Product processing jobs queued", [
            'total_products' => $totalCount,
            'jobs_created' => $jobsCreated,
            'batch_size' => $batchSize,
            'queue' => $queueName,
            'options' => $options
        ]);
        
        return 0;
    }
    
    private function estimateProcessingTime($totalProducts, $batchSize): string
    {
        // Rough estimate: 2 seconds per product
        $estimatedSeconds = $totalProducts * 2;
        
        $hours = floor($estimatedSeconds / 3600);
        $minutes = floor(($estimatedSeconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }
}
