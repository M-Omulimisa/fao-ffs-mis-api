<?php

echo "Starting Pesapal test...\n";

try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Bootstrap Laravel
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "Laravel bootstrapped successfully...\n";
    
    echo "Classes loaded...\n";
    
    // Create the service
    $pesapalService = new App\Services\PesapalService();
    echo "PesapalService created...\n";
    
    // Get order
    $order = App\Models\Order::find(99999);
    if (!$order) {
        echo "Order not found, creating new one...\n";
        $order = new App\Models\Order();
        $order->id = 99999;
        $order->order_total = 50000.00;
        $order->mail = 'test@blitxpress.com';
        $order->customer_name = 'Test Customer';
        $order->customer_phone_number_1 = '+256783204665';
        $order->customer_address = 'Test Address, Kampala';
        $order->save();
    }
    
    echo "Order ready: ID {$order->id}, Total: {$order->order_total}\n";
    echo "Testing Pesapal payment initialization...\n";
    
    // Try the payment
    $response = $pesapalService->submitOrderRequest($order, null, 'http://localhost:8888/blitxpress/payment-callback');
    
    echo "SUCCESS! Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
