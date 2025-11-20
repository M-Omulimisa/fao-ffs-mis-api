<?php

namespace App\Http\Controllers;

use App\Models\UniversalPayment;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UniversalPaymentController extends Controller
{
    protected $pesapalService;

    public function __construct(PesapalService $pesapalService)
    {
        $this->pesapalService = $pesapalService;
    }

    /**
     * Initialize a new payment (creates payment + initializes gateway)
     * POST /api/universal-payments/initialize
     */
    public function initialize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_type' => 'required|string',
            'payment_category' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            'customer_name' => 'nullable|string', // Optional - will use user's name if not provided
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string', // Optional - will use user's phone if not provided
            'payment_items' => 'required|array|min:1',
            'payment_items.*.type' => 'required|string',
            'payment_items.*.id' => 'required|integer',
            'payment_items.*.amount' => 'required|numeric|min:0',
            'payment_gateway' => 'required|string|in:pesapal,stripe,mtn,airtel',
            'callback_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Get user for customer info
            $user = \App\Models\User::find($request->user_id);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Create universal payment
            $paymentData = $request->only([
                'payment_type',
                'payment_category',
                'user_id',
                'customer_address',
                'payment_items',
                'description',
                'payment_gateway',
                'payment_method',
                'currency',
            ]);

            // Auto-populate customer info from user if not provided
            $paymentData['customer_name'] = $request->customer_name ?? $user->name;
            $paymentData['customer_phone'] = $request->customer_phone ?? $user->phone ?? $user->phone_number ?? '';
            $paymentData['customer_email'] = $request->customer_email ?? $user->email;

            // Add metadata
            $paymentData['ip_address'] = $request->ip();
            $paymentData['user_agent'] = $request->userAgent();
            $paymentData['created_by'] = Auth::id() ?? $user->id;

            $payment = UniversalPayment::createPayment($paymentData);

            // Initialize payment gateway
            if ($request->payment_gateway === 'pesapal') {
                $pesapalResponse = $this->initializePesapalPayment($payment, $request->callback_url);
                
                if (!$pesapalResponse['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to initialize Pesapal payment',
                        'error' => $pesapalResponse['message'] ?? 'Unknown error',
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment initialized successfully',
                    'data' => [
                        'payment' => $payment,
                        'pesapal' => $pesapalResponse['data'],
                    ],
                ], 201);
            }

            // Other gateways can be added here
            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => ['payment' => $payment],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize Pesapal payment using Pesapal API Client directly
     */
    protected function initializePesapalPayment(UniversalPayment $payment, $callbackUrl = null)
    {
        try {
            // Generate unique merchant reference
            $merchantReference = 'PAYMENT_' . $payment->id . '_' . time();

            // Default callback URL
            if (!$callbackUrl) {
                $callbackUrl = url('/api/universal-payments/callback');
            }

            // Prepare payment data for Pesapal API client
            $paymentData = [
                'order_id' => $payment->id,
                'merchant_reference' => $merchantReference,
                'amount' => (float) $payment->amount,
                'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                'description' => $payment->description ?: ('Universal Payment #' . $payment->id),
                'customer_name' => $payment->customer_name,
                'customer_email' => $payment->customer_email,
                'customer_phone' => $payment->customer_phone,
                'customer_address' => $payment->customer_address ?? '',
                'callback_url' => $callbackUrl
            ];

            Log::info('Pesapal: Initializing universal payment', [
                'payment_id' => $payment->id,
                'merchant_reference' => $merchantReference,
                'amount' => $payment->amount
            ]);

            // Use Pesapal API client directly (injected from PesapalService)
            $apiClient = app(\App\Services\PesapalApiClient::class);
            $response = $apiClient->initializePayment($paymentData);

            if (!$response['success']) {
                throw new \Exception($response['message'] ?? 'Payment initialization failed');
            }

            // Create Pesapal transaction record
            \App\Models\PesapalTransaction::create([
                'order_id' => $payment->id, // Use payment ID in order_id for now
                'order_tracking_id' => $response['order_tracking_id'],
                'merchant_reference' => $merchantReference,
                'amount' => (float) $payment->amount,
                'currency' => \App\Config\PesapalProductionConfig::getCurrency(),
                'status' => 'PENDING',
                'redirect_url' => $response['redirect_url'],
                'callback_url' => $callbackUrl,
                'notification_id' => $response['notification_id'] ?? null,
                'description' => $paymentData['description'],
            ]);

            // Update payment with Pesapal data
            $payment->update([
                'pesapal_order_tracking_id' => $response['order_tracking_id'],
                'pesapal_merchant_reference' => $merchantReference,
                'pesapal_redirect_url' => $response['redirect_url'],
                'pesapal_callback_url' => $callbackUrl,
                'status' => 'PROCESSING',
            ]);

            return [
                'success' => true,
                'data' => [
                    'order_tracking_id' => $response['order_tracking_id'],
                    'redirect_url' => $response['redirect_url'],
                    'merchant_reference' => $merchantReference,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Pesapal initialization failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment status
     * GET /api/universal-payments/status/{id}
     */
    public function checkStatus($id)
    {
        try {
            $payment = UniversalPayment::findOrFail($id);

            Log::info('Checking payment status', [
                'payment_id' => $payment->id,
                'reference' => $payment->payment_reference,
                'current_status' => $payment->status,
                'items_processed' => $payment->items_processed,
                'gateway' => $payment->payment_gateway,
            ]);

            // If Pesapal payment, check with Pesapal API
            if ($payment->payment_gateway === 'pesapal' && $payment->pesapal_order_tracking_id) {
                Log::info('Fetching status from Pesapal', [
                    'payment_id' => $payment->id,
                    'tracking_id' => $payment->pesapal_order_tracking_id,
                ]);

                $pesapalStatus = $this->pesapalService->getTransactionStatus(
                    $payment->pesapal_order_tracking_id
                );

                // Update payment status
                if ($pesapalStatus) {
                    $this->updatePaymentFromPesapal($payment, $pesapalStatus);
                } else {
                    Log::warning('No status received from Pesapal', [
                        'payment_id' => $payment->id,
                    ]);
                }
            }

            // Refresh payment to get latest status
            $payment->refresh();

            // CRITICAL: Auto-process items if payment is COMPLETED and not yet processed
            $processingResult = null;
            if ($payment->isCompleted() && !$payment->items_processed) {
                Log::info('🎯 Payment is COMPLETED, processing items now', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->payment_reference,
                    'status' => $payment->status,
                    'status_code' => $payment->payment_status_code,
                    'payment_type' => $payment->payment_type,
                    'item_count' => count($payment->payment_items ?? []),
                ]);

                try {
                    $processingResult = $payment->processPaymentItems();
                    $payment->refresh(); // Refresh again after processing

                    Log::info('✅ Items processed successfully', [
                        'payment_id' => $payment->id,
                        'result' => $processingResult,
                        'items_processed' => $payment->items_processed,
                    ]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to process items', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            } elseif ($payment->items_processed) {
                Log::info('ℹ️ Items already processed', [
                    'payment_id' => $payment->id,
                    'processed_at' => $payment->items_processed_at,
                ]);
            } elseif (!$payment->isCompleted()) {
                Log::info('⏳ Payment not yet completed', [
                    'payment_id' => $payment->id,
                    'current_status' => $payment->status,
                    'status_code' => $payment->payment_status_code,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment' => $payment,
                    'is_completed' => $payment->isCompleted(),
                    'is_pending' => $payment->isPending(),
                    'is_failed' => $payment->isFailed(),
                    'items_processed' => $payment->items_processed,
                    'processing_result' => $processingResult,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check payment status', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Pesapal callback
     * GET|POST /api/universal-payments/callback
     */
    public function handleCallback(Request $request)
    {
        try {
            $orderTrackingId = $request->input('OrderTrackingId');
            $merchantReference = $request->input('OrderMerchantReference');

            if (!$orderTrackingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing order tracking ID',
                ], 400);
            }

            // Find payment by tracking ID
            $payment = UniversalPayment::where('pesapal_order_tracking_id', $orderTrackingId)->first();

            if (!$payment) {
                Log::warning('Payment not found for callback', [
                    'tracking_id' => $orderTrackingId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // Get status from Pesapal
            $pesapalStatus = $this->pesapalService->getTransactionStatus($orderTrackingId);

            if ($pesapalStatus) {
                $this->updatePaymentFromPesapal($payment, $pesapalStatus);
            }

            return response()->json([
                'success' => true,
                'message' => 'Callback processed',
                'data' => ['payment' => $payment->fresh()],
            ]);

        } catch (\Exception $e) {
            Log::error('Callback handling failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Pesapal IPN
     * POST /api/universal-payments/ipn
     */
    public function handleIPN(Request $request)
    {
        try {
            $orderTrackingId = $request->input('OrderTrackingId');
            $merchantReference = $request->input('OrderMerchantReference');

            if (!$orderTrackingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing order tracking ID',
                ], 400);
            }

            // Find payment
            $payment = UniversalPayment::where('pesapal_order_tracking_id', $orderTrackingId)->first();

            if (!$payment) {
                Log::warning('Payment not found for IPN', [
                    'tracking_id' => $orderTrackingId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }

            // Update IPN tracking
            $payment->update([
                'last_ipn_at' => now(),
                'ipn_count' => $payment->ipn_count + 1,
            ]);

            // Get status from Pesapal API (verify IPN)
            $pesapalStatus = $this->pesapalService->getTransactionStatus($orderTrackingId);

            if ($pesapalStatus) {
                $this->updatePaymentFromPesapal($payment, $pesapalStatus);

                // If completed, process payment items
                if ($payment->isCompleted() && !$payment->items_processed) {
                    $payment->processPaymentItems();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'IPN processed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('IPN handling failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process IPN',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update payment from Pesapal status response
     */
    protected function updatePaymentFromPesapal(UniversalPayment $payment, $pesapalStatus)
    {
        $status = 'PENDING';
        
        // Get status code, prioritizing non-empty values
        $statusCode = $pesapalStatus['payment_status_code'] ?? null;
        if (empty($statusCode)) {
            $statusCode = $pesapalStatus['status_code'] ?? null;
        }
        
        // Convert empty string to null for integer columns
        if ($statusCode === '' || $statusCode === null) {
            $statusCode = null;
        }

        Log::info('Processing Pesapal status', [
            'payment_id' => $payment->id,
            'raw_payment_status_code' => $pesapalStatus['payment_status_code'] ?? 'not_set',
            'raw_status_code' => $pesapalStatus['status_code'] ?? 'not_set',
            'final_status_code' => $statusCode,
            'pesapal_status' => $pesapalStatus['status'] ?? 'not_set',
            'current_payment_status' => $payment->status,
            'items_processed' => $payment->items_processed,
        ]);

        // Determine status based on status code or status field
        // CRITICAL: Only accept status code 1 or explicit COMPLETED status as paid
        if ($statusCode == '1' || $statusCode == 1 || ($pesapalStatus['status'] ?? '') === 'COMPLETED') {
            $status = 'COMPLETED';
            
            Log::info('✅ PAYMENT CONFIRMED AS COMPLETED', [
                'payment_id' => $payment->id,
                'reference' => $payment->payment_reference,
                'status_code' => $statusCode,
                'pesapal_status' => $pesapalStatus['status'] ?? 'not_set',
                'amount' => $payment->total_amount,
                'payment_type' => $payment->payment_type,
            ]);
        } elseif ($statusCode == '2' || $statusCode == 2 || in_array($pesapalStatus['status'] ?? '', ['FAILED', 'INVALID'])) {
            $status = 'FAILED';
            
            Log::warning('❌ PAYMENT FAILED', [
                'payment_id' => $payment->id,
                'reference' => $payment->payment_reference,
                'status_code' => $statusCode,
                'reason' => $pesapalStatus['payment_status_description'] ?? 'Unknown',
            ]);
        } else {
            Log::info('⏳ PAYMENT STILL PENDING', [
                'payment_id' => $payment->id,
                'reference' => $payment->payment_reference,
                'status_code' => $statusCode,
            ]);
        }

        $payment->update([
            'status' => $status,
            'payment_status_code' => $statusCode,
            'pesapal_status_code' => $statusCode,
            'pesapal_response' => $pesapalStatus,
            'status_message' => $pesapalStatus['payment_status_description'] ?? $pesapalStatus['description'] ?? null,
            'confirmation_code' => $pesapalStatus['confirmation_code'] ?? null,
            'payment_date' => $status === 'COMPLETED' ? now() : $payment->payment_date,
            'confirmed_at' => $status === 'COMPLETED' ? now() : $payment->confirmed_at,
            'last_status_check' => now(),
        ]);

        Log::info('Payment updated from Pesapal', [
            'payment_id' => $payment->id,
            'new_status' => $status,
            'status_code' => $statusCode,
            'will_process_items' => ($status === 'COMPLETED' && !$payment->items_processed),
        ]);
    }

    /**
     * Manually process payment items
     * POST /api/universal-payments/{id}/process
     */
    public function processItems($id)
    {
        try {
            $payment = UniversalPayment::findOrFail($id);

            if (!$payment->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is not completed yet',
                ], 400);
            }

            $result = $payment->processPaymentItems();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process payment items', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all payments
     * GET /api/universal-payments
     */
    public function index(Request $request)
    {
        try {
            $query = UniversalPayment::with('user')->orderBy('created_at', 'desc');

            // Filters
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('payment_type')) {
                $query->where('payment_type', $request->payment_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_gateway')) {
                $query->where('payment_gateway', $request->payment_gateway);
            }

            $payments = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $payments,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single payment
     * GET /api/universal-payments/{id}
     */
    public function show($id)
    {
        try {
            $payment = UniversalPayment::with('user')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => ['payment' => $payment],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
