<?php

namespace App\Http\Controllers;

use App\Models\InsuranceSubscriptionPayment;
use App\Models\InsuranceSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsuranceSubscriptionPaymentController extends Controller
{
    /**
     * Display a listing of insurance subscription payments
     */
    public function index(Request $request)
    {
        try {
            $query = InsuranceSubscriptionPayment::with(['insuranceSubscription.insuranceProgram', 'user']);

            // Filter by subscription
            if ($request->has('insurance_subscription_id') && !empty($request->insurance_subscription_id)) {
                $query->where('insurance_subscription_id', $request->insurance_subscription_id);
            }

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by program
            if ($request->has('insurance_program_id') && !empty($request->insurance_program_id)) {
                $query->where('insurance_program_id', $request->insurance_program_id);
            }

            // Filter by payment status
            if ($request->has('payment_status') && !empty($request->payment_status)) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by billing frequency
            if ($request->has('billing_frequency') && !empty($request->billing_frequency)) {
                $query->where('billing_frequency', $request->billing_frequency);
            }

            // Filter by due date range
            if ($request->has('due_date_from') && !empty($request->due_date_from)) {
                $query->where('due_date', '>=', $request->due_date_from);
            }
            if ($request->has('due_date_to') && !empty($request->due_date_to)) {
                $query->where('due_date', '<=', $request->due_date_to);
            }

            // Filter by year
            if ($request->has('year') && !empty($request->year)) {
                $query->where('year', $request->year);
            }

            // Filter by month
            if ($request->has('month_number') && !empty($request->month_number)) {
                $query->where('month_number', $request->month_number);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'due_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 50);
            $payments = $query->paginate($perPage);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription payments retrieved successfully.',
                'data' => $payments->items(),
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve insurance subscription payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified insurance subscription payment
     */
    public function show($id)
    {
        try {
            $payment = InsuranceSubscriptionPayment::with([
                'insuranceSubscription.insuranceProgram',
                'user'
            ])->findOrFail($id);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription payment retrieved successfully.',
                'data' => $payment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Insurance subscription payment not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified insurance subscription payment
     */
    public function update(Request $request, $id)
    {
        try {
            $payment = InsuranceSubscriptionPayment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'payment_status' => 'sometimes|in:Pending,Paid,Partial,Overdue,Waived',
                'paid_amount' => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string',
                'payment_reference' => 'nullable|string',
                'transaction_id' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();
            
            // Remove _method if present
            unset($data['_method']);
            
            // If marking as paid, set payment_date
            if (isset($data['payment_status']) && $data['payment_status'] === 'Paid' && empty($payment->payment_date)) {
                $data['payment_date'] = now();
            }

            $payment->update($data);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription payment updated successfully.',
                'data' => $payment->fresh()->load(['insuranceSubscription.insuranceProgram', 'user']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to update insurance subscription payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark a payment as paid
     */
    public function markAsPaid(Request $request, $id)
    {
        try {
            $payment = InsuranceSubscriptionPayment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'paid_amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'payment_reference' => 'nullable|string',
                'transaction_id' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $payment->markAsPaid(
                $request->paid_amount,
                $request->payment_method,
                $request->payment_reference
            );

            // Update transaction_id if provided
            if ($request->has('transaction_id')) {
                $payment->transaction_id = $request->transaction_id;
                $payment->save();
            }

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Payment marked as paid successfully.',
                'data' => $payment->fresh()->load(['insuranceSubscription.insuranceProgram', 'user']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to mark payment as paid: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overdue payments
     */
    public function getOverdue(Request $request)
    {
        try {
            $query = InsuranceSubscriptionPayment::with(['insuranceSubscription.insuranceProgram', 'user'])
                ->where('payment_status', 'Overdue');

            // Filter by user if provided
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by program if provided
            if ($request->has('insurance_program_id') && !empty($request->insurance_program_id)) {
                $query->where('insurance_program_id', $request->insurance_program_id);
            }

            // Sorting by days overdue
            $query->orderBy('days_overdue', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 50);
            $payments = $query->paginate($perPage);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Overdue payments retrieved successfully.',
                'data' => $payments->items(),
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve overdue payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payments for a specific user
     */
    public function getUserPayments(Request $request, $userId)
    {
        try {
            $query = InsuranceSubscriptionPayment::with(['insuranceSubscription.insuranceProgram'])
                ->where('user_id', $userId);

            // Filter by payment status if provided
            if ($request->has('payment_status') && !empty($request->payment_status)) {
                $query->where('payment_status', $request->payment_status);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'due_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 50);
            $payments = $query->paginate($perPage);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'User payments retrieved successfully.',
                'data' => $payments->items(),
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve user payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment statistics
     */
    public function stats(Request $request)
    {
        try {
            $query = InsuranceSubscriptionPayment::query();

            // Filter by user if provided
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by subscription if provided
            if ($request->has('insurance_subscription_id') && !empty($request->insurance_subscription_id)) {
                $query->where('insurance_subscription_id', $request->insurance_subscription_id);
            }

            // Filter by program if provided
            if ($request->has('insurance_program_id') && !empty($request->insurance_program_id)) {
                $query->where('insurance_program_id', $request->insurance_program_id);
            }

            $stats = [
                'total_payments' => $query->count(),
                'paid_payments' => (clone $query)->where('payment_status', 'Paid')->count(),
                'pending_payments' => (clone $query)->where('payment_status', 'Pending')->count(),
                'overdue_payments' => (clone $query)->where('payment_status', 'Overdue')->count(),
                'partial_payments' => (clone $query)->where('payment_status', 'Partial')->count(),
                'waived_payments' => (clone $query)->where('payment_status', 'Waived')->count(),
                'total_amount' => $query->sum('total_amount'),
                'total_paid' => $query->sum('paid_amount'),
                'total_penalty' => $query->sum('penalty_amount'),
                'total_balance' => $query->sum('total_amount') - $query->sum('paid_amount'),
            ];

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Payment statistics retrieved successfully.',
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve payment statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
