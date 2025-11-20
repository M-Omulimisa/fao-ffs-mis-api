<?php

namespace App\Http\Controllers;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsuranceSubscriptionController extends Controller
{
    /**
     * Display a listing of insurance subscriptions
     */
    public function index(Request $request)
    {
        try {
            $query = InsuranceSubscription::with(['user', 'insuranceProgram']);

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by program
            if ($request->has('insurance_program_id') && !empty($request->insurance_program_id)) {
                $query->where('insurance_program_id', $request->insurance_program_id);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by payment status
            if ($request->has('payment_status') && !empty($request->payment_status)) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by coverage status
            if ($request->has('coverage_status') && !empty($request->coverage_status)) {
                $query->where('coverage_status', $request->coverage_status);
            }

            // Search by policy number
            if ($request->has('policy_number') && !empty($request->policy_number)) {
                $query->where('policy_number', 'like', '%' . $request->policy_number . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 50);
            $subscriptions = $query->paginate($perPage);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscriptions retrieved successfully.',
                'data' => $subscriptions->items(),
                'pagination' => [
                    'total' => $subscriptions->total(),
                    'per_page' => $subscriptions->perPage(),
                    'current_page' => $subscriptions->currentPage(),
                    'last_page' => $subscriptions->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve insurance subscriptions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created insurance subscription
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'insurance_program_id' => 'required|exists:insurance_programs,id',
                'start_date' => 'required|date',
                'beneficiaries' => 'nullable|json',
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

            // Get the program
            $program = InsuranceProgram::findOrFail($request->insurance_program_id);

            // Check if program is available for enrollment
            if (!$program->isAvailableForEnrollment()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'This insurance program is not currently available for enrollment.',
                ], 400);
            }

            $data = $request->all();
            
            // Calculate end date based on program duration
            $data['end_date'] = date('Y-m-d', strtotime($data['start_date'] . ' + ' . $program->duration_months . ' months'));
            
            // Set initial status
            $data['status'] = 'Active';
            $data['payment_status'] = 'Current';
            $data['coverage_status'] = 'Active';
            
            // Set coverage dates
            $data['coverage_start_date'] = $data['start_date'];
            $data['coverage_end_date'] = $data['end_date'];
            
            // Set premium amount from program
            $data['premium_amount'] = $program->premium_amount;
            
            // Set created_by if authenticated
            if (auth()->check()) {
                $data['created_by'] = auth()->id();
            }

            $subscription = InsuranceSubscription::create($data);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription created successfully.',
                'data' => $subscription->load(['user', 'insuranceProgram']),
            ], 201);
        } catch (\Exception $e) {
            // Check if it's a validation error (like already has active subscription)
            $statusCode = 500;
            if (str_contains($e->getMessage(), 'already has an active')) {
                $statusCode = 400; // Bad Request is more appropriate than 500
            }
            
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(), // Include error field for clarity
            ], $statusCode);
        }
    }

    /**
     * Display the specified insurance subscription
     */
    public function show($id)
    {
        try {
            $subscription = InsuranceSubscription::with([
                'user',
                'insuranceProgram',
                'payments' => function ($query) {
                    $query->orderBy('due_date', 'asc');
                }
            ])->findOrFail($id);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription retrieved successfully.',
                'data' => $subscription,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Insurance subscription not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified insurance subscription
     */
    public function update(Request $request, $id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:Active,Suspended,Cancelled,Expired,Pending',
                'payment_status' => 'sometimes|in:Current,Late,Defaulted',
                'coverage_status' => 'sometimes|in:Active,Suspended,Terminated',
                'beneficiaries' => 'nullable|json',
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
            
            // Set updated_by if authenticated
            if (auth()->check()) {
                $data['updated_by'] = auth()->id();
            }

            $subscription->update($data);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription updated successfully.',
                'data' => $subscription->fresh()->load(['user', 'insuranceProgram']),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to update insurance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified insurance subscription
     */
    public function destroy($id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);
            $subscription->delete();

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to delete insurance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend a subscription
     */
    public function suspend(Request $request, $id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'suspension_reason' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $subscription->update([
                'status' => 'Suspended',
                'coverage_status' => 'Suspended',
                'suspended_date' => now(),
                'suspension_reason' => $request->suspension_reason,
            ]);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription suspended successfully.',
                'data' => $subscription->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to suspend insurance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a suspended subscription
     */
    public function activate($id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);

            if ($subscription->status !== 'Suspended') {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Only suspended subscriptions can be activated.',
                ], 400);
            }

            $subscription->update([
                'status' => 'Active',
                'coverage_status' => 'Active',
                'suspended_date' => null,
                'suspension_reason' => null,
            ]);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription activated successfully.',
                'data' => $subscription->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to activate insurance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancel(Request $request, $id)
    {
        try {
            $subscription = InsuranceSubscription::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'cancellation_reason' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $subscription->update([
                'status' => 'Cancelled',
                'coverage_status' => 'Terminated',
                'cancelled_date' => now(),
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance subscription cancelled successfully.',
                'data' => $subscription->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to cancel insurance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a user's active subscription
     */
    public function getUserSubscription($userId)
    {
        try {
            $subscription = InsuranceSubscription::with([
                'insuranceProgram',
                'payments' => function ($query) {
                    $query->orderBy('due_date', 'desc')->limit(10);
                }
            ])
            ->where('user_id', $userId)
            ->where('status', 'Active')
            ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'No active subscription found for this user.',
                ], 404);
            }

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'User subscription retrieved successfully.',
                'data' => $subscription,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve user subscription: ' . $e->getMessage(),
            ], 500);
        }
    }
}
