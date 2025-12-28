<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManifestController extends Controller
{
    /**
     * Get comprehensive user manifest data
     * 
     * This endpoint provides all essential information for the user's account
     * including balances, transactions, account details, and app configuration.
     */
    public function getManifest(Request $request)
    {
        try {
            // Get authenticated user
            $userId = Utils::get_user_id($request);
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required. Please log in.');
            }

            $user = User::findOrFail($userId);

            // === USER INFORMATION ===
            $userInfo = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'profile_photo' => $user->avatar,
                'user_type' => $user->user_type,
                'member_code' => $user->member_code,
                'member_since' => $user->created_at ? $user->created_at->format('M Y') : null,
                'member_since_full' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
            ];

            // === ACCOUNT BALANCE ===
            $balance = AccountTransaction::where('user_id', $userId)->sum('amount');
            
            // Calculate balance breakdown
            $balanceBreakdown = [
                'total_balance' => $balance,
                'formatted_balance' => 'UGX ' . number_format($balance, 2),
                'available_balance' => $balance, // Can be different if there are pending transactions
                'formatted_available' => 'UGX ' . number_format($balance, 2),
                'pending_balance' => 0,
                'formatted_pending' => 'UGX 0.00',
            ];

            // === RECENT TRANSACTIONS (Last 10) ===
            $recentTransactions = AccountTransaction::where('user_id', $userId)
                ->with(['creator', 'relatedDisbursement'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'formatted_amount' => $transaction->formatted_amount,
                        'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                        'formatted_date' => $transaction->formatted_date,
                        'description' => $transaction->description,
                        'source' => $transaction->source,
                        'source_label' => $transaction->source_label,
                        'type' => $transaction->type,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // === FINANCIAL STATISTICS ===
            $totalDisbursements = AccountTransaction::where('user_id', $userId)
                ->where('source', 'disbursement')
                ->sum('amount');

            $totalDeposits = AccountTransaction::where('user_id', $userId)
                ->where('source', 'deposit')
                ->sum('amount');

            $totalWithdrawals = abs(AccountTransaction::where('user_id', $userId)
                ->where('source', 'withdrawal')
                ->sum('amount'));

            $totalTransactions = AccountTransaction::where('user_id', $userId)->count();

            $statistics = [
                'total_transactions' => $totalTransactions,
                'total_disbursements' => $totalDisbursements,
                'formatted_disbursements' => 'UGX ' . number_format($totalDisbursements, 2),
                'total_deposits' => $totalDeposits,
                'formatted_deposits' => 'UGX ' . number_format($totalDeposits, 2),
                'total_withdrawals' => $totalWithdrawals,
                'formatted_withdrawals' => 'UGX ' . number_format($totalWithdrawals, 2),
            ];

            // === QUICK STATS ===
            $quickStats = [
                'pending_transactions' => 0, // Can be expanded
                'completed_transactions' => $totalTransactions,
                'monthly_transactions' => AccountTransaction::where('user_id', $userId)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'today_transactions' => AccountTransaction::where('user_id', $userId)
                    ->whereDate('created_at', now()->toDateString())
                    ->count(),
            ];

            // === APP INFORMATION ===
            $appInfo = [
                'app_version' => '100.0.124',
                'app_build' => '124',
                'api_version' => '1.0.0',
                'maintenance_mode' => false,
                'maintenance_message' => null,
                'force_update' => false,
                'minimum_version' => '100.0.100',
                'latest_version' => '100.0.124',
                'update_url' => 'https://play.google.com/store/apps/details?id=com.fao.ffs.mis',
                'support_email' => 'support@fao-ffs-mis.org',
                'support_phone' => '+256700000000',
            ];

            // === SYSTEM FEATURES ===
            $features = [
                'vsla_enabled' => true,
                'farm_management_enabled' => true,
                'marketplace_enabled' => true,
                'advisory_enabled' => true,
                'profiling_enabled' => true,
                'ffs_activities_enabled' => true,
            ];

            // === COMPILE MANIFEST ===
            $manifest = [
                'user' => $userInfo,
                'balance' => $balanceBreakdown,
                'recent_transactions' => $recentTransactions,
                'statistics' => $statistics,
                'quick_stats' => $quickStats,
                'app_info' => $appInfo,
                'features' => $features,
                'timestamp' => now()->toIso8601String(),
                'server_time' => now()->format('Y-m-d H:i:s'),
            ];

            return Utils::success(
                $manifest,
                'Manifest retrieved successfully'
            );

        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve manifest: ' . $e->getMessage());
        }
    }

    /**
     * Get app configuration only (public endpoint)
     */
    public function getAppConfig(Request $request)
    {
        try {
            $appConfig = [
                'app_version' => '100.0.124',
                'app_build' => '124',
                'api_version' => '1.0.0',
                'maintenance_mode' => false,
                'maintenance_message' => null,
                'force_update' => false,
                'minimum_version' => '100.0.100',
                'latest_version' => '100.0.124',
                'update_url' => 'https://play.google.com/store/apps/details?id=com.fao.ffs.mis',
                'support_email' => 'support@fao-ffs-mis.org',
                'support_phone' => '+256700000000',
                'features' => [
                    'vsla_enabled' => true,
                    'farm_management_enabled' => true,
                    'marketplace_enabled' => true,
                    'advisory_enabled' => true,
                    'profiling_enabled' => true,
                    'ffs_activities_enabled' => true,
                ],
            ];

            return Utils::success($appConfig, 'App configuration retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve app config: ' . $e->getMessage());
        }
    }
}
