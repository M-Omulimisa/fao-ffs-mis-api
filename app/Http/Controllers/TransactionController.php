<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions
     */
    public function index(Request $request)
    {
        try {
            $query = Transaction::with(['user', 'creator'])
                ->orderBy('created_at', 'desc');

            // Filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by type
            if ($request->has('type') && in_array($request->type, ['DEPOSIT', 'WITHDRAWAL'])) {
                $query->where('type', $request->type);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $query->search($request->search);
            }

            // Date range filter
            if ($request->has('from_date')) {
                $query->whereDate('transaction_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('transaction_date', '<=', $request->to_date);
            }

            // Pagination
            $per_page = $request->input('per_page', 20);
            $transactions = $query->paginate($per_page);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Transactions retrieved successfully.',
                'data' => $transactions
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve transactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created transaction
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:1',
                'type' => 'required|in:DEPOSIT,WITHDRAWAL',
                'description' => 'nullable|string',
                'reference_number' => 'nullable|string|unique:transactions,reference_number',
                'payment_method' => 'nullable|in:CASH,MOBILE_MONEY,BANK_TRANSFER,CHEQUE,OTHER',
                'payment_phone_number' => 'nullable|string',
                'payment_account_number' => 'nullable|string',
                'status' => 'nullable|in:PENDING,COMPLETED,FAILED,CANCELLED',
                'transaction_date' => 'nullable|date',
                'remarks' => 'nullable|string',
                'receipt_photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle file upload if present
            $data = $request->all();
            if ($request->hasFile('receipt_photo')) {
                $data['receipt_photo'] = Utils::upload_images_2($request->file('receipt_photo'), 'receipts');
            }

            // Create transaction using static method
            $transaction = Transaction::createTransaction($data);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Transaction created successfully.',
                'data' => $transaction->load(['user', 'creator'])
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to create transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified transaction
     */
    public function show($id)
    {
        try {
            $transaction = Transaction::with(['user', 'creator', 'updater'])->find($id);

            if (!$transaction) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Transaction not found.',
                ], 404);
            }

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Transaction retrieved successfully.',
                'data' => $transaction
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified transaction
     */
    public function update(Request $request, $id)
    {
        try {
            $transaction = Transaction::find($id);

            if (!$transaction) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Transaction not found.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|exists:users,id',
                'amount' => 'sometimes|numeric|min:1',
                'type' => 'sometimes|in:DEPOSIT,WITHDRAWAL',
                'description' => 'nullable|string',
                'reference_number' => 'nullable|string|unique:transactions,reference_number,' . $id,
                'payment_method' => 'nullable|in:CASH,MOBILE_MONEY,BANK_TRANSFER,CHEQUE,OTHER',
                'payment_phone_number' => 'nullable|string',
                'payment_account_number' => 'nullable|string',
                'status' => 'nullable|in:PENDING,COMPLETED,FAILED,CANCELLED',
                'transaction_date' => 'nullable|date',
                'remarks' => 'nullable|string',
                'receipt_photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Handle file upload if present
            $data = $request->except(['id', 'created_at', 'updated_at', 'deleted_at', '_method', 'created_by']);
            if ($request->hasFile('receipt_photo')) {
                $data['receipt_photo'] = Utils::upload_images_2($request->file('receipt_photo'), 'receipts');
            }

            // Set updated_by
            if (auth()->check()) {
                $data['updated_by'] = auth()->id();
            }

            // Update amount sign based on type if both are provided
            if (isset($data['type']) && isset($data['amount'])) {
                if ($data['type'] == 'WITHDRAWAL') {
                    $data['amount'] = abs($data['amount']) * -1;
                } else {
                    $data['amount'] = abs($data['amount']);
                }
            }

            $transaction->update($data);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Transaction updated successfully.',
                'data' => $transaction->fresh()->load(['user', 'creator', 'updater'])
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to update transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified transaction
     */
    public function destroy($id)
    {
        try {
            $transaction = Transaction::find($id);

            if (!$transaction) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Transaction not found.',
                ], 404);
            }

            $transaction->delete();

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Transaction deleted successfully.',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to delete transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction statistics
     */
    public function stats(Request $request)
    {
        try {
            $query = Transaction::query();

            // Filter by user if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('transaction_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('transaction_date', '<=', $request->to_date);
            }

            $stats = [
                'total_transactions' => (clone $query)->count(),
                'total_deposits' => (clone $query)->where('type', 'DEPOSIT')->count(),
                'total_withdrawals' => (clone $query)->where('type', 'WITHDRAWAL')->count(),
                'total_pending' => (clone $query)->where('status', 'PENDING')->count(),
                'total_completed' => (clone $query)->where('status', 'COMPLETED')->count(),
                'sum_deposits' => abs((clone $query)->where('type', 'DEPOSIT')->where('status', 'COMPLETED')->sum('amount')),
                'sum_withdrawals' => abs((clone $query)->where('type', 'WITHDRAWAL')->where('status', 'COMPLETED')->sum('amount')),
                'net_balance' => (clone $query)->where('status', 'COMPLETED')->sum('amount'),
            ];

            // If specific user requested, get their balance
            if ($request->has('user_id')) {
                $user = User::find($request->user_id);
                if ($user) {
                    $stats['user_balance'] = $user->balance ?? 0;
                    $stats['user_name'] = $user->name;
                }
            }

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Statistics retrieved successfully.',
                'data' => $stats
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user balance
     */
    public function getUserBalance($userId)
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'User not found.',
                ], 404);
            }

            $balance = Transaction::where('user_id', $userId)
                ->where('status', 'COMPLETED')
                ->sum('amount');

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Balance retrieved successfully.',
                'data' => [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'balance' => $balance,
                    'formatted_balance' => 'UGX ' . number_format($balance, 0),
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve balance: ' . $e->getMessage(),
            ], 500);
        }
    }
}
