<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\AIReviewGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAIReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:generate-ai 
                           {--batch-size=200 : Number of products to process per batch}
                           {--reviews-per-product=9 : Number of reviews to generate per product (6-12 range)}
                           {--start-from=1 : Product ID to start from}
                           {--limit= : Maximum number of products to process}
                           {--dry-run : Show what would be generated without actually creating reviews}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI-powered contextual reviews for products';

    /**
     * The AI review generator service
     */
    private AIReviewGeneratorService $reviewGenerator;

    /**
     * Create a new command instance.
     */
    public function __construct(AIReviewGeneratorService $reviewGenerator)
    {
        parent::__construct();
        $this->reviewGenerator = $reviewGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $reviewsPerProduct = (int) $this->option('reviews-per-product');
        $startFrom = (int) $this->option('start-from');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $isDryRun = $this->option('dry-run');

        // Validate reviews per product range
        if ($reviewsPerProduct < 6 || $reviewsPerProduct > 12) {
            $this->error('Reviews per product must be between 6 and 12');
            return 1;
        }

        $this->info('🤖 AI Review Generator Starting...');
        $this->info("Configuration:");
        $this->info("- Batch Size: {$batchSize}");
        $this->info("- Reviews per Product: {$reviewsPerProduct}");
        $this->info("- Starting from Product ID: {$startFrom}");
        $this->info("- Limit: " . ($limit ? $limit : 'No limit'));
        $this->info("- Mode: " . ($isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        // Get products to process
        $query = Product::where('id', '>=', $startFrom)->orderBy('id');
        
        if ($limit) {
            $query->limit($limit);
        }

        $totalProducts = $query->count();
        
        if ($limit && $totalProducts > $limit) {
            $totalProducts = $limit;
        }
        $this->info("📊 Total products to process: {$totalProducts}");

        if ($isDryRun) {
            $this->info("🔍 DRY RUN: No reviews will be created");
            $this->info("Estimated reviews to generate: " . ($totalProducts * $reviewsPerProduct));
            return 0;
        }

        // Confirm before proceeding
        if (!$this->confirm('Do you want to proceed with generating AI reviews?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Process in batches
        $this->info("🚀 Starting AI review generation...");
        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->setFormat('verbose');

        $overallStats = [
            'total_products_processed' => 0,
            'total_reviews_generated' => 0,
            'total_failures' => 0,
            'batches_processed' => 0
        ];

        $products = $query->get();
        $productChunks = $products->chunk($batchSize);

        foreach ($productChunks as $chunkIndex => $productChunk) {
            $this->newLine();
            $this->info("📦 Processing batch " . ($chunkIndex + 1) . " of " . $productChunks->count());
            
            $productIds = $productChunk->pluck('id')->toArray();
            
            try {
                // Use database transaction for each batch
                DB::beginTransaction();
                
                $batchResults = $this->reviewGenerator->generateReviewsForBatch(
                    $productIds, 
                    $reviewsPerProduct
                );
                
                DB::commit();
                
                // Update statistics
                $overallStats['total_products_processed'] += count($productIds);
                $overallStats['total_reviews_generated'] += $batchResults['success'];
                $overallStats['total_failures'] += $batchResults['failed'];
                $overallStats['batches_processed']++;
                
                // Show batch results
                $this->info("✅ Batch completed:");
                $this->info("   - Products: " . count($productIds));
                $this->info("   - Reviews generated: " . $batchResults['success']);
                $this->info("   - Failures: " . $batchResults['failed']);
                
                if (!empty($batchResults['errors'])) {
                    $this->warn("⚠️  Batch errors:");
                    foreach (array_slice($batchResults['errors'], 0, 5) as $error) {
                        $this->warn("   - " . $error);
                    }
                    if (count($batchResults['errors']) > 5) {
                        $this->warn("   - ... and " . (count($batchResults['errors']) - 5) . " more errors");
                    }
                }
                
                $progressBar->advance(count($productIds));
                
                // Small delay between batches to prevent overwhelming the system
                sleep(1);
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Batch failed: " . $e->getMessage());
                $overallStats['total_failures'] += count($productIds);
                $progressBar->advance(count($productIds));
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Final results
        $this->info("🎉 AI Review Generation Complete!");
        $this->info("📈 Final Statistics:");
        $this->info("   - Products processed: " . $overallStats['total_products_processed']);
        $this->info("   - Reviews generated: " . $overallStats['total_reviews_generated']);
        $this->info("   - Failures: " . $overallStats['total_failures']);
        $this->info("   - Batches processed: " . $overallStats['batches_processed']);
        $this->info("   - Success rate: " . round(($overallStats['total_reviews_generated'] / ($overallStats['total_products_processed'] * $reviewsPerProduct)) * 100, 2) . "%");

        // Show some sample generated reviews
        $this->newLine();
        $this->info("📝 Sample generated reviews:");
        $sampleReviews = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->select('reviews.rating', 'reviews.comment', 'products.name as product_name', 'users.name as user_name')
            ->where('reviews.created_at', '>=', now()->subHour())
            ->orderBy('reviews.id', 'desc')
            ->limit(3)
            ->get();

        foreach ($sampleReviews as $review) {
            $this->info("⭐ {$review->rating}/5 - " . substr($review->product_name, 0, 40) . "...");
            $this->info("   \"{$review->comment}\" - {$review->user_name}");
            $this->newLine();
        }

        return 0;
    }
}
