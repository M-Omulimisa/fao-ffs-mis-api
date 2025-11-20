<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PesapalTransaction;
use App\Models\PesapalIpnLog;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PesapalController extends Controller
{
    protected $pesapalService;

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    /**
     * Initialize payment for an order
     * POST /api/pesapal/initialize
     */
    public function initialize(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'callback_url' => 'url|nullable',
                'notification_id' => 'string|nullable'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 400,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $order = Order::find($request->order_id);
            
            // Check if order is already paid
            if ($order->isPaid()) {
                return response()->json([
                    'code' => 0,
                    'status' => 400,
                    'message' => 'Order is already paid',
                    'data' => null
                ], 400);
            }

            // Get or register IPN URL if notification_id not provided
            $notificationId = $request->notification_id;
            if (!$notificationId) {
                $ipnResponse = $this->pesapalService->registerIpnUrl();
                $notificationId = $ipnResponse['ipn_id'] ?? null;
                
                if (!$notificationId) {
                    throw new \Exception('Failed to register IPN URL');
                }
            }

            // Submit order to Pesapal
            $response = $this->pesapalService->submitOrderRequest(
                $order, 
                $notificationId, 
                $request->callback_url
            );

            return response()->json([
                'code' => 1,
                'status' => 200,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'order_tracking_id' => $response['order_tracking_id'],
                    'merchant_reference' => $response['merchant_reference'],
                    'redirect_url' => $response['redirect_url'],
                    'status' => $response['status'] ?? '200'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Pesapal: Payment initialization failed', [
                'order_id' => $request->order_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Handle payment callback from Pesapal
     * GET /api/pesapal/callback
     */
    public function callback(Request $request)
    {
        try {
            Log::info('Pesapal: Callback received', $request->all());

            $orderTrackingId = $request->get('OrderTrackingId');
            $merchantReference = $request->get('OrderMerchantReference');
            $notificationType = $request->get('OrderNotificationType');

            if (!$orderTrackingId) {
                Log::warning('Pesapal: Callback missing OrderTrackingId');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid callback parameters'
                ], 400);
            }

            // Get transaction status from Pesapal
            $statusData = $this->pesapalService->getTransactionStatus($orderTrackingId);
            
            // Update transaction status
            $this->pesapalService->updateTransactionStatus($orderTrackingId, $statusData);

            // Find the order for redirect
            $transaction = PesapalTransaction::where('order_tracking_id', $orderTrackingId)->first();
            $order = $transaction ? $transaction->order : null;

            // Prepare response data
            $responseData = [
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
                'payment_status' => $statusData['payment_status_description'] ?? 'Unknown',
                'payment_method' => $statusData['payment_method'] ?? null,
                'amount' => $statusData['amount'] ?? null,
                'order_id' => $order ? $order->id : null
            ];

            // For web callback, you might want to redirect to a success/failure page
            // For API callback, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Callback processed successfully',
                    'data' => $responseData
                ]);
            } else {
                // Redirect to frontend with payment status
                $status = strtolower($statusData['payment_status_description'] ?? 'unknown');
                $frontendUrl = env('APP_URL') . "/payment-result?status={$status}&order_id=" . ($order ? $order->id : '');
                return redirect($frontendUrl);
            }

        } catch (\Exception $e) {
            Log::error('Pesapal: Callback processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Callback processing failed: ' . $e->getMessage(),
                    'data' => null
                ], 500);
            } else {
                $frontendUrl = env('APP_URL') . "/payment-result?status=error";
                return redirect($frontendUrl);
            }
        }
    }

    /**
     * Handle IPN notifications from Pesapal
     * POST /api/pesapal/ipn
     */
    public function ipn(Request $request)
    {
        try {
            // Log the IPN request
            $ipnLog = PesapalIpnLog::create([
                'order_tracking_id' => $request->get('OrderTrackingId'),
                'merchant_reference' => $request->get('OrderMerchantReference'),
                'notification_type' => $request->get('OrderNotificationType', 'IPNCHANGE'),
                'request_method' => $request->method(),
                'payload' => json_encode($request->all()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'PENDING'
            ]);

            Log::info('Pesapal: IPN received', [
                'ipn_log_id' => $ipnLog->id,
                'order_tracking_id' => $request->get('OrderTrackingId'),
                'merchant_reference' => $request->get('OrderMerchantReference')
            ]);

            $orderTrackingId = $request->get('OrderTrackingId');
            
            if (!$orderTrackingId) {
                $ipnLog->markAsError('Missing OrderTrackingId in IPN request');
                
                return response()->json([
                    'orderNotificationType' => 'IPNCHANGE',
                    'orderTrackingId' => '',
                    'orderMerchantReference' => '',
                    'status' => 500
                ]);
            }

            // Get transaction status from Pesapal
            $statusData = $this->pesapalService->getTransactionStatus($orderTrackingId);
            
            // Update transaction status
            $updated = $this->pesapalService->updateTransactionStatus($orderTrackingId, $statusData);
            
            if ($updated) {
                $ipnLog->markAsProcessed('Transaction status updated successfully');
                $status = 200;
            } else {
                $ipnLog->markAsError('Failed to update transaction status');
                $status = 500;
            }

            // Prepare response for Pesapal
            $response = [
                'orderNotificationType' => $request->get('OrderNotificationType', 'IPNCHANGE'),
                'orderTrackingId' => $orderTrackingId,
                'orderMerchantReference' => $request->get('OrderMerchantReference'),
                'status' => $status
            ];

            // Log the response we're sending back
            $ipnLog->response_sent = json_encode($response);
            $ipnLog->save();

            Log::info('Pesapal: IPN processed', [
                'ipn_log_id' => $ipnLog->id,
                'status' => $status,
                'response' => $response
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Pesapal: IPN processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            if (isset($ipnLog)) {
                $ipnLog->markAsError('Exception: ' . $e->getMessage());
            }

            return response()->json([
                'orderNotificationType' => 'IPNCHANGE',
                'orderTrackingId' => $request->get('OrderTrackingId', ''),
                'orderMerchantReference' => $request->get('OrderMerchantReference', ''),
                'status' => 500
            ]);
        }
    }

    /**
     * Check payment status for an order
     * GET /api/pesapal/status/{orderId}
     */
    public function status($orderId)
    {
        try {
            $order = Order::find($orderId);
            
            if (!$order) {
                return response()->json([
                    'code' => 0,
                    'status' => 404,
                    'message' => 'Order not found',
                    'data' => null
                ], 404);
            }

            $transaction = $order->latestPesapalTransaction;
            
            if (!$transaction) {
                return response()->json([
                    'code' => 1,
                    'status' => 200,
                    'message' => 'No Pesapal transaction found for this order',
                    'data' => [
                        'order_id' => $order->id,
                        'payment_status' => $order->payment_status ?: 'PENDING_PAYMENT',
                        'order_state' => $order->order_state,
                        'payment_gateway' => $order->payment_gateway
                    ]
                ]);
            }

            // Get fresh status from Pesapal
            if ($transaction->order_tracking_id) {
                $statusData = $this->pesapalService->getTransactionStatus($transaction->order_tracking_id);
                $this->pesapalService->updateTransactionStatus($transaction->order_tracking_id, $statusData);
                
                // Refresh the transaction data
                $transaction->refresh();
                $order->refresh();
            }

            return response()->json([
                'code' => 1,
                'status' => 200,
                'message' => 'Status retrieved successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_tracking_id' => $transaction->order_tracking_id,
                    'merchant_reference' => $transaction->merchant_reference,
                    'payment_status' => $order->payment_status,
                    'pesapal_status' => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'confirmation_code' => $transaction->confirmation_code,
                    'payment_account' => $transaction->payment_account,
                    'order_state' => $order->order_state,
                    'is_paid' => $order->isPaid(),
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Pesapal: Status check failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 0,
                'status' => 500,
                'message' => 'Status check failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Register IPN URL (utility endpoint)
     * POST /api/pesapal/register-ipn
     */
    public function registerIpn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ipn_url' => 'url|nullable',
                'notification_type' => 'in:GET,POST|nullable'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $response = $this->pesapalService->registerIpnUrl(
                $request->ipn_url,
                $request->notification_type ?: 'POST'
            );

            return response()->json([
                'success' => true,
                'message' => 'IPN URL registered successfully',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('Pesapal: IPN registration failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'IPN registration failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get Pesapal configuration and status
     * GET /api/pesapal/config
     */
    public function config()
    {
        try {
            $config = \App\Config\PesapalConfig::toArray();
            
            // Test authentication
            $authStatus = 'unknown';
            try {
                $token = $this->pesapalService->getAuthToken();
                $authStatus = !empty($token) ? 'connected' : 'failed';
            } catch (\Exception $e) {
                $authStatus = 'failed';
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuration retrieved successfully',
                'data' => [
                    'config' => $config,
                    'auth_status' => $authStatus,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Pesapal: Configuration retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Configuration retrieval failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Test Pesapal connectivity
     * POST /api/pesapal/test
     */
    public function test()
    {
        try {
            $results = [];
            
            // Test 1: Configuration
            try {
                $config = \App\Config\PesapalConfig::toArray();
                $results['configuration'] = [
                    'status' => 'passed',
                    'message' => 'Configuration loaded successfully',
                    'environment' => $config['environment']
                ];
            } catch (\Exception $e) {
                $results['configuration'] = [
                    'status' => 'failed',
                    'message' => 'Configuration error: ' . $e->getMessage()
                ];
            }

            // Test 2: Authentication
            try {
                $token = $this->pesapalService->getAuthToken();
                $results['authentication'] = [
                    'status' => !empty($token) ? 'passed' : 'failed',
                    'message' => !empty($token) ? 'Authentication successful' : 'Failed to get token'
                ];
            } catch (\Exception $e) {
                $results['authentication'] = [
                    'status' => 'failed',
                    'message' => 'Authentication failed: ' . $e->getMessage()
                ];
            }

            // Test 3: IPN Registration
            try {
                $ipnResponse = $this->pesapalService->registerIpnUrl();
                $results['ipn_registration'] = [
                    'status' => !empty($ipnResponse['ipn_id']) ? 'passed' : 'failed',
                    'message' => !empty($ipnResponse['ipn_id']) ? 'IPN registration successful' : 'Failed to register IPN',
                    'ipn_id' => $ipnResponse['ipn_id'] ?? null
                ];
            } catch (\Exception $e) {
                $results['ipn_registration'] = [
                    'status' => 'failed',
                    'message' => 'IPN registration failed: ' . $e->getMessage()
                ];
            }

            $overallStatus = collect($results)->every(fn($result) => $result['status'] === 'passed') ? 'passed' : 'failed';

            return response()->json([
                'success' => true,
                'message' => 'Pesapal connectivity test completed',
                'data' => [
                    'overall_status' => $overallStatus,
                    'tests' => $results,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Pesapal: Connectivity test failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connectivity test failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
