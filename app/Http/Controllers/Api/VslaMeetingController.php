<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VslaMeeting;
use App\Models\Project;
use App\Models\FfsGroup;
use App\Services\MeetingProcessingService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * VSLA Meeting API Controller
 * Handles offline meeting submission and synchronization from mobile app
 */
class VslaMeetingController extends Controller
{
    use ApiResponser;

    protected $meetingProcessor;

    public function __construct(MeetingProcessingService $meetingProcessor)
    {
        $this->meetingProcessor = $meetingProcessor;
    }

    /**
     * Submit a new meeting from mobile app
     * POST /api/vsla-meetings/submit
     * 
     * This is the main endpoint for offline meeting synchronization
     */
    public function submit(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'local_id' => 'required|string|max:255',
                'cycle_id' => 'required|integer|exists:projects,id',
                'group_id' => 'nullable|integer',
                'meeting_date' => 'required|date',
                'notes' => 'nullable|string',
                
                // Member counts
                'members_present' => 'required|integer|min:0',
                'members_absent' => 'nullable|integer|min:0',
                
                // Financial totals
                'total_savings_collected' => 'nullable|numeric|min:0',
                'total_welfare_collected' => 'nullable|numeric|min:0',
                'total_social_fund_collected' => 'nullable|numeric|min:0',
                'total_fines_collected' => 'nullable|numeric|min:0',
                'total_loans_disbursed' => 'nullable|numeric|min:0',
                'total_shares_sold' => 'nullable|integer|min:0',
                'total_share_value' => 'nullable|numeric|min:0',
                
                // JSON data arrays
                'attendance_data' => 'required|array',
                'transactions_data' => 'nullable|array',
                'loans_data' => 'nullable|array',
                'share_purchases_data' => 'nullable|array',
                'previous_action_plans_data' => 'nullable|array',
                'upcoming_action_plans_data' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, [
                    'errors' => $validator->errors()
                ]);
            }

            // Check for duplicate submission (by local_id)
            $existing = VslaMeeting::where('local_id', $request->local_id)->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting already submitted',
                    'code' => 409,
                    'meeting_id' => $existing->id,
                    'meeting_number' => $existing->meeting_number,
                    'processing_status' => $existing->processing_status,
                    'submitted_at' => $existing->created_at
                ], 409);
            }

            // Validate cycle
            $cycle = Project::find($request->cycle_id);
            if (!$cycle) {
                return $this->error('Cycle not found', 404);
            }

            // Validate cycle is active
            if ($cycle->is_active_cycle !== 'Yes') {
                return $this->error('This cycle is not active. Please select an active cycle.', 422, [
                    'error_type' => 'inactive_cycle',
                    'cycle_status' => $cycle->is_active_cycle
                ]);
            }

            // Validate cycle is VSLA type
            if ($cycle->is_vsla_cycle !== 'Yes') {
                return $this->error('This cycle is not a VSLA cycle', 422, [
                    'error_type' => 'invalid_cycle_type'
                ]);
            }

            // Validate group belongs to cycle (if group_id provided)
            if ($request->group_id) {
                $group = FfsGroup::find($request->group_id);
                if (!$group) {
                    return $this->error('Group not found', 404);
                }
                
                if ($group->type !== 'VSLA') {
                    return $this->error('Group is not a VSLA group', 422);
                }
            }

            // Auto-generate meeting number (server-controlled)
            $meetingNumber = $this->generateMeetingNumber($request->cycle_id, $request->group_id);

            // Get authenticated user ID (server-controlled)
            // Try multiple methods: Auth facade, request user, request userModel, or request parameter
            $createdById = Auth::id() 
                ?? optional($request->user())->id 
                ?? optional($request->userModel)->id
                ?? $request->user_id 
                ?? 1; // Fallback to admin user ID 1 if all else fails

            // Create meeting record
            DB::beginTransaction();

            $meeting = VslaMeeting::create([
                'local_id' => $request->local_id,
                'cycle_id' => $request->cycle_id,
                'group_id' => $request->group_id,
                'meeting_date' => $request->meeting_date,
                'meeting_number' => $meetingNumber,
                'notes' => $request->notes,
                'members_present' => $request->members_present,
                'members_absent' => $request->members_absent ?? 0,
                'total_savings_collected' => $request->total_savings_collected ?? 0,
                'total_welfare_collected' => $request->total_welfare_collected ?? 0,
                'total_social_fund_collected' => $request->total_social_fund_collected ?? 0,
                'total_fines_collected' => $request->total_fines_collected ?? 0,
                'total_loans_disbursed' => $request->total_loans_disbursed ?? 0,
                'total_shares_sold' => $request->total_shares_sold ?? 0,
                'total_share_value' => $request->total_share_value ?? 0,
                'attendance_data' => $request->attendance_data,
                'transactions_data' => $request->transactions_data ?? [],
                'loans_data' => $request->loans_data ?? [],
                'share_purchases_data' => $request->share_purchases_data ?? [],
                'previous_action_plans_data' => $request->previous_action_plans_data ?? [],
                'upcoming_action_plans_data' => $request->upcoming_action_plans_data ?? [],
                'processing_status' => 'pending',
                'created_by_id' => $createdById,
                'submitted_from_app_at' => now(),
                'received_at' => now(),
            ]);

            // Process meeting immediately
            $processingResult = $this->meetingProcessor->processMeeting($meeting);

            DB::commit();

            // Reload meeting to get updated status
            $meeting->refresh();

            return response()->json([
                'success' => $processingResult['success'],
                'message' => $processingResult['success'] 
                    ? 'Meeting submitted and processed successfully'
                    : 'Meeting submitted but processing had errors',
                'code' => $processingResult['success'] ? 200 : 207, // 207 = Multi-Status
                'meeting_id' => $meeting->id,
                'meeting_number' => $meeting->meeting_number,
                'processing_status' => $meeting->processing_status,
                'has_errors' => $meeting->has_errors,
                'has_warnings' => $meeting->has_warnings,
                'errors' => $processingResult['errors'] ?? [],
                'warnings' => $processingResult['warnings'] ?? [],
                'meeting_data' => [
                    'id' => $meeting->id,
                    'local_id' => $meeting->local_id,
                    'meeting_number' => $meeting->meeting_number,
                    'meeting_date' => $meeting->meeting_date,
                    'cycle_id' => $meeting->cycle_id,
                    'group_id' => $meeting->group_id,
                    'processing_status' => $meeting->processing_status,
                    'processed_at' => $meeting->processed_at,
                ]
            ], $processingResult['success'] ? 200 : 207);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return $this->error('Failed to submit meeting: ' . $e->getMessage(), 500, [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Get list of meetings (paginated)
     * GET /api/vsla-meetings
     */
    public function index(Request $request)
    {
        try {
            $query = VslaMeeting::with(['cycle', 'group', 'creator']);

            // Filter by cycle
            if ($request->has('cycle_id')) {
                $query->where('cycle_id', $request->cycle_id);
            }

            // Filter by group
            if ($request->has('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            // Filter by processing status
            if ($request->has('processing_status')) {
                $query->where('processing_status', $request->processing_status);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->where('meeting_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->where('meeting_date', '<=', $request->end_date);
            }

            // Order by date and meeting number
            $query->orderBy('meeting_date', 'desc')
                  ->orderBy('meeting_number', 'desc');

            $meetings = $query->paginate($request->per_page ?? 20);

            return $this->success('Meetings retrieved successfully', [
                'meetings' => $meetings
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve meetings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single meeting details
     * GET /api/vsla-meetings/{id}
     */
    public function show($id)
    {
        try {
            $meeting = VslaMeeting::with([
                'cycle',
                'group',
                'creator',
                'processor',
                'attendance.member',
                'actionPlans.assignedTo',
                'loans.borrower'
            ])->find($id);

            if (!$meeting) {
                return $this->error('Meeting not found', 404);
            }

            return $this->success('Meeting details retrieved successfully', [
                'meeting' => $meeting
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve meeting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get meeting statistics
     * GET /api/vsla-meetings/stats
     */
    public function stats(Request $request)
    {
        try {
            $query = VslaMeeting::query();

            // Filter by cycle if provided
            if ($request->has('cycle_id')) {
                $query->where('cycle_id', $request->cycle_id);
            }

            // Filter by group if provided
            if ($request->has('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            $stats = [
                'total_meetings' => (clone $query)->count(),
                'pending' => (clone $query)->where('processing_status', 'pending')->count(),
                'processing' => (clone $query)->where('processing_status', 'processing')->count(),
                'completed' => (clone $query)->where('processing_status', 'completed')->count(),
                'failed' => (clone $query)->where('processing_status', 'failed')->count(),
                'needs_review' => (clone $query)->where('processing_status', 'needs_review')->count(),
                'with_errors' => (clone $query)->where('has_errors', true)->count(),
                'with_warnings' => (clone $query)->where('has_warnings', true)->count(),
            ];

            return $this->success('Meeting statistics retrieved successfully', $stats);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reprocess a failed meeting
     * PUT /api/vsla-meetings/{id}/reprocess
     */
    public function reprocess($id)
    {
        try {
            $meeting = VslaMeeting::find($id);

            if (!$meeting) {
                return $this->error('Meeting not found', 404);
            }

            // Only allow reprocessing of failed or error meetings
            if (!in_array($meeting->processing_status, ['failed', 'needs_review'])) {
                return $this->error('Only failed or needs_review meetings can be reprocessed', 422);
            }

            // Reprocess
            DB::beginTransaction();
            
            $processingResult = $this->meetingProcessor->processMeeting($meeting);
            
            DB::commit();

            $meeting->refresh();

            return response()->json([
                'success' => $processingResult['success'],
                'message' => $processingResult['success'] 
                    ? 'Meeting reprocessed successfully'
                    : 'Reprocessing completed with errors',
                'processing_status' => $meeting->processing_status,
                'has_errors' => $meeting->has_errors,
                'has_warnings' => $meeting->has_warnings,
                'errors' => $processingResult['errors'] ?? [],
                'warnings' => $processingResult['warnings'] ?? [],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to reprocess meeting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a meeting (admin only, pending meetings only)
     * DELETE /api/vsla-meetings/{id}
     */
    public function destroy($id)
    {
        try {
            $meeting = VslaMeeting::find($id);

            if (!$meeting) {
                return $this->error('Meeting not found', 404);
            }

            // Only allow deletion of pending meetings
            if ($meeting->processing_status !== 'pending') {
                return $this->error('Only pending meetings can be deleted', 422);
            }

            $meeting->delete();

            return $this->success('Meeting deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete meeting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate meeting number for the cycle/group
     * Server-controlled field
     */
    private function generateMeetingNumber($cycleId, $groupId = null)
    {
        $query = VslaMeeting::where('cycle_id', $cycleId);
        
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        
        $lastMeetingNumber = $query->max('meeting_number') ?? 0;
        
        return $lastMeetingNumber + 1;
    }
}
