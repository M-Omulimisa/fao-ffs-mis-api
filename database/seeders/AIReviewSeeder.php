<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\AIReviewGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AIReviewSeeder extends Seeder
{
    /**
     * The AI review generator service
     */
    private AIReviewGeneratorService $reviewGenerator;

    /**
     * Create a new seeder instance.
     */
    public function __construct(AIReviewGeneratorService $reviewGenerator)
    {
        $this->reviewGenerator = $reviewGenerator;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🤖 Starting AI Review Seeding...');

        // Configuration
        $batchSize = 200;
        $reviewsPerProduct = 8; // Average between 6-12
        $maxProducts = 100; // Limit for seeding (can be increased)

        // Get products to seed
        $products = Product::orderBy('id')->limit($maxProducts)->get();
        $totalProducts = $products->count();

        $this->command->info("📊 Seeding reviews for {$totalProducts} products");
        $this->command->info("🎯 Target: {$reviewsPerProduct} reviews per product");

        $progressBar = $this->command->getOutput()->createProgressBar($totalProducts);
        $progressBar->setFormat('verbose');

        $stats = [
            'products_processed' => 0,
            'reviews_generated' => 0,
            'failures' => 0
        ];

        // Process in chunks
        $productChunks = $products->chunk($batchSize);

        foreach ($productChunks as $chunk) {
            DB::beginTransaction();
            
            try {
                $productIds = $chunk->pluck('id')->toArray();
                $results = $this->reviewGenerator->generateReviewsForBatch($productIds, $reviewsPerProduct);
                
                $stats['products_processed'] += count($productIds);
                $stats['reviews_generated'] += $results['success'];
                $stats['failures'] += $results['failed'];
                
                DB::commit();
                $progressBar->advance(count($productIds));
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error("Batch failed: " . $e->getMessage());
                $stats['failures'] += count($chunk);
                $progressBar->advance(count($chunk));
            }
        }

        $progressBar->finish();
        $this->command->newLine(2);

        // Results
        $this->command->info('🎉 AI Review Seeding Complete!');
        $this->command->info("📈 Results:");
        $this->command->info("   - Products processed: {$stats['products_processed']}");
        $this->command->info("   - Reviews generated: {$stats['reviews_generated']}");
        $this->command->info("   - Failures: {$stats['failures']}");

        // Show sample reviews
        $this->showSampleReviews();
    }

    /**
     * Show sample generated reviews
     */
    private function showSampleReviews(): void
    {
        $this->command->newLine();
        $this->command->info('📝 Sample Generated Reviews:');

        $sampleReviews = DB::table('reviews')
            ->join('products', 'reviews.product_id', '=', 'products.id')
            ->join('users', 'reviews.user_id', '=', 'users.id')
            ->select('reviews.rating', 'reviews.comment', 'products.name as product_name', 'users.name as user_name')
            ->where('reviews.created_at', '>=', now()->subHour())
            ->orderBy('reviews.id', 'desc')
            ->limit(5)
            ->get();

        foreach ($sampleReviews as $review) {
            $this->command->info("⭐ {$review->rating}/5 stars");
            $this->command->info("📱 Product: " . substr($review->product_name, 0, 50) . "...");
            $this->command->info("💬 Review: \"{$review->comment}\"");
            $this->command->info("👤 User: {$review->user_name}");
            $this->command->newLine();
        }
    }
}
