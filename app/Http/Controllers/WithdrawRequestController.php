<?php

namespace App\Http\Controllers;

use App\Models\WithdrawRequest;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WithdrawRequestController extends Controller
{
    /**
     * Get all withdraw requests for authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Get user ID from authenticated user via JWT token or headers
            $userId = Utils::get_user_id($request);
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required. Please log in.');
            }

            $withdrawRequests = WithdrawRequest::where('user_id', $userId)
                ->with(['processedBy:id,name', 'accountTransaction'])
                ->orderBy('created_at', 'desc')
                ->get();

            return Utils::success($withdrawRequests, 'Withdraw requests retrieved successfully.');

        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve withdraw requests: ' . $e->getMessage());
        }
    }

    /**
     * Create a new withdraw request
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Get user ID from authenticated user via JWT token or headers
            $userId = Utils::get_user_id($request);
            
            // Ensure user ID is integer
            $userId = is_numeric($userId) ? (int) $userId : 0;
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required. Please log in.');
            }

            $user = User::find($userId);
            
            if (!$user) {
                return Utils::error('User account not found.');
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1000',
                'description' => 'nullable|string|max:500',
                'payment_method' => 'required|string|in:mobile_money,bank_transfer',
                'payment_phone_number' => 'required_if:payment_method,mobile_money|string|max:20',
            ]);

            if ($validator->fails()) {
                return Utils::error($validator->errors()->first());
            }

            // Get current balance
            $currentBalance = $user->calculateAccountBalance();

            // Validate sufficient balance
            if ($currentBalance < $request->amount) {
                return Utils::error(
                    'Insufficient balance. Your current balance is UGX ' . number_format($currentBalance, 2),
                    [
                        'current_balance' => $currentBalance,
                        'requested_amount' => $request->amount,
                    ]
                );
            }

            // Check for pending requests
            $pendingRequest = WithdrawRequest::where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if ($pendingRequest) {
                return Utils::error(
                    'You already have a pending withdraw request. Please wait for it to be processed.',
                    $pendingRequest
                );
            }

            // Create withdraw request
            $withdrawRequest = WithdrawRequest::create([
                'user_id' => (int) $userId,
                'amount' => (float) $request->amount,
                'account_balance_before' => (float) $currentBalance,
                'status' => 'pending',
                'description' => $request->description,
                'payment_method' => $request->payment_method,
                'payment_phone_number' => $request->payment_phone_number,
            ]);

            $withdrawRequest->load('user:id,name,email,phone_number');

            return Utils::success(
                $withdrawRequest,
                'Withdraw request created successfully. Please wait for admin approval.'
            );

        } catch (\Exception $e) {
            return Utils::error('Failed to create withdraw request: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific withdraw request
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            // Get user ID from authenticated user via JWT token or headers
            $userId = Utils::get_user_id($request);
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required. Please log in.');
            }

            $withdrawRequest = WithdrawRequest::with(['processedBy:id,name', 'accountTransaction'])
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$withdrawRequest) {
                return Utils::error('Withdraw request not found.');
            }

            return Utils::success($withdrawRequest, 'Withdraw request retrieved successfully.');

        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve withdraw request: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a pending withdraw request
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        try {
            // Get user ID from authenticated user via JWT token or headers
            $userId = Utils::get_user_id($request);
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required. Please log in.');
            }

            $withdrawRequest = WithdrawRequest::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$withdrawRequest) {
                return Utils::error('Withdraw request not found.');
            }

            if ($withdrawRequest->status !== 'pending') {
                return Utils::error('Only pending requests can be cancelled.');
            }

            if ($withdrawRequest->account_transaction_id) {
                return Utils::error('Cannot cancel request with associated transaction.');
            }

            $withdrawRequest->delete();

            return Utils::success(null, 'Withdraw request cancelled successfully.');

        } catch (\Exception $e) {
            return Utils::error('Failed to cancel withdraw request: ' . $e->getMessage());
        }
    }

    /**
     * Get user's account balance and withdrawal summary
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance(Request $request)
    {
        try {
            // Get user ID from authenticated user via JWT token or headers
            $userId = Utils::get_user_id($request);
            
            if (!$userId || $userId < 1) {
                return Utils::error('Authentication required. Please log in.');
            }

            $user = User::findOrFail($userId);

            $currentBalance = $user->calculateAccountBalance();
            $pendingWithdrawals = WithdrawRequest::where('user_id', $userId)
                ->where('status', 'pending')
                ->sum('amount');

            $totalWithdrawn = WithdrawRequest::where('user_id', $userId)
                ->where('status', 'approved')
                ->sum('amount');

            $data = [
                'current_balance' => $currentBalance,
                'pending_withdrawals' => $pendingWithdrawals,
                'available_balance' => $currentBalance - $pendingWithdrawals,
                'total_withdrawn' => $totalWithdrawn,
                'formatted_balance' => 'UGX ' . number_format($currentBalance, 2),
                'formatted_pending' => 'UGX ' . number_format($pendingWithdrawals, 2),
                'formatted_available' => 'UGX ' . number_format($currentBalance - $pendingWithdrawals, 2),
            ];

            return Utils::success($data, 'Balance retrieved successfully.');

        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve balance: ' . $e->getMessage());
        }
    }
}
