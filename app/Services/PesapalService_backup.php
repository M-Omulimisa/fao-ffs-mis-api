<?php

namespace App\Services;

use App\Config\PesapalConfig;
use App\Enums\PesapalPaymentStatus;
use App\Exceptions\PesapalException;
use App\Models\Order;
use App\Models\PesapalTransaction;
use App\Models\PesapalIpnLog;
use App\Models\PesapalLog;
use App\Services\PesapalApiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * PesapalService - Now uses centralized PesapalApiClient
 * All API communication goes through PesapalApiClient
 */
class PesapalService
{
    private $apiClient;

    public function __construct()
    {
        $this->apiClient = new PesapalApiClient();
    }

    /**
     * Submit order request to Pesapal
     * Now uses centralized API client
     */
    public function submitOrderRequest(Order $order, $notificationId = null, $callbackUrl = null)
    {
        try {
            // Generate unique merchant reference
            $merchantReference = 'ORDER_' . $order->id . '_' . time();

            // Validate amount for current environment
            $amount = (float) $order->order_total;
            \App\Config\PesapalProductionConfig::validateTransactionAmount($amount);

            // Prepare order data for centralized API client
            $orderData = [
                'order_id' => $order->id,
                'merchant_reference' => $merchantReference,
                'amount' => $amount,
                'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                'description' => 'Order #' . $order->id . ' payment',
                'customer_name' => $order->customer_name ?: ($order->customer->first_name ?? ''),
                'customer_email' => $order->mail ?: ($order->customer->email ?? ''),
                'customer_phone' => $order->customer_phone_number_1 ?: ($order->customer->phone_number ?? ''),
                'customer_address' => $order->customer_address ?: '',
                'callback_url' => $callbackUrl ?: env('PESAPAL_CALLBACK_URL')
            ];

            Log::info('Pesapal: Submitting order request via centralized API client', [
                'order_id' => $order->id,
                'merchant_reference' => $merchantReference,
                'amount' => $amount
            ]);

            // Use centralized API client
            $response = $this->apiClient->initializePayment($orderData);

            if ($response['success']) {
                // Create transaction record
                $transaction = PesapalTransaction::create([
                    'order_id' => $order->id,
                    'order_tracking_id' => $response['order_tracking_id'],
                    'merchant_reference' => $merchantReference,
                    'amount' => $amount,
                    'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                    'status' => 'PENDING',
                    'redirect_url' => $response['redirect_url'],
                    'callback_url' => $orderData['callback_url'],
                    'notification_id' => $response['notification_id'],
                    'description' => $orderData['description'],
                    'pesapal_response' => json_encode($response)
                ]);

                // Update order with Pesapal info
                $order->update([
                    'payment_gateway' => 'pesapal',
                    'pesapal_order_tracking_id' => $response['order_tracking_id'],
                    'pesapal_merchant_reference' => $merchantReference,
                    'pesapal_status' => 'PENDING',
                    'pesapal_redirect_url' => $response['redirect_url'],
                    'payment_status' => 'PENDING_PAYMENT'
                ]);

                Log::info('Pesapal: Order submitted successfully', [
                    'order_id' => $order->id,
                    'tracking_id' => $response['order_tracking_id']
                ]);

                return $response;
            }

            throw new \Exception($response['error'] ?? 'Payment initialization failed');

        } catch (\Exception $e) {
            Log::error('Pesapal: Order submission exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Check payment status
     * Now uses centralized API client
     */
    public function getTransactionStatus($orderTrackingId)
    {
        try {
            Log::info('Pesapal: Checking transaction status via centralized API client', [
                'order_tracking_id' => $orderTrackingId
            ]);

            $response = $this->apiClient->checkPaymentStatus($orderTrackingId);

            if ($response['success']) {
                Log::info('Pesapal: Transaction status retrieved successfully', [
                    'order_tracking_id' => $orderTrackingId,
                    'status' => $response['data']['status_code'] ?? 'unknown'
                ]);

                return $response['data'];
            }

            throw new \Exception($response['error'] ?? 'Status check failed');

        } catch (\Exception $e) {
            Log::error('Pesapal: Transaction status check failed', [
                'order_tracking_id' => $orderTrackingId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get authentication token
     * Now uses centralized API client
     */
    public function getAuthToken()
    {
        return $this->apiClient->authenticate();
    }

    /**
     * Register IPN URL
     * Now uses centralized API client
     */
    public function registerIpnUrl($ipnUrl = null, $notificationType = 'POST')
    {
        try {
            $ipnId = $this->apiClient->registerIpnUrl($ipnUrl);
            
            return [
                'ipn_id' => $ipnId,
                'url' => $ipnUrl ?: env('PESAPAL_IPN_URL'),
                'notification_type' => $notificationType
            ];
        } catch (\Exception $e) {
            Log::error('Pesapal: IPN registration failed', [
                'ipn_url' => $ipnUrl,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Test connection
     * Now uses centralized API client
     */
    public function testConnection()
    {
        return $this->apiClient->testConnection();
    }

    /**
     * Create simple payment for testing
     * Simplified method for test interface
     */
    public function createTestPayment($orderData)
    {
        try {
            // Use centralized API client directly
            $response = $this->apiClient->initializePayment($orderData);

            if ($response['success']) {
                Log::info('Pesapal: Test payment created successfully', [
                    'merchant_reference' => $orderData['merchant_reference'],
                    'tracking_id' => $response['order_tracking_id']
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Pesapal: Test payment creation failed', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
{
    private $consumerKey;
    private $consumerSecret;
    private $baseUrl;
    private $environment;
    private $token;
    private $tokenExpiresAt;

    public function __construct()
    {
        $this->consumerKey = PesapalConfig::getConsumerKey();
        $this->consumerSecret = PesapalConfig::getConsumerSecret();
        $this->environment = PesapalConfig::getEnvironment();
        $this->baseUrl = PesapalConfig::getBaseUrl();
        
        // Validate configuration
        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            throw PesapalException::configurationError('Consumer key and secret are required');
        }
    }

    /**
     * Get or refresh authentication token
     */
    public function getAuthToken()
    {
        // Check if we have a valid token
        if ($this->token && $this->tokenExpiresAt && Carbon::now()->isBefore($this->tokenExpiresAt)) {
            return $this->token;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/Auth/RequestToken', [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['token'])) {
                    $this->token = $data['token'];
                    // Token expires in 5 minutes, set expiry 30 seconds before
                    $this->tokenExpiresAt = Carbon::now()->addMinutes(4.5);
                    
                    Log::info('Pesapal: Authentication token obtained successfully');
                    return $this->token;
                }
            }

            Log::error('Pesapal: Authentication failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            
            throw new \Exception('Failed to authenticate with Pesapal: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Pesapal: Authentication exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Register IPN URL with Pesapal
     */
    public function registerIpnUrl($ipnUrl = null, $notificationType = 'POST')
    {
        if (!$ipnUrl) {
            $ipnUrl = env('PESAPAL_IPN_URL');
        }

        try {
            $token = $this->getAuthToken();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->post($this->baseUrl . '/URLSetup/RegisterIPN', [
                'url' => $ipnUrl,
                'ipn_notification_type' => $notificationType,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Pesapal: IPN URL registered successfully', $data);
                return $data;
            }

            Log::error('Pesapal: IPN registration failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            throw new \Exception('Failed to register IPN URL: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Pesapal: IPN registration exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Submit order request to Pesapal
     */
    public function submitOrderRequest(Order $order, $notificationId, $callbackUrl = null)
    {
        if (!$callbackUrl) {
            $callbackUrl = env('PESAPAL_CALLBACK_URL');
        }

        // Generate unique merchant reference
        $merchantReference = 'ORDER_' . $order->id . '_' . time();

        // Validate amount for current environment
        $amount = (float) $order->order_total;
        \App\Config\PesapalProductionConfig::validateTransactionAmount($amount);

        // 📝 START DETAILED LOGGING - Create initial log entry
        $logData = [
            'test_type' => 'api_call',
            'action' => 'submit_order_request',
            'method' => 'POST',
            'endpoint' => $this->baseUrl . '/Transactions/SubmitOrderRequest',
            'order_id' => $order->id,
            'merchant_reference' => $merchantReference,
            'amount' => $amount,
            'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
            'customer_name' => $order->customer_name ?: ($order->customer->first_name ?? ''),
            'customer_email' => $order->mail ?: ($order->customer->email ?? ''),
            'customer_phone' => $order->customer_phone_number_1 ?: ($order->customer->phone_number ?? ''),
            'description' => 'Order #' . $order->id . ' payment',
            'api_environment' => $this->baseUrl === 'https://pay.pesapal.com/v3/api' ? 'PRODUCTION' : 'SANDBOX',
        ];

        $log = PesapalLog::logTest($logData);

        try {
            $token = $this->getAuthToken();

            // Handle IPN URL - if no notification_id provided or it's invalid, don't include it
            // Pesapal will use the default IPN configuration
            $payload = [
                'id' => $merchantReference,
                'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                'amount' => $amount,
                'description' => 'Order #' . $order->id . ' payment',
                'callback_url' => $callbackUrl,
                'billing_address' => [
                    'email_address' => $order->mail ?: ($order->customer->email ?? ''),
                    'phone_number' => $order->customer_phone_number_1 ?: ($order->customer->phone_number ?? ''),
                    'country_code' => \App\Config\PesapalProductionConfig::getCountryCode(),
                    'first_name' => $order->customer_name ?: ($order->customer->first_name ?? ''),
                    'last_name' => $order->customer->last_name ?? '',
                    'line_1' => $order->customer_address ?: '',
                    'line_2' => '',
                    'city' => '',
                    'state' => '',
                    'postal_code' => '',
                    'zip_code' => ''
                ]
            ];

            // Register IPN URL and get notification_id
            if (!empty($notificationId)) {
                $payload['notification_id'] = $notificationId;
                Log::info('Pesapal: Using provided notification_id', ['notification_id' => $notificationId]);
            } else {
                Log::info('Pesapal: No notification_id provided, attempting to register IPN URL');
                try {
                    $ipnUrl = 'https://blitxpress.com/api/pesapal/ipn'; // Use production URL
                    Log::info('Pesapal: Registering IPN URL', ['ipn_url' => $ipnUrl]);
                    $ipnResponse = $this->registerIpnUrl($ipnUrl);
                    if (isset($ipnResponse['ipn_id'])) {
                        $payload['notification_id'] = $ipnResponse['ipn_id'];
                        Log::info('Pesapal: Successfully registered IPN URL', ['ipn_id' => $ipnResponse['ipn_id']]);
                    } else {
                        Log::warning('Pesapal: IPN registration failed, proceeding without notification_id');
                    }
                } catch (\Exception $ipnError) {
                    Log::error('Pesapal: Failed to register IPN URL', [
                        'error' => $ipnError->getMessage()
                    ]);
                    // Continue without notification_id
                }
            }

            // 📝 LOG THE COMPLETE POST PAYLOAD
            $requestHeaders = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer [HIDDEN]', // Hide token in logs
            ];

            // Update log with complete request data
            $log->update([
                'request_data' => [
                    'payload' => $payload,
                    'headers' => $requestHeaders,
                    'endpoint' => $this->baseUrl . '/Transactions/SubmitOrderRequest',
                    'method' => 'POST',
                    'callback_url' => $callbackUrl,
                    'notification_id' => $payload['notification_id'] ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->header('User-Agent'),
                    'timestamp' => now()->toISOString()
                ],
                'tracking_id' => $merchantReference
            ]);

            Log::info('Pesapal: Submitting order request', [
                'order_id' => $order->id,
                'merchant_reference' => $merchantReference,
                'amount' => $amount,
                'currency' => $payload['currency'],
                'payload' => $payload
            ]);

            // Record start time for response timing
            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->post($this->baseUrl . '/Transactions/SubmitOrderRequest', $payload);

            // Calculate response time
            $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Pesapal: Raw response received', [
                    'data' => $data,
                    'status_code' => $response->status(),
                    'headers' => $response->headers(),
                    'raw_body' => $response->body(),
                    'response_time_ms' => $responseTimeMs
                ]);
                
                // Check if this is actually an error response disguised as a 200
                if (isset($data['error']) || isset($data['status']) && $data['status'] !== 200) {
                    $errorMsg = $data['error']['message'] ?? $data['message'] ?? 'Unknown Pesapal error';
                    $errorCode = $data['error']['code'] ?? 'unknown_error';
                    $errorType = $data['error']['error_type'] ?? 'api_error';
                    
                    // 📝 LOG ERROR RESPONSE
                    PesapalLog::completeLog($log->id, [
                        'success' => false,
                        'status_code' => $response->status(),
                        'message' => 'Pesapal API Error: ' . $errorMsg,
                        'error_message' => $errorMsg . ' (Code: ' . $errorCode . ', Type: ' . $errorType . ')',
                        'response_data' => $data,
                        'response_time_ms' => $responseTimeMs
                    ]);
                    
                    Log::error('Pesapal: API returned error in successful response', [
                        'error_message' => $errorMsg,
                        'error_code' => $errorCode,
                        'error_type' => $errorType,
                        'full_response' => $data,
                        'payload_sent' => $payload
                    ]);
                    
                    throw new \Exception('Pesapal API Error: ' . $errorMsg . ' (Code: ' . $errorCode . ')');
                }
                
                // Enhanced validation with detailed logging
                $missingFields = [];
                if (!isset($data['order_tracking_id']) || empty($data['order_tracking_id'])) {
                    $missingFields[] = 'order_tracking_id';
                }
                if (!isset($data['redirect_url']) || empty($data['redirect_url'])) {
                    $missingFields[] = 'redirect_url';
                }
                
                if (!empty($missingFields)) {
                    // 📝 LOG VALIDATION FAILURE
                    PesapalLog::completeLog($log->id, [
                        'success' => false,
                        'status_code' => $response->status(),
                        'message' => 'Missing required fields: ' . implode(' and ', $missingFields),
                        'error_message' => 'Invalid response from Pesapal: Missing ' . implode(' and ', $missingFields),
                        'response_data' => $data,
                        'response_time_ms' => $responseTimeMs,
                        'debug_info' => [
                            'missing_fields' => $missingFields,
                            'available_fields' => array_keys($data),
                            'troubleshooting' => 'Check Pesapal API response format and ensure all required fields are present'
                        ]
                    ]);
                    
                    Log::error('Pesapal: Missing required fields in response', [
                        'missing_fields' => $missingFields,
                        'available_fields' => array_keys($data),
                        'full_response' => $data,
                        'payload_sent' => $payload ?? 'Unknown'
                    ]);
                    
                    throw new \Exception('Invalid response from Pesapal: Missing ' . implode(' and ', $missingFields) . '. Available fields: ' . implode(', ', array_keys($data)));
                }
                
                // Create transaction record
                $transaction = PesapalTransaction::create([
                    'order_id' => $order->id,
                    'order_tracking_id' => $data['order_tracking_id'],
                    'merchant_reference' => $merchantReference,
                    'amount' => $order->order_total,
                    'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                    'status' => 'PENDING',
                    'redirect_url' => $data['redirect_url'],
                    'callback_url' => $callbackUrl,
                    'notification_id' => $notificationId,
                    'description' => 'Order #' . $order->id . ' payment',
                    'pesapal_response' => json_encode($data)
                ]);

                // Update order with Pesapal info
                $order->update([
                    'payment_gateway' => 'pesapal',
                    'pesapal_order_tracking_id' => $data['order_tracking_id'],
                    'pesapal_merchant_reference' => $merchantReference,
                    'pesapal_status' => 'PENDING',
                    'pesapal_redirect_url' => $data['redirect_url'],
                    'payment_status' => 'PENDING_PAYMENT'
                ]);

                // 📝 LOG SUCCESSFUL RESPONSE
                PesapalLog::completeLog($log->id, [
                    'success' => true,
                    'status_code' => $response->status(),
                    'message' => 'Order submitted successfully to Pesapal',
                    'response_data' => $data,
                    'response_time_ms' => $responseTimeMs,
                    'tracking_id' => $data['order_tracking_id']
                ]);

                Log::info('Pesapal: Order submitted successfully', [
                    'order_id' => $order->id,
                    'tracking_id' => $data['order_tracking_id']
                ]);

                return $data;
            }

            // Enhanced error handling for unsuccessful responses
            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            // 📝 LOG HTTP FAILURE RESPONSE
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'status_code' => $statusCode,
                'message' => 'HTTP request failed with status ' . $statusCode,
                'error_message' => 'Failed to submit order to Pesapal: ' . $responseBody,
                'response_data' => $responseData,
                'response_time_ms' => $responseTimeMs,
                'debug_info' => [
                    'http_status' => $statusCode,
                    'response_body' => $responseBody,
                    'troubleshooting' => 'Check API credentials, network connectivity, and Pesapal service status'
                ]
            ]);

            Log::error('Pesapal: Order submission failed', [
                'order_id' => $order->id,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'response_data' => $responseData,
                'payload_sent' => $payload,
                'headers_sent' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer [HIDDEN]'
                ]
            ]);

            // Provide specific error messages based on status code
            $errorMessage = 'Failed to submit order to Pesapal';
            
            switch ($statusCode) {
                case 400:
                    $errorMessage = 'Bad Request: Invalid parameters sent to Pesapal';
                    break;
                case 401:
                    $errorMessage = 'Unauthorized: Invalid or expired Pesapal API credentials';
                    break;
                case 403:
                    $errorMessage = 'Forbidden: Access denied by Pesapal';
                    break;
                case 404:
                    $errorMessage = 'Not Found: Pesapal endpoint not available';
                    break;
                case 422:
                    $errorMessage = 'Validation Error: ' . ($responseData['message'] ?? 'Invalid data format');
                    break;
                case 429:
                    $errorMessage = 'Rate Limited: Too many requests to Pesapal';
                    break;
                case 500:
                    $errorMessage = 'Pesapal Server Error: Service temporarily unavailable';
                    break;
                default:
                    if ($responseData && isset($responseData['message'])) {
                        $errorMessage .= ': ' . $responseData['message'];
                    } else {
                        $errorMessage .= ' (Status: ' . $statusCode . ')';
                    }
            }

            throw new \Exception($errorMessage . ' - Response: ' . $responseBody);

        } catch (\Exception $e) {
            // 📝 LOG EXCEPTION
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'status_code' => 500,
                'message' => 'Exception occurred during order submission',
                'error_message' => $e->getMessage(),
                'response_data' => null,
                'response_time_ms' => isset($responseTimeMs) ? $responseTimeMs : null,
                'debug_info' => [
                    'exception_type' => get_class($e),
                    'exception_trace' => $e->getTraceAsString(),
                    'troubleshooting' => 'Check error message and trace for specific issue details'
                ]
            ]);
            
            Log::error('Pesapal: Order submission exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction status from Pesapal
     */
    public function getTransactionStatus($orderTrackingId)
    {
        try {
            $token = $this->getAuthToken();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->get($this->baseUrl . '/Transactions/GetTransactionStatus', [
                'orderTrackingId' => $orderTrackingId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Pesapal: Transaction status retrieved', [
                    'tracking_id' => $orderTrackingId,
                    'status' => $data['payment_status_description'] ?? 'Unknown'
                ]);

                return $data;
            }

            Log::error('Pesapal: Failed to get transaction status', [
                'tracking_id' => $orderTrackingId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            throw new \Exception('Failed to get transaction status: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Pesapal: Transaction status exception', [
                'tracking_id' => $orderTrackingId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update transaction status based on Pesapal response
     */
    public function updateTransactionStatus($orderTrackingId, $statusData)
    {
        try {
            $transaction = PesapalTransaction::where('order_tracking_id', $orderTrackingId)->first();
            
            if (!$transaction) {
                Log::warning('Pesapal: Transaction not found for tracking ID: ' . $orderTrackingId);
                return false;
            }

            // Update transaction
            $transaction->update([
                'status' => $statusData['payment_status_description'] ?? 'UNKNOWN',
                'status_code' => $statusData['status_code'] ?? null,
                'payment_method' => $statusData['payment_method'] ?? null,
                'confirmation_code' => $statusData['confirmation_code'] ?? null,
                'payment_account' => $statusData['payment_account'] ?? null,
                'pesapal_response' => json_encode($statusData)
            ]);

            // Update order status
            $order = $transaction->order;
            if ($order) {
                $paymentStatus = $this->mapPesapalStatusToPaymentStatus($statusData['payment_status_description'] ?? '');
                $orderState = $this->mapPaymentStatusToOrderState($paymentStatus);

                $order->update([
                    'pesapal_status' => $statusData['payment_status_description'] ?? 'UNKNOWN',
                    'pesapal_payment_method' => $statusData['payment_method'] ?? null,
                    'payment_status' => $paymentStatus,
                    'order_state' => $orderState,
                    'payment_completed_at' => $paymentStatus === 'PAID' ? now() : null
                ]);

                Log::info('Pesapal: Order status updated', [
                    'order_id' => $order->id,
                    'payment_status' => $paymentStatus,
                    'order_state' => $orderState
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Pesapal: Failed to update transaction status', [
                'tracking_id' => $orderTrackingId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Map Pesapal status to our payment status
     */
    private function mapPesapalStatusToPaymentStatus($pesapalStatus)
    {
        switch (strtoupper($pesapalStatus)) {
            case 'COMPLETED':
                return 'PAID';
            case 'FAILED':
            case 'INVALID':
                return 'FAILED';
            case 'REVERSED':
                return 'REFUNDED';
            default:
                return 'PENDING_PAYMENT';
        }
    }

    /**
     * Map payment status to order state
     */
    private function mapPaymentStatusToOrderState($paymentStatus)
    {
        switch ($paymentStatus) {
            case 'PAID':
                return '1'; // Processing
            case 'FAILED':
                return '4'; // Failed
            default:
                return '0'; // Pending
        }
    }
}
