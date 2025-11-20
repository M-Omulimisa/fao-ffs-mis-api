<?php

namespace App\Http\Controllers;

use App\Services\PesapalService;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\PesapalTransaction;
use App\Models\PesapalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentTestController extends Controller
{
    protected $pesapalService;

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    /**
     * 🎨 Main Testing Dashboard
     */
    public function dashboard()
    {
        $recentTransactions = PesapalTransaction::with('order')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentLogs = PesapalLog::with('order')
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        $stats = [
            'total_transactions' => PesapalTransaction::count(),
            'successful_payments' => PesapalTransaction::where('status', 'COMPLETED')->count(),
            'pending_payments' => PesapalTransaction::where('status', 'PENDING')->count(),
            'failed_payments' => PesapalTransaction::where('status', 'FAILED')->count(),
        ];

        $logStats = PesapalLog::getStats(7); // Last 7 days

        return view('payment-test.dashboard', compact('recentTransactions', 'recentLogs', 'stats', 'logStats'));
    }

    /**
     * 💳 Initialize Test Payment - UPDATED for Centralized API Client
     */
    public function initializePayment(Request $request)
    {
        $startTime = microtime(true);
        
        // Create log entry
        $logData = [
            'test_type' => 'manual',
            'action' => 'initialize_payment',
            'method' => 'POST',
            'endpoint' => '/payment-test/initialize',
            'request_data' => $request->all(),
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency', 'UGX'),
            'customer_name' => $request->input('customer_name'),
            'customer_email' => $request->input('customer_email'),
            'customer_phone' => $request->input('customer_phone'),
            'description' => $request->input('description'),
            'started_at' => now()
        ];
        
        $log = PesapalLog::logTest($logData);

        try {
            // Enhanced request validation
            $validationResult = $this->validatePesapalRequest($request);
            if (!$validationResult['valid']) {
                throw new \Exception('Request validation failed: ' . implode(', ', $validationResult['errors']));
            }

            // Use centralized API client for test payment (simplified approach)
            $merchantReference = 'TEST_' . time() . '_' . uniqid();
            
            // Prepare payment data using EXACT working pattern
            $orderData = [
                'merchant_reference' => $merchantReference,
                'amount' => (float) $request->input('amount'),
                'currency' => $request->input('currency', 'UGX'),
                'description' => $request->input('description', 'Test payment'),
                'customer_name' => $request->input('customer_name'),
                'customer_email' => $request->input('customer_email'),
                'customer_phone' => $request->input('customer_phone'),
                'customer_address' => $request->input('customer_address', ''),
                'callback_url' => $request->input('callback_url', url('/payment-test/callback'))
            ];
            
            // Update log with test data
            $log->update(['merchant_reference' => $merchantReference]);
            
            // Log API configuration
            Log::info('🔧 Centralized API Client Test Payment Request', [
                'merchant_reference' => $merchantReference,
                'amount' => $orderData['amount'],
                'currency' => $orderData['currency']
            ]);
            
            // Use centralized API client directly (bypassing PesapalService for test)
            $response = $this->pesapalService->createTestPayment($orderData);
            
            // Enhanced response validation
            if ($request->boolean('validate_response', true)) {
                $responseValidation = $this->validatePesapalResponse($response);
                if (!$responseValidation['valid']) {
                    throw new \Exception('Invalid response from Pesapal: ' . $responseValidation['message']);
                }
            }
            
            
            // Validate response for successful payment
            if (isset($response['success']) && $response['success']) {
                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds
                
                // Complete the log
                PesapalLog::completeLog($log->id, [
                    'success' => true,
                    'response_data' => $response,
                    'message' => '🎉 Payment initialized successfully!',
                    'status_code' => '200',
                    'response_time_ms' => $responseTime,
                    'tracking_id' => $response['order_tracking_id'] ?? null,
                    'merchant_reference' => $merchantReference
                ]);

                // Log the test for debugging
                Log::info('🧪 Payment Test Initialized via Centralized API Client', [
                    'log_id' => $log->id,
                    'merchant_reference' => $merchantReference,
                    'amount' => $orderData['amount'],
                    'currency' => $orderData['currency'],
                    'response' => $response
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '🎉 Payment initialized successfully!',
                    'data' => [
                        'log_id' => $log->id,
                        'test_data' => $orderData,
                        'payment_response' => $response,
                        'redirect_url' => $response['redirect_url'] ?? null,
                        'order_tracking_id' => $response['order_tracking_id'] ?? null,
                        'test_info' => [
                            'test_id' => $merchantReference,
                            'timestamp' => now()->toISOString(),
                            'environment' => config('app.env'),
                            'response_time' => $responseTime . 'ms',
                            'api_client' => 'centralized'
                        ]
                    ]
                ]);
            } else {
                throw new \Exception($response['error'] ?? 'Payment initialization failed');
            }

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            // Enhanced error details for debugging
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ];

            // Check for specific Pesapal API errors
            $isApiError = strpos($e->getMessage(), 'order_tracking_id') !== false || 
                         strpos($e->getMessage(), 'redirect_url') !== false;
            
            $debugInfo = [];
            if ($isApiError) {
                $debugInfo = [
                    'api_issue_detected' => true,
                    'common_causes' => [
                        'Invalid API credentials',
                        'Network connectivity issues',
                        'Pesapal service temporarily unavailable',
                        'Invalid request parameters',
                        'API rate limiting'
                    ],
                    'troubleshooting_steps' => [
                        'Check API credentials in .env file',
                        'Verify network connectivity',
                        'Check Pesapal service status',
                        'Review request parameters',
                        'Try with different amount/currency'
                    ]
                ];
            }
            
            // Complete the log with enhanced error information
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'message' => '💥 Payment initialization failed: ' . $e->getMessage(),
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'status_code' => '500',
                'error_details' => $errorDetails,
                'debug_info' => $debugInfo,
                'request_context' => [
                    'user_agent' => request()->header('User-Agent'),
                    'ip_address' => request()->ip(),
                    'timestamp' => now()->toISOString(),
                    'environment' => config('app.env')
                ]
            ]);

            Log::error('❌ Payment Test Failed', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
                'error_details' => $errorDetails,
                'debug_info' => $debugInfo,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => '💥 Payment initialization failed: ' . $e->getMessage(),
                'data' => [
                    'log_id' => $log->id,
                    'error_details' => $errorDetails,
                    'debug_info' => $debugInfo,
                    'timestamp' => now()->toISOString(),
                    'response_time_ms' => $responseTime,
                    'troubleshooting' => $isApiError ? [
                        'issue_type' => 'Pesapal API Response Error',
                        'description' => 'The API response is missing required fields (order_tracking_id or redirect_url)',
                        'next_steps' => [
                            '1. Check API credentials configuration',
                            '2. Verify network connectivity to Pesapal',
                            '3. Try with different test parameters',
                            '4. Check Pesapal service status',
                            '5. Review request payload in logs'
                        ]
                    ] : null
                ]
            ], 500);
        }
    }

    /**
     * 🔍 Check Payment Status
     */
    public function checkPaymentStatus(Request $request)
    {
        $startTime = microtime(true);
        $orderId = $request->input('order_id');
        $trackingId = $request->input('tracking_id');

        // Create log entry
        $logData = [
            'test_type' => 'manual',
            'action' => 'check_status',
            'method' => 'POST',
            'endpoint' => '/payment-test/status',
            'request_data' => $request->all(),
            'order_id' => $orderId,
            'tracking_id' => $trackingId,
            'started_at' => now()
        ];
        
        $log = PesapalLog::logTest($logData);

        try {
            // First check our local database
            $localTransaction = null;
            $order = null;

            if ($orderId) {
                $order = Order::find($orderId);
                $localTransaction = PesapalTransaction::where('order_id', $orderId)->first();
                $actualTrackingId = $order ? $order->pesapal_order_tracking_id : null;
            } elseif ($trackingId) {
                $localTransaction = PesapalTransaction::where('order_tracking_id', $trackingId)->first();
                $order = $localTransaction ? $localTransaction->order : null;
                $actualTrackingId = $trackingId;
            } else {
                throw new \Exception('Either order_id or tracking_id is required');
            }

            // Get status from Pesapal if we have tracking ID
            $pesapalResponse = null;
            if ($actualTrackingId) {
                try {
                    $pesapalResponse = $this->pesapalService->getTransactionStatus($actualTrackingId);
                    
                    // Update local transaction if exists
                    if ($localTransaction && $pesapalResponse) {
                        // Get fresh status data and update
                        $this->pesapalService->updateTransactionStatus($actualTrackingId, $pesapalResponse);
                        $localTransaction = $localTransaction->fresh();
                    }
                } catch (\Exception $e) {
                    // Log but don't fail - we can still show local data
                    Log::warning('Failed to get Pesapal status', [
                        'tracking_id' => $actualTrackingId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            // Complete the log
            PesapalLog::completeLog($log->id, [
                'success' => true,
                'response_data' => [
                    'local_transaction' => $localTransaction,
                    'pesapal_response' => $pesapalResponse
                ],
                'message' => '✅ Status retrieved successfully!',
                'status_code' => '200',
                'response_time_ms' => $responseTime,
                'tracking_id' => $actualTrackingId
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Status retrieved successfully!',
                'data' => [
                    'log_id' => $log->id,
                    'order' => $order,
                    'local_transaction' => $localTransaction,
                    'pesapal_response' => $pesapalResponse,
                    'status_explanation' => $this->explainPaymentStatus($localTransaction ? $localTransaction->status : 'UNKNOWN'),
                    'last_updated' => $localTransaction ? $localTransaction->updated_at->diffForHumans() : 'N/A',
                    'response_time' => $responseTime . 'ms'
                ]
            ]);

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);
            
            // Complete the log with error
            PesapalLog::completeLog($log->id, [
                'success' => false,
                'message' => '💥 Status check failed: ' . $e->getMessage(),
                'error_message' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'status_code' => '500'
            ]);

            Log::error('❌ Payment Status Check Failed', [
                'log_id' => $log->id,
                'order_id' => $orderId,
                'tracking_id' => $trackingId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '💥 Status check failed: ' . $e->getMessage(),
                'data' => [
                    'log_id' => $log->id,
                    'error_details' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ]
            ], 500);
        }
    }

    /**
     * 🎲 Generate Random Test Data
     */
    public function generateTestData()
    {
        $testScenarios = [
            [
                'name' => '🛒 Small Purchase',
                'amount' => rand(1000, 25000), // UGX 1K - 25K (removed limits)
                'description' => 'Test small purchase - electronics accessories'
            ],
            [
                'name' => '🎮 Medium Purchase', 
                'amount' => rand(25000, 250000), // UGX 25K - 250K
                'description' => 'Test medium purchase - gaming console'
            ],
            [
                'name' => '💻 Large Purchase',
                'amount' => rand(250000, 2500000), // UGX 250K - 2.5M
                'description' => 'Test large purchase - laptop computer'
            ],
            [
                'name' => '📱 Premium Purchase',
                'amount' => rand(2500000, 25000000), // UGX 2.5M - 25M (no upper limit)
                'description' => 'Test premium purchase - flagship smartphone'
            ],
            [
                'name' => '🏢 Enterprise Purchase',
                'amount' => rand(25000000, 100000000), // UGX 25M - 100M
                'description' => 'Test enterprise purchase - bulk equipment'
            ]
        ];

        $scenario = $testScenarios[array_rand($testScenarios)];
        
        return response()->json([
            'success' => true,
            'data' => array_merge($scenario, [
                'customer_name' => $this->generateTestCustomerName(),
                'customer_email' => 'test.' . Str::random(8) . '@blitxpress.test',
                'customer_phone' => '+256' . rand(700000000, 799999999),
                'test_id' => 'TEST_' . strtoupper(Str::random(8)),
                'timestamp' => now()->toISOString(),
                'currency' => collect(['UGX', 'USD', 'EUR', 'KES'])->random(),
                'callback_url' => '',
                'notification_id' => ''
            ])
        ]);
    }

    /**
     * 📊 Get Payment Analytics
     */
    public function getAnalytics()
    {
        $analytics = [
            'today' => [
                'transactions' => PesapalTransaction::whereDate('created_at', today())->count(),
                'successful' => PesapalTransaction::whereDate('created_at', today())->where('status', 'COMPLETED')->count(),
                'total_amount' => PesapalTransaction::whereDate('created_at', today())->where('status', 'COMPLETED')->sum('amount')
            ],
            'this_week' => [
                'transactions' => PesapalTransaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'successful' => PesapalTransaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('status', 'COMPLETED')->count(),
                'total_amount' => PesapalTransaction::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('status', 'COMPLETED')->sum('amount')
            ],
            'status_distribution' => [
                'COMPLETED' => PesapalTransaction::where('status', 'COMPLETED')->count(),
                'PENDING' => PesapalTransaction::where('status', 'PENDING')->count(),
                'FAILED' => PesapalTransaction::where('status', 'FAILED')->count(),
                'CANCELLED' => PesapalTransaction::where('status', 'CANCELLED')->count(),
            ],
            'recent_activity' => PesapalTransaction::with('order')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => number_format($transaction->amount),
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at->diffForHumans(),
                        'order_reference' => $transaction->order->order_code ?? 'N/A'
                    ];
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * 🔄 Simulate Payment Callback
     */
    public function simulateCallback(Request $request)
    {
        $trackingId = $request->input('tracking_id');
        $status = $request->input('status', 'COMPLETED');
        
        try {
            // Find the transaction
            $transaction = PesapalTransaction::where('order_tracking_id', $trackingId)->first();
            
            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }

            // Update status
            $transaction->update(['status' => $status]);
            
            // Update order if completed
            if ($status === 'COMPLETED' && $transaction->order) {
                $transaction->order->update([
                    'payment_status' => 'PAID',
                    'pesapal_status' => $status,
                    'order_state' => 'processing'
                ]);
            }

            Log::info('🎭 Payment Callback Simulated', [
                'tracking_id' => $trackingId,
                'status' => $status,
                'transaction_id' => $transaction->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "🎭 Payment status simulated: {$status}",
                'data' => [
                    'transaction' => $transaction->fresh(),
                    'order' => $transaction->order->fresh() ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '💥 Callback simulation failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 🧹 Cleanup Test Data
     */
    public function cleanupTestData()
    {
        try {
            // Delete test orders and related transactions
            $testOrders = Order::where('order_code', 'LIKE', 'TEST_%')->get();
            $deletedCount = 0;
            
            foreach ($testOrders as $order) {
                // Delete related transactions first
                PesapalTransaction::where('order_id', $order->id)->delete();
                // Delete the order
                $order->delete();
                $deletedCount++;
            }

            Log::info('🧹 Test Data Cleanup', [
                'deleted_orders' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "🧹 Cleaned up {$deletedCount} test orders and transactions",
                'data' => ['deleted_count' => $deletedCount]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '💥 Cleanup failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * � Get Detailed Log Information
     */
    public function getLogDetails($id)
    {
        try {
            $log = PesapalLog::with('order')->find($id);
            
            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ Log not found',
                    'data' => null
                ], 404);
            }

            // Parse JSON fields safely
            $requestData = [];
            $responseData = [];
            $errorDetails = [];
            $debugInfo = [];

            if ($log->request_data) {
                $requestData = is_string($log->request_data) ? 
                    json_decode($log->request_data, true) : $log->request_data;
            }

            if ($log->response_data) {
                $responseData = is_string($log->response_data) ? 
                    json_decode($log->response_data, true) : $log->response_data;
            }

            if ($log->error_details) {
                $errorDetails = is_string($log->error_details) ? 
                    json_decode($log->error_details, true) : $log->error_details;
            }

            if ($log->debug_info) {
                $debugInfo = is_string($log->debug_info) ? 
                    json_decode($log->debug_info, true) : $log->debug_info;
            }

            // Prepare detailed log data
            $detailedLog = [
                'id' => $log->id,
                'action' => $log->action,
                'method' => $log->method,
                'endpoint' => $log->endpoint,
                'success' => $log->success,
                'message' => $log->message,
                'error_message' => $log->error_message,
                'response_time_ms' => $log->response_time_ms,
                'status_code' => $log->status_code,
                'created_at' => $log->created_at->toISOString(),
                'formatted_created_at' => $log->created_at->format('Y-m-d H:i:s'),
                
                // Request details
                'request_data' => $requestData,
                'amount' => $log->amount,
                'currency' => $log->currency,
                'customer_name' => $log->customer_name,
                'customer_email' => $log->customer_email,
                'customer_phone' => $log->customer_phone,
                'description' => $log->description,
                
                // API details
                'tracking_id' => $log->tracking_id,
                'merchant_reference' => $log->merchant_reference,
                'api_environment' => $log->api_environment,
                
                // Response details
                'response_data' => $responseData,
                'pesapal_response' => $responseData, // Alias for clarity
                
                // Error details
                'error_details' => $errorDetails,
                
                // Debug information
                'debug_info' => $debugInfo,
                'troubleshooting' => $debugInfo['troubleshooting'] ?? null,
                
                // Order information
                'order_id' => $log->order_id,
                'order' => $log->order ? [
                    'id' => $log->order->id,
                    'order_code' => $log->order->order_code,
                    'total' => $log->order->order_total,
                    'status' => $log->order->payment_status
                ] : null,
                
                // Additional metadata
                'test_type' => $log->test_type,
                'ip_address' => $requestData['ip_address'] ?? null,
                'user_agent' => $requestData['user_agent'] ?? null,
                
                // Pesapal-specific payload that was sent
                'pesapal_request_payload' => $this->reconstructPesapalPayload($log, $requestData),
            ];

            return response()->json([
                'success' => true,
                'message' => '✅ Log details retrieved successfully',
                'data' => $detailedLog
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get log details', [
                'log_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '💥 Failed to retrieve log details: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 🔄 Reconstruct the exact payload sent to Pesapal
     */
    private function reconstructPesapalPayload($log, $requestData)
    {
        // Try to reconstruct the exact payload that was sent to Pesapal
        $payload = [
            'id' => $log->merchant_reference ?: 'ORDER_' . $log->order_id . '_' . strtotime($log->created_at),
            'currency' => $log->currency ?: 'UGX',
            'amount' => floatval($log->amount ?: 0),
            'description' => $log->description ?: 'Payment for Order #' . $log->order_id,
            'callback_url' => $requestData['callback_url'] ?? config('services.pesapal.callback_url'),
        ];

        // Add notification_id if it was used
        if (!empty($requestData['notification_id'])) {
            $payload['notification_id'] = $requestData['notification_id'];
        }

        // Add billing address
        $payload['billing_address'] = [
            'email_address' => $log->customer_email ?: '',
            'phone_number' => $log->customer_phone ?: '',
            'country_code' => 'UG',
            'first_name' => $log->customer_name ?: '',
            'last_name' => '',
            'line_1' => '',
            'line_2' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'zip_code' => ''
        ];

        return $payload;
    }

    /**
     * 🔧 Test Pesapal Configuration - UPDATED for Centralized API Client
     */
    public function testConfiguration()
    {
        try {
            $config = [
                'timestamp' => now()->toISOString(),
                'environment' => config('app.env'),
                'pesapal_env' => config('services.pesapal.environment', 'production'),
                'base_url' => \App\Config\PesapalConfig::getBaseUrl(),
                'consumer_key' => config('services.pesapal.consumer_key') ? '✅ Set (' . substr(config('services.pesapal.consumer_key'), 0, 8) . '...)' : '❌ Missing',
                'consumer_secret' => config('services.pesapal.consumer_secret') ? '✅ Set (' . substr(config('services.pesapal.consumer_secret'), 0, 8) . '...)' : '❌ Missing',
                'callback_url' => config('services.pesapal.callback_url') ?: '❌ Not set',
                'ipn_url' => config('services.pesapal.ipn_url') ?: '❌ Not set',
                'currency' => config('services.pesapal.currency', 'UGX'),
                'country_code' => \App\Config\PesapalProductionConfig::getCountryCode(),
                'api_client' => 'centralized'
            ];

            // Enhanced environment variable check
            $envVars = [
                'PESAPAL_CONSUMER_KEY' => env('PESAPAL_CONSUMER_KEY') ? '✅ Set' : '❌ Missing',
                'PESAPAL_CONSUMER_SECRET' => env('PESAPAL_CONSUMER_SECRET') ? '✅ Set' : '❌ Missing',
                'PESAPAL_ENVIRONMENT' => env('PESAPAL_ENVIRONMENT', 'production'),
                'PESAPAL_CURRENCY' => env('PESAPAL_CURRENCY', 'UGX'),
                'PESAPAL_CALLBACK_URL' => env('PESAPAL_CALLBACK_URL') ?: '❌ Not set',
                'PESAPAL_IPN_URL' => env('PESAPAL_IPN_URL') ?: '❌ Not set'
            ];

            // Configuration issues check
            $issues = [];
            if (!config('services.pesapal.consumer_key')) {
                $issues[] = 'Missing PESAPAL_CONSUMER_KEY in .env file';
            }
            if (!config('services.pesapal.consumer_secret')) {
                $issues[] = 'Missing PESAPAL_CONSUMER_SECRET in .env file';
            }
            if (!config('services.pesapal.callback_url')) {
                $issues[] = 'Missing PESAPAL_CALLBACK_URL in .env file';
            }

            // Test centralized API client directly
            $authResult = [];
            $testConnectionResult = [];
            try {
                Log::info('🧪 Testing Centralized Pesapal API Client...');
                
                // Test authentication via centralized client
                $token = $this->pesapalService->getAuthToken();
                $authResult = [
                    'status' => '✅ Authentication successful (Centralized Client)',
                    'token_preview' => substr($token, 0, 20) . '...',
                    'token_length' => strlen($token)
                ];
                
                // Test connection via centralized client
                $connectionTest = $this->pesapalService->testConnection();
                $testConnectionResult = [
                    'status' => $connectionTest['success'] ? '✅ Connection test passed' : '❌ Connection test failed',
                    'response_time' => $connectionTest['response_time'] ?? 'unknown',
                    'details' => $connectionTest
                ];
                
            } catch (\Exception $e) {
                $authResult = [
                    'status' => '❌ Authentication failed',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];
                
                $testConnectionResult = [
                    'status' => '❌ Connection test failed',
                    'error' => $e->getMessage()
                ];
                
                // Add specific error analysis
                if (strpos($e->getMessage(), 'Consumer key and secret') !== false) {
                    $issues[] = 'Invalid or missing API credentials';
                } elseif (strpos($e->getMessage(), '401') !== false) {
                    $issues[] = 'API credentials are invalid';
                } elseif (strpos($e->getMessage(), 'network') !== false || strpos($e->getMessage(), 'timeout') !== false) {
                    $issues[] = 'Network connectivity issue';
                }
            }

            return response()->json([
                'success' => true,
                'message' => '🔧 Configuration analysis complete (Centralized API Client)',
                'data' => [
                    'config' => $config,
                    'environment_variables' => $envVars,
                    'authentication_test' => $authResult,
                    'connection_test' => $testConnectionResult,
                    'issues' => $issues,
                    'recommendations' => empty($issues) ? ['Configuration looks good!'] : [
                        'Check your .env file for missing PESAPAL_* variables',
                        'Ensure you have valid Pesapal production credentials',
                        'Verify network connectivity to Pesapal API',
                        'Run "php artisan config:clear" after updating .env'
                    ],
                    'health_check' => $this->performHealthCheck(),
                    'api_client_info' => [
                        'type' => 'centralized',
                        'version' => '1.0',
                        'features' => ['authentication', 'payment_initialization', 'status_checking', 'ipn_registration']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '💥 Configuration test failed: ' . $e->getMessage(),
                'data' => [
                    'error_details' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ]
            ], 500);
        }
    }

    /**
     * 🎭 Test Different Payment Scenarios
     */
    public function testScenarios(Request $request)
    {
        $scenario = $request->input('scenario', 'success');
        
        $scenarios = [
            'success' => [
                'name' => '✅ Success Scenario',
                'amount' => 50000,
                'description' => 'Test successful payment flow',
                'customer_name' => 'John Success',
                'customer_email' => 'success@test.com'
            ],
            'high_amount' => [
                'name' => '💰 High Amount Test',
                'amount' => 5000000,
                'description' => 'Test with high amount transaction',
                'customer_name' => 'Big Spender',
                'customer_email' => 'bigspender@test.com'
            ],
            'minimal' => [
                'name' => '🪙 Minimal Amount',
                'amount' => 1000,
                'description' => 'Test with minimal amount',
                'customer_name' => 'Min Tester',
                'customer_email' => 'minimal@test.com'
            ],
            'special_chars' => [
                'name' => '🔤 Special Characters',
                'amount' => 25000,
                'description' => 'Test with special chars: àáâãäåæçèéê',
                'customer_name' => 'José María',
                'customer_email' => 'special@test.com'
            ]
        ];

        try {
            $testData = $scenarios[$scenario] ?? $scenarios['success'];
            
            // Create test order with scenario data
            $order = $this->createTestOrder(new Request($testData));
            
            // Initialize payment
            $callbackUrl = url('/payment-test/callback');
            $response = $this->pesapalService->submitOrderRequest($order, null, $callbackUrl);
            
            Log::info('🎭 Scenario Test', [
                'scenario' => $scenario,
                'order_id' => $order->id,
                'amount' => $order->order_total
            ]);

            return response()->json([
                'success' => true,
                'message' => "🎭 Scenario '{$testData['name']}' initialized successfully!",
                'data' => [
                    'scenario' => $testData,
                    'order' => $order->fresh(),
                    'payment_response' => $response,
                    'available_scenarios' => array_keys($scenarios)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '💥 Scenario test failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 📊 Get Payment Statistics
     */
    public function getPaymentStats()
    {
        try {
            $stats = [
                'overview' => [
                    'total_transactions' => PesapalTransaction::count(),
                    'total_amount' => PesapalTransaction::where('status', 'COMPLETED')->sum('amount'),
                    'success_rate' => $this->calculateSuccessRate(),
                    'average_amount' => PesapalTransaction::avg('amount')
                ],
                'by_status' => PesapalTransaction::selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
                    ->groupBy('status')
                    ->get()
                    ->keyBy('status'),
                'by_date' => PesapalTransaction::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'payment_methods' => PesapalTransaction::selectRaw('payment_method, COUNT(*) as count')
                    ->whereNotNull('payment_method')
                    ->groupBy('payment_method')
                    ->get()
                    ->keyBy('payment_method')
            ];

            return response()->json([
                'success' => true,
                'message' => '📊 Statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '💥 Stats retrieval failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 🚀 Bulk Test Generator
     */
    public function bulkTest(Request $request)
    {
        $count = min($request->input('count', 5), 20); // Max 20 tests
        $results = [];
        
        try {
            for ($i = 0; $i < $count; $i++) {
                $testData = $this->generateRandomTestData();
                
                try {
                    $order = $this->createTestOrder(new Request($testData));
                    
                    $results[] = [
                        'test_number' => $i + 1,
                        'status' => 'success',
                        'order_id' => $order->id,
                        'amount' => $order->order_total,
                        'customer_name' => $order->customer_name
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'test_number' => $i + 1,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $successCount = collect($results)->where('status', 'success')->count();
            
            return response()->json([
                'success' => true,
                'message' => "🚀 Bulk test completed: {$successCount}/{$count} successful",
                'data' => [
                    'results' => $results,
                    'summary' => [
                        'total_tests' => $count,
                        'successful' => $successCount,
                        'failed' => $count - $successCount
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '💥 Bulk test failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * 🔄 Live Status Monitor
     */
    public function liveStatusMonitor(Request $request)
    {
        $trackingIds = $request->input('tracking_ids', []);
        $results = [];
        
        try {
            foreach ($trackingIds as $trackingId) {
                try {
                    $status = $this->pesapalService->getTransactionStatus($trackingId);
                    $results[$trackingId] = [
                        'status' => 'success',
                        'data' => $status,
                        'last_checked' => now()->toISOString()
                    ];
                } catch (\Exception $e) {
                    $results[$trackingId] = [
                        'status' => 'error',
                        'error' => $e->getMessage(),
                        'last_checked' => now()->toISOString()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => '🔄 Live status check completed',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '💥 Live monitor failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // Helper Methods
    private function createTestOrder($request)
    {
        // Try to get a test product or create one
        $product = Product::first();
        
        if (!$product) {
            // Create a test product if none exists
            $product = Product::create([
                'name' => 'Test Product - ' . Str::random(8),
                'description' => 'This is a test product for payment testing',
                'price_1' => $request->input('amount', 10000),
                'feature_photo' => 'test-product.jpg',
                'currency' => $request->input('currency', 'UGX'),
                'category' => 1,
                'user' => 1
            ]);
        }

        // Generate merchant reference if not provided
        $merchantReference = $request->input('merchant_reference');
        if (!$merchantReference) {
            $merchantReference = 'BX-TEST-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
        }

        // Enhanced test order creation with all new fields
        $order = Order::create([
            'order_code' => 'TEST_' . strtoupper(Str::random(8)),
            'product_name' => $request->input('description', $product->name),
            'product_picture' => $product->feature_photo ?? 'test-product.jpg',
            'customer_name' => $request->input('customer_name', 'Test Customer'),
            'mail' => $request->input('customer_email', 'test@blitxpress.test'),
            'customer_phone_number_1' => $request->input('customer_phone', '+256700000000'),
            'order_total' => $request->input('amount', 10000),
            'delivery_fee' => 0,
            'quantity' => 1,
            'payment_method' => $request->input('payment_method', 'pesapal'),
            'payment_status' => 'PENDING_PAYMENT',
            'order_state' => 'pending',
            'customer_address' => 'Test Address, Kampala, Uganda',
            'user' => 1, // Default test user
            'currency' => $request->input('currency', 'UGX'),
            'callback_url' => $request->input('callback_url'),
            'notification_id' => $request->input('notification_id'),
            // Enhanced fields for debugging
            'merchant_reference' => $merchantReference,
            'api_environment' => $request->input('api_environment', 'production'),
            'debug_mode' => $request->boolean('debug_mode', true),
            'request_timeout' => $request->input('request_timeout', 30),
            'validate_response' => $request->boolean('validate_response', true),
            'retry_on_failure' => $request->boolean('retry_on_failure', false),
            // Store test configuration for debugging
            'test_configuration' => json_encode([
                'debug_mode' => $request->boolean('debug_mode', true),
                'validate_response' => $request->boolean('validate_response', true),
                'retry_on_failure' => $request->boolean('retry_on_failure', false),
                'api_environment' => $request->input('api_environment', 'production'),
                'request_timeout' => $request->input('request_timeout', 30),
                'payment_method_restriction' => $request->input('payment_method'),
                'form_timestamp' => now()->toISOString(),
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip()
            ])
        ]);

        // Log enhanced order creation for debugging
        Log::info('🏗️ Enhanced Test Order Created', [
            'order_id' => $order->id,
            'merchant_reference' => $merchantReference,
            'debug_mode' => $request->boolean('debug_mode'),
            'api_environment' => $request->input('api_environment'),
            'validate_response' => $request->boolean('validate_response'),
            'retry_on_failure' => $request->boolean('retry_on_failure'),
            'payment_method_restriction' => $request->input('payment_method'),
            'request_timeout' => $request->input('request_timeout', 30)
        ]);

        return $order;
    }

    private function generateTestCustomerName()
    {
        $firstNames = ['John', 'Jane', 'David', 'Sarah', 'Michael', 'Emma', 'James', 'Lisa', 'Robert', 'Maria'];
        $lastNames = ['Doe', 'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function explainPaymentStatus($status)
    {
        $explanations = [
            'COMPLETED' => '✅ Payment successful! Money has been received.',
            'PENDING' => '⏳ Payment is being processed. Please wait.',
            'FAILED' => '❌ Payment failed. Transaction was declined.',
            'CANCELLED' => '🚫 Payment was cancelled by user or system.',
            'INVALID' => '⚠️ Invalid payment details or transaction.',
            'UNKNOWN' => '❓ Payment status could not be determined.'
        ];

        return $explanations[$status] ?? $explanations['UNKNOWN'];
    }

    private function performHealthCheck()
    {
        $checks = [];
        
        // Database connectivity
        try {
            Order::count();
            $checks['database'] = '✅ Connected';
        } catch (\Exception $e) {
            $checks['database'] = '❌ Failed: ' . $e->getMessage();
        }
        
        // Pesapal service availability
        try {
            $this->pesapalService->getAuthToken();
            $checks['pesapal_auth'] = '✅ Working';
        } catch (\Exception $e) {
            $checks['pesapal_auth'] = '❌ Failed: ' . $e->getMessage();
        }
        
        // Required environment variables
        $requiredEnvs = ['PESAPAL_CONSUMER_KEY', 'PESAPAL_CONSUMER_SECRET', 'PESAPAL_BASE_URL'];
        foreach ($requiredEnvs as $env) {
            $checks["env_{$env}"] = env($env) ? '✅ Set' : '❌ Missing';
        }
        
        return $checks;
    }

    private function calculateSuccessRate()
    {
        $total = PesapalTransaction::count();
        if ($total == 0) return 0;
        
        $successful = PesapalTransaction::where('status', 'COMPLETED')->count();
        return round(($successful / $total) * 100, 2);
    }

    private function generateRandomTestData()
    {
        $amounts = [1000, 5000, 10000, 25000, 50000, 100000, 250000, 500000, 1000000, 2500000, 5000000, 10000000]; // Removed limits
        $products = [
            'Test Electronics Device',
            'Sample Fashion Item', 
            'Demo Home Appliance',
            'Test Mobile Phone',
            'Sample Laptop Computer',
            'Demo Gaming Console',
            'Test Furniture Item',
            'Sample Jewelry',
            'Demo Car Parts',
            'Test Industrial Equipment'
        ];
        
        $currencies = ['UGX', 'USD', 'EUR', 'KES'];
        
        return [
            'amount' => $amounts[array_rand($amounts)],
            'currency' => $currencies[array_rand($currencies)],
            'description' => $products[array_rand($products)],
            'customer_name' => $this->generateTestCustomerName(),
            'customer_email' => 'test' . rand(1000, 9999) . '@blitxpress.test',
            'customer_phone' => '+256' . rand(700000000, 799999999),
            'callback_url' => '',
            'notification_id' => ''
        ];
    }

    /**
     * 🔍 Validate Pesapal API Request
     */
    private function validatePesapalRequest($request)
    {
        $errors = [];
        
        // Amount validation
        $amount = $request->input('amount');
        if (!$amount || !is_numeric($amount) || floatval($amount) <= 0) {
            $errors[] = 'Amount must be a positive number';
        }
        
        // Currency validation
        $validCurrencies = ['UGX', 'USD', 'EUR', 'KES', 'GBP', 'CAD'];
        if (!in_array($request->input('currency', 'UGX'), $validCurrencies)) {
            $errors[] = 'Invalid currency code';
        }
        
        // Email validation
        if (!filter_var($request->input('customer_email'), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }
        
        // Phone validation
        $phone = $request->input('customer_phone');
        if (!$phone || !preg_match('/^\+\d{10,15}$/', $phone)) {
            $errors[] = 'Phone number must start with + and contain 10-15 digits';
        }
        
        // Callback URL validation (if provided)
        $callbackUrl = $request->input('callback_url');
        if ($callbackUrl && !filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid callback URL format';
        }
        
        // Timeout validation
        $timeout = $request->input('request_timeout', 30);
        if ($timeout < 10 || $timeout > 120) {
            $errors[] = 'Request timeout must be between 10 and 120 seconds';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 🔍 Validate Pesapal API Response
     */
    private function validatePesapalResponse($response)
    {
        if (!$response || !is_array($response)) {
            return [
                'valid' => false,
                'message' => 'Empty or invalid response structure'
            ];
        }
        
        // Check for required fields that caused the original error
        if (!isset($response['order_tracking_id']) || empty($response['order_tracking_id'])) {
            return [
                'valid' => false,
                'message' => 'Missing order_tracking_id in response'
            ];
        }
        
        if (!isset($response['redirect_url']) || empty($response['redirect_url'])) {
            return [
                'valid' => false,
                'message' => 'Missing redirect_url in response'
            ];
        }
        
        // Validate redirect URL format
        if (!filter_var($response['redirect_url'], FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'message' => 'Invalid redirect_url format in response'
            ];
        }
        
        // Additional validation for merchant reference
        if (isset($response['merchant_reference']) && empty($response['merchant_reference'])) {
            return [
                'valid' => false,
                'message' => 'Empty merchant_reference in response'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Response validation passed'
        ];
    }
}
