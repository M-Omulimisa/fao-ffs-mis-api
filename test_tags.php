<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "Testing enhanced search with tags...\n";

// Test search for 'electronics'
$products = Product::enhancedSearch('electronics')->take(3)->get();
echo "Search for 'electronics':\n";
foreach($products as $product) {
    echo "  - {$product->name} (tags: {$product->tags})\n";
}

// Test tag counting
$products = Product::whereNotNull('tags')->where('tags', '!=', '')->get(['tags']);
echo "\nTotal products with tags: " . $products->count() . "\n";

if($products->count() > 0) {
    $tagCounts = [];
    foreach($products as $product) {
        $tags = array_map('trim', explode(',', $product->tags));
        foreach($tags as $tag) {
            if(!empty($tag)) {
                $tag = strtolower($tag);
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }
    }
    
    arsort($tagCounts);
    echo "Top 10 tags:\n";
    $i = 0;
    foreach($tagCounts as $tag => $count) {
        if($i++ >= 10) break;
        echo "  - $tag: $count products\n";
    }
}

echo "Test completed!\n";
