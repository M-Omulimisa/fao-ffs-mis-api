<?php

echo "=== TESTING WITH REAL LIVE PESAPAL KEYS ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Phone: +256783204665\n";
echo "Amount: UGX 50,000\n";
echo "======================================\n\n";

// Test authentication first
$consumer_key = "lRkoOQIl7QQc17Ej//RtpRfrq4Z9qzl/";
$consumer_secret = "AlcvoKfr+Al2nCL9u0AH/eASyTk=";
$base_url = "https://pay.pesapal.com/v3";

echo "Step 1: Testing Authentication\n";
echo "==============================\n";
echo "URL: {$base_url}/api/Auth/RequestToken\n";
echo "Consumer Key: {$consumer_key}\n";
echo "Consumer Secret: {$consumer_secret}\n\n";

$auth_data = json_encode([
    "consumer_key" => $consumer_key,
    "consumer_secret" => $consumer_secret
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$base_url}/api/Auth/RequestToken");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$auth_response = curl_exec($ch);
$auth_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$auth_http_code}\n";
echo "Response: {$auth_response}\n";

$auth_data_response = json_decode($auth_response, true);

if ($auth_http_code == 200 && isset($auth_data_response['token'])) {
    $token = $auth_data_response['token'];
    echo "✅ SUCCESS: Authentication token received!\n\n";
    
    echo "Step 2: Testing Payment Initialization\n";
    echo "=====================================\n";
    echo "URL: {$base_url}/api/Transactions/SubmitOrderRequest\n";
    echo "Authorization: Bearer {$token}\n\n";
    
    $payment_data = json_encode([
        "id" => "ORDER_99999_" . time(),
        "currency" => "UGX",
        "amount" => 50000.00,
        "description" => "Test payment with real live keys - NO IPN",
        "callback_url" => "http://localhost:8888/blitxpress/payment-callback",
        "billing_address" => [
            "email_address" => "test@blitxpress.com",
            "phone_number" => "+256783204665",
            "country_code" => "UG",
            "first_name" => "Test Customer",
            "last_name" => "",
            "line_1" => "Test Address, Kampala",
            "line_2" => "",
            "city" => "",
            "state" => "",
            "postal_code" => "",
            "zip_code" => ""
        ]
    ], JSON_PRETTY_PRINT);
    
    echo "Payment Data: {$payment_data}\n\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$base_url}/api/Transactions/SubmitOrderRequest");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payment_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        "Authorization: Bearer {$token}"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $payment_response = curl_exec($ch);
    $payment_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: {$payment_http_code}\n";
    echo "Response: {$payment_response}\n";
    
    $payment_data_response = json_decode($payment_response, true);
    
    if ($payment_http_code == 200 && isset($payment_data_response['order_tracking_id'])) {
        echo "✅ SUCCESS: Payment initialized!\n";
        echo "Tracking ID: {$payment_data_response['order_tracking_id']}\n";
        echo "Redirect URL: {$payment_data_response['redirect_url']}\n";
    } else {
        echo "❌ FAILED: Payment initialization failed\n";
        echo "This is the error we need to send to Pesapal:\n";
        echo "Response: {$payment_response}\n";
    }
    
} else {
    echo "❌ FAILED: Authentication failed\n";
    echo "This is the error we need to send to Pesapal:\n";
    echo "Response: {$auth_response}\n";
}

echo "\n=== TEST COMPLETED ===\n";
