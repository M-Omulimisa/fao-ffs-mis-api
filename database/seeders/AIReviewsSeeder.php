<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\AIReviewGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AIReviewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🤖 Starting AI Review Generation via Seeder...');
        
        $aiService = new AIReviewGeneratorService();
        
        // Configuration
        // Use optimal batch size for large-scale generation
        $batchSize = 200; // For ~1000 products, 200 is optimal
        $reviewsPerProduct = 8; // 8 reviews per product on average
        
        $stats = [
            'products_processed' => 0,
            'reviews_created' => 0,
            'batches' => 0,
            'errors' => 0
        ];
        
        $startTime = now();
        
        // Process products in batches
        Product::chunk($batchSize, function ($products) use ($aiService, &$stats) {
            $stats['batches']++;
            $batchStart = now();
            $this->command->info("Processing batch {$stats['batches']} with {$products->count()} products...");
            foreach ($products as $product) {
                try {
                    // Generate 6-12 reviews per product (random within range)
                    $reviewCount = rand(6, 12);
                    $generated = $aiService->generateReviewsForProduct($product->id, $reviewCount);
                    $stats['products_processed']++;
                    $stats['reviews_created'] += $generated;
                    if ($generated > 0) {
                        $this->command->line("  ✅ Product #{$product->id}: {$generated} reviews generated");
                    } else {
                        $this->command->line("  ⚠️  Product #{$product->id}: No reviews generated");
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->command->error("  ❌ Product #{$product->id}: " . $e->getMessage());
                    Log::error("AI Review Generation failed for product {$product->id}: " . $e->getMessage());
                }
            }
            $batchTime = $batchStart->diffInSeconds(now());
            $this->command->info("Batch completed in {$batchTime} seconds\n");
            // Small delay to prevent overwhelming the system
            sleep(1);
        });
        
        $totalTime = $startTime->diffInMinutes(now());
        
        // Display final statistics
        $this->command->info("🎉 AI Review Generation Completed!");
        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Total Runtime', $totalTime . ' minutes'],
                ['Batches Processed', $stats['batches']],
                ['Products Processed', $stats['products_processed']],
                ['Reviews Created', $stats['reviews_created']],
                ['Errors', $stats['errors']],
                ['Avg Reviews/Product', $stats['products_processed'] > 0 ? round($stats['reviews_created'] / $stats['products_processed'], 1) : 0],
                ['Reviews/Minute', $totalTime > 0 ? round($stats['reviews_created'] / $totalTime, 1) : 0],
            ]
        );
        
        // Log final stats
        Log::info("AI Review Generation completed", $stats);
    }
}
