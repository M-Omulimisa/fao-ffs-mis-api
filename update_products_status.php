<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

// Update all products with status = 0 or null to status = 1
$updated = Product::where(function($query) {
    $query->where('status', '0')
          ->orWhereNull('status');
})->update(['status' => 1]);

echo "Updated $updated products to active status (status = 1)\n";

// Show products for user 1
$userProducts = Product::where('user', 1)->get(['id', 'name', 'status', 'user']);
echo "\nUser 1 products:\n";
foreach ($userProducts as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Status: {$product->status}, User: {$product->user}\n";
}
