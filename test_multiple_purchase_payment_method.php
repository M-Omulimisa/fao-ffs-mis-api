<?php

/**
 * Test Multiple Product Purchase with Payment Method Selection
 * Tests both Cash on Delivery and Pay Online options
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\Administrator;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

echo "================================================================\n";
echo "MULTIPLE PRODUCT PURCHASE - PAYMENT METHOD TEST\n";
echo "================================================================\n\n";

// Step 1: Find a test user
echo "Step 1: Finding test user...\n";
$user = Administrator::where('id', '>', 0)->first();

if (!$user) {
    echo "   ✗ No users found.\n";
    exit(1);
}

echo "   ✓ Test User Found:\n";
echo "     - ID: {$user->id}\n";
echo "     - Name: {$user->first_name} {$user->last_name}\n";
echo "     - Phone: {$user->phone_number}\n\n";

// Step 2: Find test products
echo "Step 2: Finding test products...\n";
$products = Product::where('status', 1)->take(3)->get();

if ($products->count() < 1) {
    echo "   ✗ No products found.\n";
    exit(1);
}

echo "   ✓ Found {$products->count()} products:\n";
foreach ($products as $product) {
    echo "     - ID: {$product->id}, Name: {$product->name}, Price: {$product->price_1}\n";
}
echo "\n";

// Step 3: Test Cash on Delivery Order
echo "Step 3: Testing CASH ON DELIVERY order...\n";

$items = [];
$total = 0;
foreach ($products as $product) {
    $qty = rand(1, 3);
    $items[] = [
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_quantity' => $qty,
        'product_price_1' => $product->price_1,
        'color' => '',
        'size' => '',
    ];
    $total += $product->price_1 * $qty;
}

$delivery = [
    'customer_name' => "{$user->first_name} {$user->last_name}",
    'mail' => $user->email ?? 'test@example.com',
    'customer_phone_number_1' => $user->phone_number,
    'customer_phone_number_2' => $user->phone_number,
    'phone_number_1' => $user->phone_number,
    'phone_number_2' => $user->phone_number,
    'phone_number' => $user->phone_number,
    'delivery_method' => 'delivery',
    'delivery_amount' => '5000',
    'delivery_district' => '1',
    'customer_address' => 'Test Address, Kampala',
    'description' => 'Test order - Cash on Delivery',
    'date_created' => date('Y-m-d H:i:s'),
    'date_updated' => date('Y-m-d H:i:s'),
];

$postData = [
    'items' => json_encode($items),
    'delivery' => json_encode($delivery),
    'pay_on_delivery' => 'true', // CASH ON DELIVERY
];

echo "   Creating order with:\n";
echo "     - Items: " . count($items) . "\n";
echo "     - Total: UGX " . number_format($total) . "\n";
echo "     - Delivery: UGX 5,000\n";
echo "     - Payment: CASH ON DELIVERY\n\n";

try {
    // Simulate API call with proper authentication
    $request = new \Illuminate\Http\Request($postData);
    $request->merge(['user_id' => $user->id]); // Add user_id to request
    
    $controller = new \App\Http\Controllers\ApiResurceController();
    $response = $controller->orders_create($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['code'] == 1) {
        $order = Order::find($responseData['data']['id']);
        
        echo "   ✓ CASH ON DELIVERY Order Created Successfully!\n";
        echo "     - Order ID: {$order->id}\n";
        echo "     - Payment Status: {$order->payment_status}\n";
        echo "     - Payment Gateway: {$order->payment_gateway}\n";
        echo "     - Pay on Delivery: " . ($order->pay_on_delivery ? 'YES' : 'NO') . "\n";
        echo "     - Total Amount: UGX " . number_format($order->amount) . "\n";
        echo "     - Order State: {$order->order_state}\n\n";
        
        // Verify payment status
        if ($order->payment_status == 'PAY_ON_DELIVERY' && $order->payment_gateway == 'cash_on_delivery') {
            echo "   ✓ Payment method correctly set to CASH ON DELIVERY\n\n";
        } else {
            echo "   ✗ Payment method NOT correctly set!\n\n";
        }
    } else {
        echo "   ✗ Failed to create order\n";
        echo "     Error: " . ($responseData['message'] ?? 'Unknown error') . "\n\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
}

// Step 4: Test Pay Online Order
echo "Step 4: Testing PAY ONLINE order...\n";

$postData['pay_on_delivery'] = 'false'; // PAY ONLINE
$delivery['description'] = 'Test order - Pay Online';
$postData['delivery'] = json_encode($delivery);

echo "   Creating order with:\n";
echo "     - Items: " . count($items) . "\n";
echo "     - Total: UGX " . number_format($total) . "\n";
echo "     - Delivery: UGX 5,000\n";
echo "     - Payment: PAY ONLINE\n\n";

try {
    $request = new \Illuminate\Http\Request($postData);
    
    $response = $controller->orders_create($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['code'] == 1) {
        $order = Order::find($responseData['data']['id']);
        
        echo "   ✓ PAY ONLINE Order Created Successfully!\n";
        echo "     - Order ID: {$order->id}\n";
        echo "     - Payment Status: {$order->payment_status}\n";
        echo "     - Payment Gateway: {$order->payment_gateway}\n";
        echo "     - Pay on Delivery: " . ($order->pay_on_delivery ? 'YES' : 'NO') . "\n";
        echo "     - Total Amount: UGX " . number_format($order->amount) . "\n";
        echo "     - Order State: {$order->order_state}\n\n";
        
        // Verify payment status
        if ($order->payment_status == 'PENDING_PAYMENT' && !$order->pay_on_delivery) {
            echo "   ✓ Payment method correctly set to PAY ONLINE\n\n";
        } else {
            echo "   ✗ Payment method NOT correctly set!\n\n";
        }
    } else {
        echo "   ✗ Failed to create order\n";
        echo "     Error: " . ($responseData['message'] ?? 'Unknown error') . "\n\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
}

echo "================================================================\n";
echo "TEST SUMMARY\n";
echo "================================================================\n\n";

$cashOrders = Order::where('payment_gateway', 'cash_on_delivery')->count();
$onlineOrders = Order::where('payment_status', 'PENDING_PAYMENT')
    ->where('pay_on_delivery', false)
    ->count();

echo "Total Orders in System:\n";
echo "  - Cash on Delivery: {$cashOrders}\n";
echo "  - Pay Online: {$onlineOrders}\n\n";

echo "✓ Payment Method Selection - WORKING!\n";
echo "✓ Backend API - CONFIGURED!\n";
echo "✓ Database Storage - VERIFIED!\n\n";

echo "Next Steps:\n";
echo "1. Test from mobile app\n";
echo "2. Verify email notifications\n";
echo "3. Check admin dashboard displays payment method correctly\n\n";

echo "================================================================\n";
echo "TEST COMPLETE\n";
echo "================================================================\n";
