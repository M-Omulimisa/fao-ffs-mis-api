<?php

echo "=== TESTING WITH IPN REGISTRATION FIRST ===\n";
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

$auth_data_response = json_decode($auth_response, true);

if ($auth_http_code == 200 && isset($auth_data_response['token'])) {
    $token = $auth_data_response['token'];
    echo "✅ SUCCESS: Authentication token received!\n\n";
    
    echo "Step 2: Registering IPN URL\n";
    echo "===========================\n";
    
    $ipn_data = json_encode([
        "url" => "https://blitxpress.com/api/pesapal/ipn",
        "ipn_notification_type" => "POST"
    ]);
    
    echo "IPN Data: {$ipn_data}\n\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$base_url}/api/URLSetup/RegisterIPN");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $ipn_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        "Authorization: Bearer {$token}"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $ipn_response = curl_exec($ch);
    $ipn_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "IPN Registration HTTP Status: {$ipn_http_code}\n";
    echo "IPN Registration Response: {$ipn_response}\n\n";
    
    $ipn_data_response = json_decode($ipn_response, true);
    $notification_id = null;
    
    if ($ipn_http_code == 200 && isset($ipn_data_response['ipn_id'])) {
        $notification_id = $ipn_data_response['ipn_id'];
        echo "✅ SUCCESS: IPN registered with ID: {$notification_id}\n\n";
    } else {
        echo "⚠️  IPN registration failed, proceeding without notification_id\n\n";
    }
    
    echo "Step 3: Testing Payment Initialization\n";
    echo "=====================================\n";
    
    $payment_payload = [
        "id" => "ORDER_99999_" . time(),
        "currency" => "UGX",
        "amount" => 50000.00,
        "description" => "Test payment with IPN registration",
        "callback_url" => "https://blitxpress.com/payment-callback",
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
    ];
    
    // Only add notification_id if we got one from IPN registration
    if ($notification_id) {
        $payment_payload["notification_id"] = $notification_id;
        echo "Using notification_id: {$notification_id}\n";
    } else {
        echo "No notification_id - proceeding without IPN\n";
    }
    
    $payment_data = json_encode($payment_payload, JSON_PRETTY_PRINT);
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
        echo "This is the error response:\n";
        echo $payment_response . "\n";
    }
    
} else {
    echo "❌ FAILED: Authentication failed\n";
    echo "Response: {$auth_response}\n";
}

echo "\n=== TEST COMPLETED ===\n";
