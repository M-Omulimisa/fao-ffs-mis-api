<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Exception;

class AIReviewGeneratorService
{
    /**
     * Ugandan context phrases and words
     */
    private $ugandanContext = [
        'delivery_terms' => ['delivered quickly', 'fast delivery', 'good delivery', 'arrived on time', 'prompt delivery'],
        'price_terms' => ['good value', 'fair price', 'reasonable price', 'worth the money', 'affordable'],
        'quality_terms' => ['good quality', 'excellent quality', 'solid build', 'durable', 'long-lasting'],
        'satisfaction' => ['satisfied', 'happy', 'pleased', 'impressed', 'recommend'],
        'local_terms' => ['in Kampala', 'here in Uganda', 'in Entebbe', 'locally', 'within Uganda'],
        'currency' => 'UGX',
        'greetings' => ['Thanks', 'Webale', 'Asante', 'Good'],
    ];

    /**
     * Product-specific review templates organized by rating
     */
    private $reviewTemplates = [
        5 => [
            'Excellent {product_type}! {quality_praise} {delivery_comment} {recommendation}',
            '{greeting}! This {product_name} is amazing. {feature_highlight} {satisfaction_statement} {price_comment}',
            'Perfect {product_type}! {performance_comment} {durability_comment} Will definitely buy again.',
            'Outstanding quality! {specific_feature} {local_context} Highly recommend to everyone.',
            '{product_name} exceeded my expectations. {technical_comment} {value_statement} 5 stars!',
        ],
        4 => [
            'Very good {product_type}. {positive_feature} {minor_concern} Overall satisfied with purchase.',
            'Good quality {product_name}. {performance_comment} {small_improvement} Worth buying.',
            'Great {product_type}! {feature_highlight} {slight_issue} Still recommend it.',
            'Solid {product_name}. {quality_comment} {delivery_comment} Happy customer.',
            'Nice {product_type}. {technical_comment} {minor_suggestion} Good value for money.',
        ],
        3 => [
            'Decent {product_type}. {average_comment} {mixed_feedback} Okay for the price.',
            '{product_name} is alright. {some_positives} {some_concerns} Average experience.',
            'Fair {product_type}. {basic_functionality} {improvement_areas} Not bad overall.',
            'Acceptable quality. {meets_expectations} {could_be_better} Reasonable purchase.',
            'Good enough {product_name}. {works_fine} {some_limitations} Fair deal.',
        ]
    ];

    /**
     * Feature highlights based on product type
     */
    private $featureHighlights = [
        'phone' => ['battery life is great', 'camera quality is good', 'display is clear', 'performance is smooth', 'storage is adequate'],
        'electronics' => ['works perfectly', 'easy to use', 'good build quality', 'reliable performance', 'value for money'],
        'default' => ['quality is good', 'works as expected', 'good value', 'satisfied with purchase', 'meets requirements']
    ];

    /**
     * Generate AI reviews for a batch of products
     */
    public function generateReviewsForBatch(array $productIds, int $reviewsPerProduct = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($productIds as $productId) {
            try {
                $reviewCount = $reviewsPerProduct ?? rand(6, 12);
                $generated = $this->generateReviewsForProduct($productId, $reviewCount);
                
                if ($generated > 0) {
                    $results['success'] += $generated;
                    Log::info("Generated {$generated} reviews for product {$productId}");
                } else {
                    $results['failed']++;
                    $results['errors'][] = "No reviews generated for product {$productId}";
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Product {$productId}: " . $e->getMessage();
                Log::error("Failed to generate reviews for product {$productId}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Generate reviews for a specific product
     */
    public function generateReviewsForProduct(int $productId, int $count = 8): int
    {
        $product = Product::find($productId);
        if (!$product) {
            throw new Exception("Product not found: {$productId}");
        }

        // Get available users (excluding those who already reviewed this product)
        $existingUserIds = Review::where('product_id', $productId)->pluck('user_id')->toArray();
        $availableUsers = User::whereNotIn('id', $existingUserIds)->inRandomOrder()->take($count)->get();

        if ($availableUsers->count() < $count) {
            $count = $availableUsers->count();
        }

        $generatedCount = 0;

        foreach ($availableUsers as $user) {
            try {
                $review = $this->generateSingleReview($product, $user);
                if ($review) {
                    $generatedCount++;
                }
            } catch (Exception $e) {
                Log::error("Failed to generate review for product {$productId}, user {$user->id}: " . $e->getMessage());
            }
        }

        return $generatedCount;
    }

    /**
     * Generate a single review for a product and user
     */
    private function generateSingleReview(Product $product, User $user): ?Review
    {
        // Generate rating (3-5 stars with bias toward higher ratings)
        $rating = $this->generateRating();
        
        // Generate contextual comment
        $comment = $this->generateContextualComment($product, $rating);
        
        // Validate word count (30-70 words)
        if (!$this->validateWordCount($comment)) {
            $comment = $this->adjustWordCount($comment, $product, $rating);
        }

        try {
            return Review::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'rating' => $rating,
                'comment' => $comment,
                'created_at' => $this->randomRecentDate(),
            ]);
        } catch (Exception $e) {
            Log::error("Failed to create review: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate rating with bias toward 4-5 stars
     */
    private function generateRating(): int
    {
        $weights = [3 => 15, 4 => 40, 5 => 45]; // 15% for 3 stars, 40% for 4 stars, 45% for 5 stars
        $random = rand(1, 100);
        
        if ($random <= 15) return 3;
        if ($random <= 55) return 4;
        return 5;
    }

    /**
     * Generate contextual comment based on product and rating
     */
    private function generateContextualComment(Product $product, int $rating): string
    {
        // Analyze product to determine type and features
        $productType = $this->determineProductType($product);
        $features = $this->extractProductFeatures($product);
        
        // Select appropriate template
        $templates = $this->reviewTemplates[$rating];
        $template = $templates[array_rand($templates)];
        
        // Replace placeholders with contextual content
        $comment = $this->fillTemplate($template, $product, $productType, $features, $rating);
        
        return $comment;
    }

    /**
     * Determine product type from name and description
     */
    private function determineProductType(Product $product): string
    {
        $name = strtolower($product->name);
        $description = strtolower($product->description ?? '');
        
        if (str_contains($name, 'phone') || str_contains($name, 'smartphone') || str_contains($description, 'android') || str_contains($description, 'ios')) {
            return 'phone';
        }
        
        if (str_contains($name, 'laptop') || str_contains($name, 'computer')) {
            return 'laptop';
        }
        
        if (str_contains($name, 'tv') || str_contains($name, 'television')) {
            return 'TV';
        }
        
        return 'product';
    }

    /**
     * Extract key features from product
     */
    private function extractProductFeatures(Product $product): array
    {
        $features = [];
        $text = strtolower($product->name . ' ' . ($product->description ?? ''));
        
        // Battery
        if (preg_match('/(\d+)mah/', $text, $matches)) {
            $features['battery'] = $matches[1] . 'mAh battery';
        }
        
        // RAM
        if (preg_match('/(\d+)gb ram/', $text, $matches)) {
            $features['ram'] = $matches[1] . 'GB RAM';
        }
        
        // Storage
        if (preg_match('/(\d+)gb (rom|storage)/', $text, $matches)) {
            $features['storage'] = $matches[1] . 'GB storage';
        }
        
        // Display
        if (preg_match('/(\d+\.?\d*)"/', $text, $matches)) {
            $features['display'] = $matches[1] . '" display';
        }
        
        // Camera
        if (preg_match('/(\d+)mp/', $text, $matches)) {
            $features['camera'] = $matches[1] . 'MP camera';
        }
        
        return $features;
    }

    /**
     * Fill template with contextual data
     */
    private function fillTemplate(string $template, Product $product, string $productType, array $features, int $rating): string
    {
        $replacements = [
            '{product_type}' => $productType,
            '{product_name}' => $this->getShortProductName($product->name),
            '{greeting}' => $this->ugandanContext['greetings'][array_rand($this->ugandanContext['greetings'])],
            '{quality_praise}' => $this->ugandanContext['quality_terms'][array_rand($this->ugandanContext['quality_terms'])],
            '{delivery_comment}' => $this->ugandanContext['delivery_terms'][array_rand($this->ugandanContext['delivery_terms'])],
            '{recommendation}' => $this->ugandanContext['satisfaction'][array_rand($this->ugandanContext['satisfaction'])],
            '{feature_highlight}' => $this->getFeatureHighlight($features, $productType),
            '{satisfaction_statement}' => 'Very ' . $this->ugandanContext['satisfaction'][array_rand($this->ugandanContext['satisfaction'])],
            '{price_comment}' => $this->ugandanContext['price_terms'][array_rand($this->ugandanContext['price_terms'])],
            '{performance_comment}' => $this->getPerformanceComment($productType),
            '{durability_comment}' => 'Seems durable and well-built.',
            '{specific_feature}' => $this->getSpecificFeature($features),
            '{local_context}' => $this->ugandanContext['local_terms'][array_rand($this->ugandanContext['local_terms'])],
            '{technical_comment}' => $this->getTechnicalComment($features),
            '{value_statement}' => 'Great value for money',
            '{positive_feature}' => $this->getPositiveFeature($features, $productType),
            '{minor_concern}' => $this->getMinorConcern($rating),
            '{small_improvement}' => 'Could be slightly improved but good overall.',
            '{slight_issue}' => $this->getSlightIssue($rating),
            '{quality_comment}' => $this->ugandanContext['quality_terms'][array_rand($this->ugandanContext['quality_terms'])],
            '{minor_suggestion}' => 'Minor improvements possible.',
            '{average_comment}' => 'Does what it should.',
            '{mixed_feedback}' => 'Some good points, some not so much.',
            '{some_positives}' => 'Has some good features.',
            '{some_concerns}' => 'A few concerns but manageable.',
            '{basic_functionality}' => 'Basic functionality works fine.',
            '{improvement_areas}' => 'Room for improvement.',
            '{meets_expectations}' => 'Meets basic expectations.',
            '{could_be_better}' => 'Could be better in some areas.',
            '{works_fine}' => 'Works fine for basic needs.',
            '{some_limitations}' => 'Has some limitations.'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Get short product name for natural flow
     */
    private function getShortProductName(string $fullName): string
    {
        $words = explode(' ', $fullName);
        return implode(' ', array_slice($words, 0, 3));
    }

    /**
     * Get feature highlight based on available features
     */
    private function getFeatureHighlight(array $features, string $productType): string
    {
        if (!empty($features)) {
            $feature = array_rand($features);
            return 'The ' . $features[$feature] . ' is impressive.';
        }
        
        $highlights = $this->featureHighlights[$productType] ?? $this->featureHighlights['default'];
        return ucfirst($highlights[array_rand($highlights)]) . '.';
    }

    /**
     * Get performance comment based on product type
     */
    private function getPerformanceComment(string $productType): string
    {
        $comments = [
            'phone' => 'Runs smoothly without lag.',
            'laptop' => 'Good performance for daily tasks.',
            'default' => 'Performance is reliable.'
        ];
        
        return $comments[$productType] ?? $comments['default'];
    }

    /**
     * Get specific feature mention
     */
    private function getSpecificFeature(array $features): string
    {
        if (!empty($features)) {
            $feature = array_rand($features);
            return 'The ' . $features[$feature] . ' works great.';
        }
        
        return 'The build quality is solid.';
    }

    /**
     * Get technical comment based on features
     */
    private function getTechnicalComment(array $features): string
    {
        if (isset($features['ram']) && isset($features['storage'])) {
            return 'Good specs with ' . $features['ram'] . ' and ' . $features['storage'] . '.';
        }
        
        if (isset($features['battery'])) {
            return 'The ' . $features['battery'] . ' lasts well.';
        }
        
        return 'Technical specifications are adequate.';
    }

    /**
     * Get positive feature for 4-star reviews
     */
    private function getPositiveFeature(array $features, string $productType): string
    {
        if (!empty($features)) {
            $feature = array_rand($features);
            return 'The ' . $features[$feature] . ' is good.';
        }
        
        return 'Main features work well.';
    }

    /**
     * Get minor concern for 4-star reviews
     */
    private function getMinorConcern(int $rating): string
    {
        if ($rating === 4) {
            $concerns = [
                'Could be slightly faster.',
                'Minor design improvements needed.',
                'Small packaging issue.',
                'Delivery took a bit longer.'
            ];
            return $concerns[array_rand($concerns)];
        }
        
        return '';
    }

    /**
     * Get slight issue for 4-star reviews
     */
    private function getSlightIssue(int $rating): string
    {
        if ($rating === 4) {
            return 'One small issue but nothing major.';
        }
        
        return '';
    }

    /**
     * Validate word count (30-70 words)
     */
    private function validateWordCount(string $text): bool
    {
        $wordCount = str_word_count($text);
        return $wordCount >= 30 && $wordCount <= 70;
    }

    /**
     * Adjust word count to fit requirements
     */
    private function adjustWordCount(string $text, Product $product, int $rating): string
    {
        $wordCount = str_word_count($text);
        
        if ($wordCount < 30) {
            // Add more content
            $additions = [
                ' Delivery was prompt and packaging was good.',
                ' Would definitely recommend to friends and family.',
                ' The price is reasonable for the quality offered.',
                ' Customer service was helpful during purchase.',
                ' Overall experience has been positive so far.'
            ];
            
            while (str_word_count($text) < 30 && !empty($additions)) {
                $text .= array_shift($additions);
            }
        }
        
        if (str_word_count($text) > 70) {
            // Trim to fit
            $words = explode(' ', $text);
            $text = implode(' ', array_slice($words, 0, 65)) . '.';
        }
        
        return $text;
    }

    /**
     * Generate random recent date for review
     */
    private function randomRecentDate(): string
    {
        $daysAgo = rand(1, 90); // Reviews from last 90 days
        return now()->subDays($daysAgo)->format('Y-m-d H:i:s');
    }
}
