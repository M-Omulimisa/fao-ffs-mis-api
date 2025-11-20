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
     * Update transaction status after IPN callback
     */
    public function updateTransactionStatus($orderTrackingId, $statusData)
    {
        try {
            Log::info('Pesapal: Updating transaction status', [
                'order_tracking_id' => $orderTrackingId,
                'status_data' => $statusData
            ]);

            // Find the transaction by order tracking ID
            $transaction = PesapalTransaction::where('order_tracking_id', $orderTrackingId)->first();

            if (!$transaction) {
                throw new \Exception("Transaction not found for order tracking ID: {$orderTrackingId}");
            }

            // Get the latest status from Pesapal API
            $latestStatus = $this->getTransactionStatus($orderTrackingId);

            // Map Pesapal status to our payment status
            $paymentStatus = $this->mapPesapalStatusToPaymentStatus($latestStatus['status_code'] ?? 0);

            // Update transaction
            $transaction->update([
                'status' => $paymentStatus,
                'pesapal_status' => $latestStatus['status_code'] ?? 0,
                'status_message' => $latestStatus['description'] ?? '',
                'last_status_check' => now(),
                'pesapal_response' => json_encode($latestStatus)
            ]);

            // Update associated order
            $order = Order::find($transaction->order_id);
            if ($order) {
                $orderState = $this->mapPaymentStatusToOrderState($paymentStatus);
                
                $order->update([
                    'pesapal_status' => $paymentStatus,
                    'payment_status' => $paymentStatus,
                    'order_state' => $orderState
                ]);

                Log::info('Pesapal: Order updated with new status', [
                    'order_id' => $order->id,
                    'payment_status' => $paymentStatus,
                    'order_state' => $orderState
                ]);
            }

            return [
                'success' => true,
                'transaction' => $transaction,
                'order' => $order,
                'status' => $paymentStatus
            ];

        } catch (\Exception $e) {
            Log::error('Pesapal: Transaction status update failed', [
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

    /**
     * Process IPN callback
     * Handles incoming payment notifications from Pesapal
     */
    public function processIpnCallback($orderTrackingId, $merchantReference = null)
    {
        try {
            Log::info('Pesapal: Processing IPN callback', [
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference
            ]);

            // Create IPN log entry
            $ipnLog = PesapalIpnLog::create([
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
                'processed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_data' => json_encode(request()->all())
            ]);

            // Get latest transaction status
            $statusData = $this->getTransactionStatus($orderTrackingId);

            // Update transaction and order status
            $result = $this->updateTransactionStatus($orderTrackingId, $statusData);

            $ipnLog->update([
                'status_retrieved' => true,
                'status_data' => json_encode($statusData),
                'processing_result' => json_encode($result)
            ]);

            Log::info('Pesapal: IPN callback processed successfully', [
                'order_tracking_id' => $orderTrackingId,
                'status' => $result['status']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Pesapal: IPN callback processing failed', [
                'order_tracking_id' => $orderTrackingId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Map Pesapal status codes to our payment status
     */
    private function mapPesapalStatusToPaymentStatus($pesapalStatus)
    {
        switch ($pesapalStatus) {
            case 0:
                return 'INVALID';
            case 1:
                return 'COMPLETED';
            case 2:
                return 'FAILED';
            case 3:
                return 'REVERSED';
            default:
                return 'PENDING';
        }
    }

    /**
     * Map payment status to order state
     */
    private function mapPaymentStatusToOrderState($paymentStatus)
    {
        switch ($paymentStatus) {
            case 'COMPLETED':
                return 'confirmed';
            case 'FAILED':
            case 'INVALID':
                return 'cancelled';
            case 'REVERSED':
                return 'refunded';
            default:
                return 'pending';
        }
    }

    /**
     * Clean up test data (for development/testing only)
     */
    public function cleanupTestData()
    {
        if (app()->environment('production')) {
            throw new \Exception('Cannot cleanup data in production environment');
        }

        try {
            $deleted = [
                'transactions' => PesapalTransaction::where('merchant_reference', 'like', 'TEST_%')->delete(),
                'ipn_logs' => PesapalIpnLog::where('merchant_reference', 'like', 'TEST_%')->delete(),
                'logs' => PesapalLog::where('action', 'like', '%test%')->delete()
            ];

            Log::info('Pesapal: Test data cleanup completed', $deleted);
            return $deleted;

        } catch (\Exception $e) {
            Log::error('Pesapal: Test data cleanup failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics($startDate = null, $endDate = null)
    {
        $query = PesapalTransaction::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return [
            'total_transactions' => $query->count(),
            'completed_transactions' => $query->clone()->where('status', 'COMPLETED')->count(),
            'pending_transactions' => $query->clone()->where('status', 'PENDING')->count(),
            'failed_transactions' => $query->clone()->where('status', 'FAILED')->count(),
            'total_amount' => $query->clone()->where('status', 'COMPLETED')->sum('amount'),
            'average_amount' => $query->clone()->where('status', 'COMPLETED')->avg('amount')
        ];
    }
}
