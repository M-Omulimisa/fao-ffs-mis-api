<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\PesapalLog;

/**
 * Centralized Pesapal API Client
 * Based on the working test from September 13, 2025
 * 
 * This is the ONLY class that should communicate with Pesapal API
 * All other services should use this class
 */
class PesapalApiClient
{
    private $consumerKey;
    private $consumerSecret;
    private $baseUrl;
    private $ipnUrl;
    private $callbackUrl;

    public function __construct()
    {
        $this->consumerKey = env('PESAPAL_CONSUMER_KEY');
        $this->consumerSecret = env('PESAPAL_CONSUMER_SECRET');
        $this->baseUrl = env('PESAPAL_PRODUCTION_URL');
        $this->ipnUrl = env('PESAPAL_IPN_URL');
        $this->callbackUrl = env('PESAPAL_CALLBACK_URL');
    }

    /**
     * 🔐 STEP 1: Authenticate with Pesapal
     * Returns: JWT token for API calls
     */
    public function authenticate()
    {
        $logData = [
            'test_type' => 'api_call',
            'action' => 'authenticate',
            'method' => 'POST',
            'endpoint' => $this->baseUrl . '/api/Auth/RequestToken',
            'api_environment' => 'PRODUCTION',
        ];

        $log = PesapalLog::logTest($logData);

        try {
            // Check cache first (tokens are valid for 5 minutes)
            $cacheKey = 'pesapal_token_' . md5($this->consumerKey);
            $cachedToken = Cache::get($cacheKey);
            
            if ($cachedToken) {
                Log::info('Pesapal: Using cached authentication token');
                return $cachedToken;
            }

            $payload = [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api/Auth/RequestToken');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            curl_close($ch);

            $data = json_decode($response, true);

            // Complete log entry
            PesapalLog::completeLog($log->id, [
                'success' => $httpCode === 200 && isset($data['token']),
                'status_code' => $httpCode,
                'response_data' => $data,
                'response_time_ms' => $responseTime,
                'message' => $httpCode === 200 ? 'Authentication successful' : 'Authentication failed',
                'error_message' => $httpCode !== 200 ? ($data['error']['message'] ?? 'HTTP ' . $httpCode) : null
            ]);

            if ($httpCode === 200 && isset($data['token'])) {
                $token = $data['token'];
                
                // Cache token for 4 minutes (expires in 5)
                Cache::put($cacheKey, $token, 240);
                
                Log::info('Pesapal: Authentication successful', [
                    'expires_at' => $data['expiryDate'] ?? 'unknown'
                ]);
                
                return $token;
            }

            throw new \Exception('Authentication failed: ' . ($data['error']['message'] ?? 'HTTP ' . $httpCode));

        } catch (\Exception $e) {
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 🔗 STEP 2: Register IPN URL
     * Returns: IPN ID for payment notifications
     */
    public function registerIpnUrl($ipnUrl = null)
    {
        $ipnUrl = $ipnUrl ?: $this->ipnUrl;
        
        $logData = [
            'test_type' => 'api_call',
            'action' => 'register_ipn',
            'method' => 'POST',
            'endpoint' => $this->baseUrl . '/api/URLSetup/RegisterIPN',
            'api_environment' => 'PRODUCTION',
            'description' => 'Register IPN URL: ' . $ipnUrl,
        ];

        $log = PesapalLog::logTest($logData);

        try {
            $token = $this->authenticate();

            $payload = [
                'url' => $ipnUrl,
                'ipn_notification_type' => 'POST'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api/URLSetup/RegisterIPN');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            curl_close($ch);

            $data = json_decode($response, true);

            // Complete log entry
            PesapalLog::completeLog($log->id, [
                'success' => $httpCode === 200 && isset($data['ipn_id']),
                'status_code' => $httpCode,
                'response_data' => $data,
                'response_time_ms' => $responseTime,
                'message' => $httpCode === 200 ? 'IPN registration successful' : 'IPN registration failed',
                'error_message' => $httpCode !== 200 ? ($data['error']['message'] ?? 'HTTP ' . $httpCode) : null
            ]);

            if ($httpCode === 200 && isset($data['ipn_id'])) {
                Log::info('Pesapal: IPN URL registered successfully', [
                    'ipn_id' => $data['ipn_id'],
                    'url' => $ipnUrl
                ]);
                
                return $data['ipn_id'];
            }

            throw new \Exception('IPN registration failed: ' . ($data['error']['message'] ?? 'HTTP ' . $httpCode));

        } catch (\Exception $e) {
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 💳 STEP 3: Initialize Payment
     * This is the main method that creates a payment request
     * Returns: array with order_tracking_id and redirect_url
     */
    public function initializePayment($orderData)
    {
        $logData = [
            'test_type' => 'payment_initialization',
            'action' => 'submit_order_request',
            'method' => 'POST',
            'endpoint' => $this->baseUrl . '/api/Transactions/SubmitOrderRequest',
            'order_id' => $orderData['order_id'] ?? null,
            'merchant_reference' => $orderData['merchant_reference'] ?? null,
            'amount' => $orderData['amount'] ?? null,
            'currency' => $orderData['currency'] ?? 'UGX',
            'customer_name' => $orderData['customer_name'] ?? null,
            'customer_email' => $orderData['customer_email'] ?? null,
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'description' => $orderData['description'] ?? null,
            'api_environment' => 'PRODUCTION',
        ];

        $log = PesapalLog::logTest($logData);

        try {
            // Step 1: Get authentication token
            $token = $this->authenticate();

            // Step 2: Register IPN URL and get notification_id
            $notificationId = $this->registerIpnUrl();

            // Step 3: Prepare payment payload (EXACT format from working test)
            $payload = [
                'id' => $orderData['merchant_reference'],
                'currency' => $orderData['currency'] ?? 'UGX',
                'amount' => (float) $orderData['amount'],
                'description' => $orderData['description'],
                'callback_url' => $orderData['callback_url'] ?? $this->callbackUrl,
                'notification_id' => $notificationId,
                'billing_address' => [
                    'email_address' => $orderData['customer_email'],
                    'phone_number' => $orderData['customer_phone'],
                    'country_code' => 'UG',
                    'first_name' => $orderData['customer_name'],
                    'last_name' => '',
                    'line_1' => $orderData['customer_address'] ?? 'Kampala, Uganda',
                    'line_2' => '',
                    'city' => '',
                    'state' => '',
                    'postal_code' => '',
                    'zip_code' => ''
                ]
            ];

            // Log the complete request payload
            $log->update([
                'request_data' => [
                    'payload' => $payload,
                    'notification_id' => $notificationId,
                    'ipn_url' => $this->ipnUrl,
                    'callback_url' => $payload['callback_url']
                ]
            ]);

            // Step 4: Make payment request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api/Transactions/SubmitOrderRequest');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            curl_close($ch);

            $data = json_decode($response, true);

            // Complete log entry
            PesapalLog::completeLog($log->id, [
                'success' => $httpCode === 200 && isset($data['order_tracking_id']),
                'status_code' => $httpCode,
                'response_data' => $data,
                'response_time_ms' => $responseTime,
                'tracking_id' => $data['order_tracking_id'] ?? null,
                'message' => $httpCode === 200 ? 'Payment initialization successful' : 'Payment initialization failed',
                'error_message' => $httpCode !== 200 ? ($data['error']['message'] ?? 'HTTP ' . $httpCode) : null
            ]);

            if ($httpCode === 200 && isset($data['order_tracking_id']) && isset($data['redirect_url'])) {
                Log::info('Pesapal: Payment initialized successfully', [
                    'order_tracking_id' => $data['order_tracking_id'],
                    'merchant_reference' => $data['merchant_reference'],
                    'order_id' => $orderData['order_id']
                ]);

                return [
                    'success' => true,
                    'order_tracking_id' => $data['order_tracking_id'],
                    'merchant_reference' => $data['merchant_reference'],
                    'redirect_url' => $data['redirect_url'],
                    'notification_id' => $notificationId,
                    'status' => $data['status'],
                    'message' => 'Payment initialized successfully'
                ];
            }

            throw new \Exception('Payment initialization failed: ' . ($data['error']['message'] ?? 'Missing required fields in response'));

        } catch (\Exception $e) {
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'error_message' => $e->getMessage()
            ]);

            Log::error('Pesapal: Payment initialization failed', [
                'error' => $e->getMessage(),
                'order_id' => $orderData['order_id'] ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * 🔍 STEP 4: Check Payment Status
     * Returns: current payment status from Pesapal
     */
    public function checkPaymentStatus($orderTrackingId)
    {
        $logData = [
            'test_type' => 'api_call',
            'action' => 'check_payment_status',
            'method' => 'GET',
            'endpoint' => $this->baseUrl . '/api/Transactions/GetTransactionStatus',
            'tracking_id' => $orderTrackingId,
            'api_environment' => 'PRODUCTION',
        ];

        $log = PesapalLog::logTest($logData);

        try {
            $token = $this->authenticate();

            $url = $this->baseUrl . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . $orderTrackingId;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $startTime = microtime(true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            curl_close($ch);

            $data = json_decode($response, true);

            // Complete log entry
            PesapalLog::completeLog($log->id, [
                'success' => $httpCode === 200,
                'status_code' => $httpCode,
                'response_data' => $data,
                'response_time_ms' => $responseTime,
                'message' => $httpCode === 200 ? 'Status check successful' : 'Status check failed',
                'error_message' => $httpCode !== 200 ? ($data['error']['message'] ?? 'HTTP ' . $httpCode) : null
            ]);

            if ($httpCode === 200) {
                Log::info('Pesapal: Payment status retrieved', [
                    'order_tracking_id' => $orderTrackingId,
                    'status' => $data['status_code'] ?? 'unknown'
                ]);

                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            throw new \Exception('Status check failed: ' . ($data['error']['message'] ?? 'HTTP ' . $httpCode));

        } catch (\Exception $e) {
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 🛠️ Test Connection
     * Quick test to verify API credentials and connectivity
     */
    public function testConnection()
    {
        try {
            $token = $this->authenticate();
            return [
                'success' => true,
                'message' => 'Connection successful',
                'token_received' => !empty($token)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
