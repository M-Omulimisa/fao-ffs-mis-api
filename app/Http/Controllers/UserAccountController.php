<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAccountController extends Controller
{
    /**
     * Get account dashboard for logged-in user
     */
    public function getUserAccountDashboard(Request $request)
    {
        try {
            // Get user ID from authenticated user via JWT token or headers
            $userId = Utils::get_user_id($request);
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required. Please log in.');
            }

            $user = User::findOrFail($userId);

            // Calculate balance
            $balance = AccountTransaction::where('user_id', $userId)->sum('amount');

            // Get recent transactions
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
                    ];
                });

            // Calculate statistics
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

            return Utils::success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'balance' => $balance,
                'formatted_balance' => 'UGX ' . number_format($balance, 2),
                'recent_transactions' => $recentTransactions,
                'statistics' => [
                    'total_disbursements' => $totalDisbursements,
                    'formatted_disbursements' => 'UGX ' . number_format($totalDisbursements, 2),
                    'total_deposits' => $totalDeposits,
                    'formatted_deposits' => 'UGX ' . number_format($totalDeposits, 2),
                    'total_withdrawals' => $totalWithdrawals,
                    'formatted_withdrawals' => 'UGX ' . number_format($totalWithdrawals, 2),
                    'total_transactions' => $totalTransactions,
                ],
            ], 'Account dashboard retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve account dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Get all users with their account balances (admin only)
     */
    public function getAllUsersWithBalances(Request $request)
    {
        try {
            // Get all users with their account balances
            $users = User::select('id', 'name', 'email', 'phone_number', 'created_at')
                ->get()
                ->map(function ($user) {
                    $balance = AccountTransaction::where('user_id', $user->id)->sum('amount');
                    $transactionCount = AccountTransaction::where('user_id', $user->id)->count();
                    
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'balance' => $balance,
                        'formatted_balance' => 'UGX ' . number_format($balance, 2),
                        'transaction_count' => $transactionCount,
                        'member_since' => $user->created_at->format('M Y'),
                    ];
                });

            // Search filter
            if ($request->has('search') && $request->search) {
                $search = strtolower($request->search);
                $users = $users->filter(function ($user) use ($search) {
                    return stripos($user['name'], $search) !== false ||
                           stripos($user['email'], $search) !== false ||
                           stripos($user['phone_number'], $search) !== false;
                });
            }

            // Sort by balance if requested
            if ($request->input('sort_by') === 'balance') {
                $sortOrder = $request->input('sort_order', 'desc');
                $users = $sortOrder === 'desc' 
                    ? $users->sortByDesc('balance')
                    : $users->sortBy('balance');
            }

            // Calculate summary
            $summary = [
                'total_users' => $users->count(),
                'total_balance' => $users->sum('balance'),
                'formatted_total_balance' => 'UGX ' . number_format($users->sum('balance'), 2),
            ];

            return Utils::success([
                'users' => $users->values(),
                'summary' => $summary,
            ], 'Users with balances retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve users: ' . $e->getMessage());
        }
    }

    /**
     * Get specific user's account dashboard (admin viewing another user)
     */
    public function getUserDashboard(Request $request, $userId)
    {
        // Check if user is admin (only admins can view other users' dashboards)
        $currentUser = Utils::get_user_from_request($request);
        if (!$currentUser || $currentUser->user_type !== 'Admin') {
            return Utils::error('Only administrators can view user account dashboards');
        }

        try {
            $user = User::findOrFail($userId);

            // Calculate balance
            $balance = AccountTransaction::where('user_id', $userId)->sum('amount');

            // Get recent transactions
            $recentTransactions = AccountTransaction::where('user_id', $userId)
                ->with(['creator', 'relatedDisbursement', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'user_id' => $transaction->user_id,
                        'user_name' => $transaction->user ? $transaction->user->name : 'N/A',
                        'amount' => $transaction->amount,
                        'formatted_amount' => $transaction->formatted_amount,
                        'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                        'formatted_date' => $transaction->formatted_date,
                        'description' => $transaction->description,
                        'source' => $transaction->source,
                        'source_label' => $transaction->source_label,
                        'type' => $transaction->type,
                        'related_disbursement_id' => $transaction->related_disbursement_id,
                        'created_by' => $transaction->creator ? $transaction->creator->name : 'System',
                        'created_by_id' => $transaction->created_by_id,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                        'can_edit' => true, // Add permission logic if needed
                        'can_delete' => true, // Add permission logic if needed
                    ];
                });

            // Calculate statistics
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

            return Utils::success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ],
                'balance' => $balance,
                'formatted_balance' => 'UGX ' . number_format($balance, 2),
                'recent_transactions' => $recentTransactions,
                'statistics' => [
                    'total_disbursements' => $totalDisbursements,
                    'formatted_disbursements' => 'UGX ' . number_format($totalDisbursements, 2),
                    'total_deposits' => $totalDeposits,
                    'formatted_deposits' => 'UGX ' . number_format($totalDeposits, 2),
                    'total_withdrawals' => $totalWithdrawals,
                    'formatted_withdrawals' => 'UGX ' . number_format($totalWithdrawals, 2),
                    'total_transactions' => $totalTransactions,
                ],
            ], 'User account dashboard retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve user dashboard: ' . $e->getMessage());
        }
    }
}
