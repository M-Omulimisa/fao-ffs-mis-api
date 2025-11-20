<?php

namespace App\Http\Controllers;

use App\Models\PesapalTransaction;
use App\Models\PesapalIpnLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PesapalAdminController extends Controller
{
    /**
     * Get payment analytics dashboard data
     */
    public function analytics(Request $request)
    {
        $period = $request->get('period', '30'); // Default 30 days
        $startDate = Carbon::now()->subDays($period);

        // Basic statistics
        $stats = [
            'total_transactions' => PesapalTransaction::where('created_at', '>=', $startDate)->count(),
            'successful_transactions' => PesapalTransaction::where('status', 'COMPLETED')
                ->where('created_at', '>=', $startDate)->count(),
            'pending_transactions' => PesapalTransaction::where('status', 'PENDING')
                ->where('created_at', '>=', $startDate)->count(),
            'failed_transactions' => PesapalTransaction::whereIn('status', ['FAILED', 'INVALID'])
                ->where('created_at', '>=', $startDate)->count(),
        ];

        // Revenue statistics
        $revenue = [
            'total_amount' => PesapalTransaction::where('status', 'COMPLETED')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'pending_amount' => PesapalTransaction::where('status', 'PENDING')
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
        ];

        // Daily transaction trends
        $dailyTrends = PesapalTransaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "COMPLETED" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "COMPLETED" THEN amount ELSE 0 END) as revenue')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->get();

        // Payment method breakdown
        $paymentMethods = PesapalTransaction::select('payment_method', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->where('status', 'COMPLETED')
            ->groupBy('payment_method')
            ->get();

        // Recent transactions
        $recentTransactions = PesapalTransaction::with('order')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // IPN activity
        $ipnStats = [
            'total_ipns' => PesapalIpnLog::where('created_at', '>=', $startDate)->count(),
            'successful_ipns' => PesapalIpnLog::where('created_at', '>=', $startDate)
                ->where('response_code', 200)->count(),
            'failed_ipns' => PesapalIpnLog::where('created_at', '>=', $startDate)
                ->where('response_code', '!=', 200)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period . ' days',
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => Carbon::now()->toDateString()
                ],
                'statistics' => $stats,
                'revenue' => $revenue,
                'daily_trends' => $dailyTrends,
                'payment_methods' => $paymentMethods,
                'recent_transactions' => $recentTransactions,
                'ipn_statistics' => $ipnStats
            ]
        ]);
    }

    /**
     * Get transaction details with full audit trail
     */
    public function transactionDetails($transactionId)
    {
        $transaction = PesapalTransaction::with(['order', 'ipnLogs'])
            ->where('id', $transactionId)
            ->orWhere('tracking_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        // Get audit trail
        $auditTrail = PesapalIpnLog::where('order_tracking_id', $transaction->tracking_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => $transaction,
                'audit_trail' => $auditTrail,
                'timeline' => $this->buildTransactionTimeline($transaction, $auditTrail)
            ]
        ]);
    }

    /**
     * Get failed transactions for troubleshooting
     */
    public function failedTransactions(Request $request)
    {
        $period = $request->get('period', '7'); // Default 7 days for failed transactions
        $startDate = Carbon::now()->subDays($period);

        $failedTransactions = PesapalTransaction::with('order')
            ->whereIn('status', ['FAILED', 'INVALID'])
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Group by failure reasons
        $failureReasons = PesapalTransaction::select('status_reason', DB::raw('COUNT(*) as count'))
            ->whereIn('status', ['FAILED', 'INVALID'])
            ->where('created_at', '>=', $startDate)
            ->groupBy('status_reason')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'failed_transactions' => $failedTransactions,
                'failure_reasons' => $failureReasons,
                'summary' => [
                    'total_failed' => $failedTransactions->total(),
                    'period' => $period . ' days'
                ]
            ]
        ]);
    }

    /**
     * Retry a failed transaction
     */
    public function retryTransaction($transactionId)
    {
        $transaction = PesapalTransaction::find($transactionId);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        if (!in_array($transaction->status, ['FAILED', 'INVALID', 'PENDING'])) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction cannot be retried in current status: ' . $transaction->status
            ], 400);
        }

        try {
            // Reset transaction status
            $transaction->status = 'PENDING';
            $transaction->retry_count = ($transaction->retry_count ?? 0) + 1;
            $transaction->save();

            // Log the retry attempt
            PesapalIpnLog::create([
                'order_tracking_id' => $transaction->tracking_id,
                'merchant_reference' => $transaction->merchant_reference,
                'ipn_data' => json_encode(['action' => 'manual_retry', 'admin_user' => auth()->id() ?? 'system']),
                'response_code' => 200,
                'response_message' => 'Manual retry initiated'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction retry initiated',
                'data' => ['transaction_id' => $transaction->id, 'new_status' => 'PENDING']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build transaction timeline from audit trail
     */
    private function buildTransactionTimeline($transaction, $auditTrail)
    {
        $timeline = [];

        // Add transaction creation
        $timeline[] = [
            'timestamp' => $transaction->created_at,
            'event' => 'Transaction Created',
            'status' => 'CREATED',
            'details' => 'Initial transaction record created'
        ];

        // Add IPN events
        foreach ($auditTrail as $log) {
            $ipnData = json_decode($log->ipn_data, true) ?? [];
            
            $timeline[] = [
                'timestamp' => $log->created_at,
                'event' => 'IPN Received',
                'status' => $ipnData['pesapal_notification_type'] ?? 'CHANGE',
                'details' => $log->response_message,
                'response_code' => $log->response_code
            ];
        }

        // Sort by timestamp
        usort($timeline, function($a, $b) {
            return $a['timestamp']->timestamp - $b['timestamp']->timestamp;
        });

        return $timeline;
    }

    /**
     * Export transactions to CSV
     */
    public function exportTransactions(Request $request)
    {
        $period = $request->get('period', '30');
        $status = $request->get('status', 'all');
        $startDate = Carbon::now()->subDays($period);

        $query = PesapalTransaction::with('order')
            ->where('created_at', '>=', $startDate);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV content
        $csvContent = "ID,Order ID,Tracking ID,Merchant Reference,Amount,Currency,Status,Payment Method,Created At,Updated At\n";
        
        foreach ($transactions as $transaction) {
            $csvContent .= implode(',', [
                $transaction->id,
                $transaction->order_id,
                $transaction->tracking_id,
                $transaction->merchant_reference,
                $transaction->amount,
                $transaction->currency,
                $transaction->status,
                $transaction->payment_method ?? 'Unknown',
                $transaction->created_at->toDateTimeString(),
                $transaction->updated_at->toDateTimeString()
            ]) . "\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="pesapal_transactions_' . date('Y-m-d') . '.csv"');
    }
}
