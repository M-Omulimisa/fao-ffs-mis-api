<?php

namespace App\Http\Controllers;

use App\Models\MedicalServiceRequest;
use App\Models\User;
use App\Models\InsuranceSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;

class MedicalServiceRequestController extends Controller
{
    /**
     * Display a listing of medical service requests
     */
    public function index(Request $request)
    {
        try {
            $query = MedicalServiceRequest::with(['user', 'insuranceSubscription', 'reviewer'])
                ->orderBy('created_at', 'desc');

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by urgency level
            if ($request->has('urgency_level') && !empty($request->urgency_level)) {
                $query->where('urgency_level', $request->urgency_level);
            }

            // Filter by service type
            if ($request->has('service_type') && !empty($request->service_type)) {
                $query->where('service_type', $request->service_type);
            }

            // Search by reference number
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('symptoms_description', 'like', "%{$search}%")
                        ->orWhere('preferred_hospital', 'like', "%{$search}%");
                });
            }

            // Date range filter
            if ($request->has('from_date') && !empty($request->from_date)) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date') && !empty($request->to_date)) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $requests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Medical service requests retrieved successfully',
                'data' => $requests,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve medical service requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created medical service request
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'insurance_subscription_id' => 'nullable|exists:insurance_subscriptions,id',
                'service_type' => 'required|in:consultation,emergency,lab_test,prescription,surgery,dental,optical,physiotherapy,mental_health,maternity,vaccination,other',
                'service_category' => 'nullable|string',
                'urgency_level' => 'required|in:emergency,urgent,normal',
                'symptoms_description' => 'required|string|min:10',
                'additional_notes' => 'nullable|string',
                'preferred_hospital' => 'nullable|string',
                'preferred_doctor' => 'nullable|string',
                'preferred_date' => 'nullable|date|after_or_equal:today',
                'preferred_time' => 'nullable|date_format:H:i',
                'contact_phone' => 'required|string',
                'contact_email' => 'nullable|email',
                'contact_address' => 'nullable|string',
                'attachments' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();
            
            // Add meta data
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
            $data['status'] = 'pending';

            $serviceRequest = MedicalServiceRequest::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Medical service request submitted successfully',
                'data' => $serviceRequest->load(['user', 'insuranceSubscription']),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit medical service request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified medical service request
     */
    public function show($id)
    {
        try {
            $request = MedicalServiceRequest::with(['user', 'insuranceSubscription', 'reviewer'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Medical service request retrieved successfully',
                'data' => $request,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medical service request not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get request by reference number
     */
    public function getByReference($reference)
    {
        try {
            $request = MedicalServiceRequest::with(['user', 'insuranceSubscription', 'reviewer'])
                ->where('reference_number', $reference)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Medical service request retrieved successfully',
                'data' => $request,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medical service request not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified medical service request (for users)
     */
    public function update(Request $request, $id)
    {
        try {
            $serviceRequest = MedicalServiceRequest::findOrFail($id);

            // Only allow updates if status is pending
            if ($serviceRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update request after it has been reviewed',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'service_type' => 'sometimes|in:consultation,emergency,lab_test,prescription,surgery,dental,optical,physiotherapy,mental_health,maternity,vaccination,other',
                'service_category' => 'nullable|string',
                'urgency_level' => 'sometimes|in:emergency,urgent,normal',
                'symptoms_description' => 'sometimes|string|min:10',
                'additional_notes' => 'nullable|string',
                'preferred_hospital' => 'nullable|string',
                'preferred_doctor' => 'nullable|string',
                'preferred_date' => 'nullable|date|after_or_equal:today',
                'preferred_time' => 'nullable|date_format:H:i',
                'contact_phone' => 'sometimes|string',
                'contact_email' => 'nullable|email',
                'contact_address' => 'nullable|string',
                'attachments' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $serviceRequest->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Medical service request updated successfully',
                'data' => $serviceRequest->load(['user', 'insuranceSubscription']),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update medical service request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Review/Approve/Reject request (admin only)
     */
    public function review(Request $request, $id)
    {
        try {
            $serviceRequest = MedicalServiceRequest::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:approved,rejected,in_progress,completed',
                'admin_feedback' => 'required|string|min:10',
                'assigned_hospital' => 'nullable|string',
                'assigned_doctor' => 'nullable|string',
                'scheduled_date' => 'nullable|date',
                'scheduled_time' => 'nullable|date_format:H:i',
                'appointment_details' => 'nullable|string',
                'estimated_cost' => 'nullable|numeric|min:0',
                'insurance_coverage' => 'nullable|numeric|min:0',
                'patient_payment' => 'nullable|numeric|min:0',
                'reviewed_by' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->only([
                'status',
                'admin_feedback',
                'assigned_hospital',
                'assigned_doctor',
                'scheduled_date',
                'scheduled_time',
                'appointment_details',
                'estimated_cost',
                'insurance_coverage',
                'patient_payment',
                'reviewed_by',
            ]);

            $data['reviewed_at'] = now();

            $serviceRequest->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Medical service request reviewed successfully',
                'data' => $serviceRequest->load(['user', 'insuranceSubscription', 'reviewer']),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to review medical service request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel request (user only)
     */
    public function cancel(Request $request, $id)
    {
        try {
            $serviceRequest = MedicalServiceRequest::findOrFail($id);

            // Only allow cancellation if not completed or already cancelled
            if (in_array($serviceRequest->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a completed or already cancelled request',
                ], 403);
            }

            $serviceRequest->update([
                'status' => 'cancelled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Medical service request cancelled successfully',
                'data' => $serviceRequest,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel medical service request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete request
     */
    public function destroy($id)
    {
        try {
            $serviceRequest = MedicalServiceRequest::findOrFail($id);
            $serviceRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Medical service request deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete medical service request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function stats(Request $request)
    {
        try {
            $query = MedicalServiceRequest::query();

            // Filter by user if provided
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            $stats = [
                'total_requests' => (clone $query)->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
                'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
                'emergency' => (clone $query)->where('urgency_level', 'emergency')->count(),
                'urgent' => (clone $query)->where('urgency_level', 'urgent')->count(),
                'normal' => (clone $query)->where('urgency_level', 'normal')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's requests
     */
    public function getUserRequests($userId)
    {
        try {
            $requests = MedicalServiceRequest::with(['insuranceSubscription', 'reviewer'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'User requests retrieved successfully',
                'data' => $requests,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Update request status with full details
     * This method allows admins to update any aspect of a medical service request
     */
    public function adminUpdateStatus(Request $request, $id)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected,in_progress,completed,cancelled',
                'admin_feedback' => 'nullable|string',
                'assigned_hospital' => 'nullable|string',
                'assigned_doctor' => 'nullable|string',
                'scheduled_date' => 'nullable|date',
                'scheduled_time' => 'nullable|date_format:H:i',
                'estimated_cost' => 'nullable|numeric|min:0',
                'insurance_coverage' => 'nullable|numeric|min:0',
                'patient_payment' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Find the request
            $serviceRequest = MedicalServiceRequest::findOrFail($id);

            // Prevent updating cancelled requests
            if ($serviceRequest->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update a cancelled request',
                ], 400);
            }

            // Track if status is changing
            $statusChanged = $serviceRequest->status !== $request->status;
            $oldStatus = $serviceRequest->status;

            // Update status
            $serviceRequest->status = $request->status;

            // Update admin feedback if provided
            if ($request->has('admin_feedback')) {
                $serviceRequest->admin_feedback = $request->admin_feedback;
            }

            // Update assignment details if provided
            if ($request->has('assigned_hospital')) {
                $serviceRequest->assigned_hospital = $request->assigned_hospital;
            }
            if ($request->has('assigned_doctor')) {
                $serviceRequest->assigned_doctor = $request->assigned_doctor;
            }
            if ($request->has('scheduled_date')) {
                $serviceRequest->scheduled_date = $request->scheduled_date;
            }
            if ($request->has('scheduled_time')) {
                $serviceRequest->scheduled_time = $request->scheduled_time;
            }

            // Update cost details if provided
            if ($request->has('estimated_cost')) {
                $serviceRequest->estimated_cost = $request->estimated_cost;
            }
            if ($request->has('insurance_coverage')) {
                $serviceRequest->insurance_coverage = $request->insurance_coverage;
            }
            if ($request->has('patient_payment')) {
                $serviceRequest->patient_payment = $request->patient_payment;
            }

            // Set reviewed timestamp and reviewer when status changes to approved/rejected
            if (($request->status === 'approved' || $request->status === 'rejected') && $statusChanged) {
                $serviceRequest->reviewed_at = now();
                // Note: You would set reviewed_by if you have admin authentication
                // $serviceRequest->reviewed_by = Auth::id();
            }

            // Save the changes
            $serviceRequest->save();

            // Reload with relationships
            $serviceRequest->load(['user', 'insuranceSubscription', 'reviewer']);

            return response()->json([
                'success' => true,
                'message' => 'Request status updated successfully',
                'data' => $serviceRequest,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Medical service request not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update request status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
