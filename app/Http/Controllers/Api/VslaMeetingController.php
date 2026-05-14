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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
            // cycle_id is optional for submit because backend is authoritative:
            // if group is identified, server determines/creates active cycle.
            $validator = Validator::make($request->all(), [
                'local_id' => 'required|string|max:255',
                'cycle_id' => 'nullable|integer',
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
                'total_loans_repaid' => 'nullable|numeric|min:0',
                'total_shares_sold' => 'nullable|integer|min:0',
                'total_share_value' => 'nullable|numeric|min:0',
                
                // JSON data arrays
                'attendance_data' => 'required|array',
                'transactions_data' => 'nullable|array',
                'loan_repayments_data' => 'nullable|array',
                'social_fund_contributions_data' => 'nullable|array',
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

            // ── Resolve the user's authoritative group ────────────────────────
            // user.group_id ALWAYS takes priority over the submitted group_id
            // to prevent cross-group data leakage when a facilitator switches
            // between different chairpersons / groups.
            $user = Auth::user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            $submittedGroupId = (int) $request->group_id;
            $submittedCycleId = (int) $request->cycle_id;

            $group = null;
            if (!empty($user->group_id) && (int) $user->group_id > 0) {
                $group = FfsGroup::find((int) $user->group_id);
                if ($group && $submittedGroupId > 0 && (int) $group->id !== $submittedGroupId) {
                    Log::warning("[Meeting submit] group_id overridden: submitted={$submittedGroupId}, "
                        . "user.group_id={$group->id} (user #{$user->id})");
                }
            }
            if (!$group && $submittedGroupId > 0) {
                $group = FfsGroup::find($submittedGroupId);
            }

            $groupId = $group ? (int) $group->id : null;

            // Validate group type
            if ($group && !empty($group->type) && !in_array($group->type, ['VSLA', 'FFS'])) {
                return $this->error('Group type "' . $group->type . '" is not supported for VSLA meetings', 422);
            }

            // ── Resolve cycle from backend authority (strict existing cycle) ──
            // If group is identified, ALWAYS resolve cycle from backend.
            // Do NOT auto-create a cycle here; meeting submission requires an
            // already existing active VSLA cycle.
            if ($group) {
                $cycle = Project::where('is_vsla_cycle', 'Yes')
                    ->where('group_id', $group->id)
                    ->where('is_active_cycle', 'Yes')
                    ->where(function ($q) {
                        $q->whereNull('status')
                            ->orWhere('status', '!=', 'completed');
                    })
                    ->latest('start_date')
                    ->first();

                if (!$cycle) {
                    return $this->error('No active cycle found for this group. Please activate a cycle before submitting meetings.', 422, [
                        'error_type' => 'missing_active_cycle',
                        'group_id' => $groupId,
                    ]);
                }

                $cycleId = (int) $cycle->id;

                if ($submittedCycleId > 0 && $submittedCycleId !== $cycleId) {
                    Log::warning("[Meeting submit] client cycle_id overridden by backend: "
                        . "submitted={$submittedCycleId}, resolved={$cycleId} for group #{$groupId} (user #{$user->id})");
                }
            } else {
                // No group resolved — fall back to direct cycle lookup
                $cycle = Project::find($submittedCycleId);

                // If cycle found, try to resolve the group from it
                if ($cycle && $cycle->group_id) {
                    $group   = FfsGroup::find($cycle->group_id);
                    $groupId = $group ? (int) $group->id : ($cycle->group_id ?? $groupId);
                }

                if (!$cycle) {
                    return $this->error('Unable to determine cycle. Please select a group with an active cycle.', 404);
                }

                $cycleId = (int) $cycle->id;
                $groupId = $cycle->group_id ?? $groupId;
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

            $normalizedMeetingDate = Carbon::parse($request->meeting_date)->toDateString();

            $computedTotals = $this->calculateSubmittedTotals(
                $request->input('attendance_data', []),
                $request->input('transactions_data', []),
                $request->input('loan_repayments_data', []),
                $request->input('social_fund_contributions_data', []),
                $request->input('loans_data', []),
                $request->input('share_purchases_data', [])
            );

            // Get authenticated user ID (server-controlled)
            $createdById = $user->id ?? 1;

            // ── Idempotent same-day upsert ─────────────────────────────────────
            // Wrapped in a transaction with a pessimistic lock to prevent races.
            // If an active meeting for this group+date already exists (submitted
            // from a different local_id, i.e. the user went through the flow a
            // second time), we UPDATE it with the newest – and likely more
            // complete – data rather than creating a duplicate.
            DB::beginTransaction();

            $sameDayExisting = null;
            if (!empty($groupId)) {
                $sameDayExisting = VslaMeeting::where('group_id', $groupId)
                    ->whereDate('meeting_date', $normalizedMeetingDate)
                    ->lockForUpdate()
                    ->first();
            }

            if ($sameDayExisting) {
                // Update the existing meeting with the new (more complete) payload.
                $sameDayExisting->fill([
                    'members_present'                => $computedTotals['members_present'],
                    'members_absent'                 => $computedTotals['members_absent'],
                    'total_savings_collected'        => $computedTotals['total_savings_collected'],
                    'total_welfare_collected'        => $computedTotals['total_welfare_collected'],
                    'total_social_fund_collected'    => $computedTotals['total_social_fund_collected'],
                    'total_fines_collected'          => $computedTotals['total_fines_collected'],
                    'total_loans_disbursed'          => $computedTotals['total_loans_disbursed'],
                    'total_loans_repaid'             => $computedTotals['total_loans_repaid'],
                    'total_shares_sold'              => $computedTotals['total_shares_sold'],
                    'total_share_value'              => $computedTotals['total_share_value'],
                    'attendance_data'                => $request->attendance_data,
                    'transactions_data'              => $request->transactions_data ?? [],
                    'loan_repayments_data'           => $request->loan_repayments_data ?? [],
                    'social_fund_contributions_data' => $request->social_fund_contributions_data ?? [],
                    'loans_data'                     => $request->loans_data ?? [],
                    'share_purchases_data'           => $request->share_purchases_data ?? [],
                    'previous_action_plans_data'     => $request->previous_action_plans_data ?? [],
                    'upcoming_action_plans_data'     => $request->upcoming_action_plans_data ?? [],
                    'notes'                          => $request->notes ?? $sameDayExisting->notes,
                    'processing_status'              => 'pending',
                ]);
                $sameDayExisting->save();

                $processingResult = $this->meetingProcessor->processMeeting($sameDayExisting);

                if (!$processingResult['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Meeting update failed due to processing errors',
                        'code' => 422,
                        'errors' => $processingResult['errors'] ?? [],
                        'warnings' => $processingResult['warnings'] ?? [],
                    ], 422);
                }

                DB::commit();
                $sameDayExisting->refresh();

                Log::info("[Meeting submit] Same-day upsert: existing meeting #{$sameDayExisting->id} "
                    . "updated with new local_id={$request->local_id} for group #{$groupId} on {$normalizedMeetingDate}");

                return response()->json([
                    'success' => true,
                    'message' => 'Meeting updated with latest data and processed successfully',
                    'code' => 200,
                    'meeting_id' => $sameDayExisting->id,
                    'meeting_number' => $sameDayExisting->meeting_number,
                    'processing_status' => $sameDayExisting->processing_status,
                    'has_errors' => $sameDayExisting->has_errors,
                    'has_warnings' => $sameDayExisting->has_warnings,
                    'errors' => $processingResult['errors'] ?? [],
                    'warnings' => $processingResult['warnings'] ?? [],
                    'meeting_data' => [
                        'id' => $sameDayExisting->id,
                        'local_id' => $sameDayExisting->local_id,
                        'meeting_number' => $sameDayExisting->meeting_number,
                        'meeting_date' => $sameDayExisting->meeting_date,
                        'cycle_id' => $sameDayExisting->cycle_id,
                        'group_id' => $sameDayExisting->group_id,
                        'processing_status' => $sameDayExisting->processing_status,
                        'processed_at' => $sameDayExisting->processed_at,
                    ],
                ], 200);
            }
            // ── No existing meeting — proceed with normal create ───────────────

            // Auto-generate meeting number (server-controlled)
            $meetingNumber = $this->generateMeetingNumber($cycleId, $groupId);

            $meeting = VslaMeeting::create([
                'local_id' => $request->local_id,
                'cycle_id' => $cycleId,
                'group_id' => $groupId,
                'meeting_date' => $normalizedMeetingDate,
                'meeting_number' => $meetingNumber,
                'notes' => $request->notes,
                'members_present' => $computedTotals['members_present'],
                'members_absent' => $computedTotals['members_absent'],
                'total_savings_collected' => $computedTotals['total_savings_collected'],
                'total_welfare_collected' => $computedTotals['total_welfare_collected'],
                'total_social_fund_collected' => $computedTotals['total_social_fund_collected'],
                'total_fines_collected' => $computedTotals['total_fines_collected'],
                'total_loans_disbursed' => $computedTotals['total_loans_disbursed'],
                'total_loans_repaid' => $computedTotals['total_loans_repaid'],
                'total_shares_sold' => $computedTotals['total_shares_sold'],
                'total_share_value' => $computedTotals['total_share_value'],
                'attendance_data' => $request->attendance_data,
                'transactions_data' => $request->transactions_data ?? [],
                'loan_repayments_data' => $request->loan_repayments_data ?? [],
                'social_fund_contributions_data' => $request->social_fund_contributions_data ?? [],
                'loans_data' => $request->loans_data ?? [],
                'share_purchases_data' => $request->share_purchases_data ?? [],
                'previous_action_plans_data' => $request->previous_action_plans_data ?? [],
                'upcoming_action_plans_data' => $request->upcoming_action_plans_data ?? [],
                'processing_status' => 'pending',
                'created_by_id' => $createdById,
                'ip_id' => $user->ip_id ?? optional($group)->ip_id,
                'submitted_from_app_at' => now(),
                'received_at' => now(),
            ]);

            // Process meeting immediately
            $processingResult = $this->meetingProcessor->processMeeting($meeting);

            // Check if processing succeeded
            if (!$processingResult['success']) {
                // Processing failed - rollback and return meeting with error status
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting submission failed due to processing errors',
                    'code' => 422,
                    'errors' => $processingResult['errors'] ?? [],
                    'warnings' => $processingResult['warnings'] ?? [],
                ], 422);
            }

            // Processing succeeded - commit
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
            
            return $this->error('Failed to submit meeting: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get list of meetings
     * GET /api/vsla-meetings or /api/vsla/meetings
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = VslaMeeting::with(['cycle', 'group', 'creator'])
                ->where('processing_status', 'completed'); // Only show completed meetings

            // IP scoping: only show meetings from user's IP
            if ($user && $user->ip_id) {
                $query->where('ip_id', $user->ip_id);
            }

            // Filter by current user's group if they are not an admin
            if (!$user->isAdmin()) {
                // Get user's VSLA group
                $userGroup = $user->group_id ?? null;
                if ($userGroup) {
                    $query->where('group_id', $userGroup);
                } else {
                    // User has no group, return empty result
                    return response()->json([
                        'code' => 1,
                        'message' => 'Meetings retrieved successfully',
                        'data' => [],
                    ]);
                }
            }

            // Filter by cycle
            if ($request->has('cycle_id')) {
                $query->where('cycle_id', $request->cycle_id);
            }

            // Filter by group (admin only)
            if ($request->has('group_id') && $user->isAdmin()) {
                $query->where('group_id', $request->group_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $status = strtolower($request->status);
                if ($status === 'completed') {
                    $query->where('processing_status', 'completed')
                          ->where('has_errors', false);
                } elseif ($status === 'cancelled') {
                    // Add cancelled logic if needed
                    $query->where('has_errors', true);
                }
            }

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('meeting_number', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('creator', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->sort_by ?? 'meeting_date';
            $sortOrder = $request->sort_order ?? 'desc';
            
            // Map frontend sort fields to database fields
            $sortFieldMap = [
                'meeting_date' => 'meeting_date',
                'meeting_number' => 'meeting_number',
                'total_savings' => 'total_savings_collected',
            ];

            $dbSortField = $sortFieldMap[$sortBy] ?? 'meeting_date';
            $query->orderBy($dbSortField, $sortOrder);

            // Get all meetings (no pagination - keep it simple)
            $meetings = $query->get();

            // Transform data to match mobile app expectations
            $transformedData = $meetings->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'cycle_id' => $meeting->cycle_id,
                    'cycle_name' => $meeting->cycle->name ?? null,
                    'group_id' => $meeting->group_id,
                    'group_name' => $meeting->group->name ?? null,
                    'meeting_number' => $meeting->meeting_number,
                    'meeting_date' => $meeting->meeting_date?->format('Y-m-d'),
                    'location' => $meeting->notes ?? 'N/A',
                    'status' => $meeting->has_errors ? 'cancelled' : 'completed',
                    'chairperson_id' => null,
                    'chairperson_name' => null,
                    'secretary_id' => null,
                    'secretary_name' => null,
                    'treasurer_id' => null,
                    'treasurer_name' => null,
                    'total_attendees' => $meeting->members_present ?? 0,
                    'total_absentees' => $meeting->members_absent ?? 0,
                    'total_savings' => (float) ($meeting->total_savings_collected ?? 0),
                    'total_fines' => (float) ($meeting->total_fines_collected ?? 0),
                    'total_welfare' => (float) ($meeting->total_welfare_collected ?? 0),
                    'total_loans_issued' => (float) ($meeting->total_loans_disbursed ?? 0),
                    'total_loans_repaid' => (float) ($meeting->total_loans_repaid ?? 0),
                    'cash_at_hand' => (float) ($meeting->net_cash_flow ?? 0),
                    'notes' => $meeting->notes,
                    'submitted_by' => $meeting->creator?->name ?? null,
                    'submitted_at' => $meeting->created_at?->format('Y-m-d H:i:s'),
                    'created_at' => $meeting->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $meeting->updated_at?->format('Y-m-d H:i:s'),
                ];
            })->values()->toArray();

            return response()->json([
                'code' => 1,
                'message' => 'Meetings retrieved successfully',
                'data' => $transformedData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve meetings: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get single meeting details
     * GET /api/vsla-meetings/{id} or /api/vsla/meetings/{id}
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
                return response()->json([
                    'code' => 0,
                    'message' => 'Meeting not found',
                    'data' => null,
                ], 404);
            }

            // Check access permission
            $user = Auth::user();
            if (!$user->isAdmin() && $meeting->group_id !== $user->group_id) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You do not have permission to view this meeting',
                    'data' => null,
                ], 403);
            }

            // Transform data to match mobile app expectations.
            // Raw JSON arrays are included so the detail screen can render
            // attendance, transactions, loans, shares and action-plan sections.
            $transformedData = [
                'id' => $meeting->id,
                'cycle_id' => $meeting->cycle_id,
                'cycle_name' => $meeting->cycle->name ?? null,
                'group_id' => $meeting->group_id,
                'group_name' => $meeting->group->name ?? null,
                'meeting_number' => $meeting->meeting_number,
                'meeting_date' => $meeting->meeting_date?->format('Y-m-d'),
                'location' => $meeting->notes ?? 'N/A',
                'status' => $meeting->has_errors ? 'cancelled' : 'completed',
                'chairperson_id' => null,
                'chairperson_name' => null,
                'secretary_id' => null,
                'secretary_name' => null,
                'treasurer_id' => null,
                'treasurer_name' => null,
                'total_attendees' => $meeting->members_present ?? 0,
                'total_absentees' => $meeting->members_absent ?? 0,
                'total_savings' => (float) ($meeting->total_savings_collected ?? 0),
                'total_fines' => (float) ($meeting->total_fines_collected ?? 0),
                'total_welfare' => (float) ($meeting->total_welfare_collected ?? 0),
                'total_loans_issued' => (float) ($meeting->total_loans_disbursed ?? 0),
                'total_loans_repaid' => (float) ($meeting->total_loans_repaid ?? 0),
                'cash_at_hand' => (float) ($meeting->net_cash_flow ?? 0),
                'notes' => $meeting->notes,
                'submitted_by' => $meeting->creator?->name ?? null,
                'submitted_at' => $meeting->created_at?->format('Y-m-d H:i:s'),
                'created_at' => $meeting->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $meeting->updated_at?->format('Y-m-d H:i:s'),
                // Raw detail arrays for the meeting detail screen
                'attendance_data' => $meeting->attendance_data ?? [],
                'transactions_data' => $meeting->transactions_data ?? [],
                'loans_data' => $meeting->loans_data ?? [],
                'loan_repayments_data' => $meeting->loan_repayments_data ?? [],
                'social_fund_contributions_data' => $meeting->social_fund_contributions_data ?? [],
                'share_purchases_data' => $meeting->share_purchases_data ?? [],
                'previous_action_plans_data' => $meeting->previous_action_plans_data ?? [],
                'upcoming_action_plans_data' => $meeting->upcoming_action_plans_data ?? [],
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Meeting details retrieved successfully',
                'data' => $transformedData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve meeting: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get meeting statistics
     * GET /api/vsla-meetings/stats or /api/vsla/meetings/stats
     */
    public function stats(Request $request)
    {
        try {
            $user = Auth::user();
            $query = VslaMeeting::query();

            // Filter by current user's group if they are not an admin
            if (!$user->isAdmin()) {
                $userGroup = $user->group_id ?? null;
                if ($userGroup) {
                    $query->where('group_id', $userGroup);
                } else {
                    // User has no group, return empty stats
                    return response()->json([
                        'code' => 1,
                        'message' => 'Meeting statistics retrieved successfully',
                        'data' => [
                            'total_meetings' => 0,
                            'total_savings' => 0.0,
                            'completed_meetings' => 0,
                            'cancelled_meetings' => 0,
                        ],
                    ]);
                }
            }

            // Filter by cycle if provided
            if ($request->has('cycle_id')) {
                $query->where('cycle_id', $request->cycle_id);
            }

            // Filter by group if provided (admin only)
            if ($request->has('group_id') && $user->isAdmin()) {
                $query->where('group_id', $request->group_id);
            }

            $stats = [
                'total_meetings' => (clone $query)->where('processing_status', 'completed')->count(),
                'total_savings' => (clone $query)->where('processing_status', 'completed')->sum('total_savings_collected'),
                'completed_meetings' => (clone $query)->where('processing_status', 'completed')->where('has_errors', false)->count(),
                'cancelled_meetings' => (clone $query)->where('has_errors', true)->count(),
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Meeting statistics retrieved successfully',
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
                'data' => null,
            ], 500);
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

            if ($processingResult['success']) {
                DB::commit();
            } else {
                DB::rollBack();
            }

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
     * DELETE /api/vsla-meetings/{id} or /api/vsla/meetings/{id}
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Only administrators can delete meetings',
                ], 403);
            }

            $meeting = VslaMeeting::find($id);

            if (!$meeting) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Meeting not found',
                ], 404);
            }

            // Only allow deletion of pending meetings
            if ($meeting->processing_status !== 'pending') {
                return response()->json([
                    'code' => 0,
                    'message' => 'Only pending meetings can be deleted',
                ], 422);
            }

            DB::transaction(function () use ($meeting) {
                // Model deleting hook performs full cascade cleanup.
                $meeting->forceDelete();
            });

            return response()->json([
                'code' => 1,
                'message' => 'Meeting deleted successfully',
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'code' => 0,
                'message' => 'Failed to delete meeting: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export single meeting as a PDF download.
     * GET /api/vsla/meetings/{id}/export-pdf
     */
    public function exportPdf($id)
    {
        try {
            $meeting = VslaMeeting::with([
                'cycle',
                'group',
                'group.implementingPartner',
                'creator',
                'implementingPartner',
            ])->find($id);

            if (!$meeting) {
                return response()->json(['code' => 0, 'message' => 'Meeting not found'], 404);
            }

            $user = Auth::user();
            if ($user && !$user->isAdmin() && (int)$meeting->group_id !== (int)$user->group_id) {
                return response()->json(['code' => 0, 'message' => 'Access denied'], 403);
            }

            $generatedBy = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
                : 'System';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.vsla-meeting-pdf', [
                'meeting'     => $meeting,
                'generatedAt' => now()->format('d/m/Y H:i'),
                'generatedBy' => $generatedBy,
            ]);

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions(['dpi' => 110, 'defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

            $filename = 'vsla-meeting-' . ($meeting->group->name ?? 'group')
                . '-meeting' . $meeting->meeting_number
                . '-' . now()->format('Ymd') . '.pdf';
            $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', $filename);

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Meeting PDF export failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['code' => 0, 'message' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export meetings as a CSV file (filterable).
     * GET /api/vsla-meetings/export  or  GET /api/vsla/meetings/export
     * Query params: group_id, cycle_id, date_from, date_to, status
     */
    public function exportCsv(Request $request)
    {
        try {
            $user  = Auth::user();
            $query = VslaMeeting::with(['group', 'cycle', 'creator']);

            // Scope non-admin users to their own group
            if ($user && !$user->isAdmin() && $user->group_id) {
                $query->where('group_id', $user->group_id);
            }

            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }
            if ($request->filled('cycle_id')) {
                $query->where('cycle_id', $request->cycle_id);
            }
            if ($request->filled('status')) {
                $query->where('processing_status', $request->status);
            }
            if ($request->filled('date_from')) {
                $query->where('meeting_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('meeting_date', '<=', $request->date_to);
            }

            $meetings = $query->orderBy('meeting_date', 'desc')->get();

            $writer = \League\Csv\Writer::fromString();
            $writer->setDelimiter(',');

            // Header row
            $writer->insertOne([
                'Meeting #', 'Group', 'Cycle', 'Meeting Date', 'Status',
                'Members Present', 'Members Absent', 'Attendance Rate (%)',
                'Total Savings (UGX)', 'Total Loans Disbursed (UGX)',
                'Total Loans Repaid (UGX)', 'Total Fines (UGX)',
                'Total Welfare (UGX)', 'Net Cash In (UGX)',
                'Loans Count', 'Repayments Count',
                'Submitted By', 'Created At',
            ]);

            foreach ($meetings as $m) {
                $attendance    = is_array($m->attendance_data)    ? $m->attendance_data    : (json_decode($m->attendance_data ?? '[]', true) ?? []);
                $transactions  = is_array($m->transactions_data)  ? $m->transactions_data  : (json_decode($m->transactions_data ?? '[]', true) ?? []);
                $loans         = is_array($m->loans_data)         ? $m->loans_data         : (json_decode($m->loans_data ?? '[]', true) ?? []);
                $repayments    = is_array($m->loan_repayments_data) ? $m->loan_repayments_data : (json_decode($m->loan_repayments_data ?? '[]', true) ?? []);

                $present = 0; $absent = 0;
                foreach ($attendance as $a) {
                    $isPresent = $a['isPresent'] ?? $a['is_present'] ?? false;
                    if ($isPresent === true || $isPresent === 1 || $isPresent === '1' || $isPresent === 'true') $present++;
                    else $absent++;
                }
                $total = $present + $absent;
                $rate  = $total > 0 ? round(($present / $total) * 100, 1) : 0;

                $savings = $welfare = $fines = 0;
                foreach ($transactions as $t) {
                    $type   = strtolower($t['accountType'] ?? $t['account_type'] ?? '');
                    $amount = (float)($t['amount'] ?? 0);
                    if ($type === 'saving') $savings += $amount;
                    elseif (str_contains($type, 'welfare') || str_contains($type, 'social_fund') || str_contains($type, 'socialfund')) $welfare += $amount;
                    elseif ($type === 'fine') $fines += $amount;
                }

                $disbursed = 0;
                foreach ($loans as $l) {
                    $disbursed += (float)($l['amount'] ?? $l['loanAmount'] ?? $l['principal'] ?? 0);
                }
                $repaid = 0;
                foreach ($repayments as $r) {
                    $repaid += (float)($r['totalAmount'] ?? $r['total_amount'] ?? (($r['principalAmount'] ?? $r['principal_amount'] ?? $r['principal'] ?? 0) + ($r['interestAmount'] ?? $r['interest_amount'] ?? 0)));
                }
                $net = $savings + $welfare + $fines + $repaid - $disbursed;

                $writer->insertOne([
                    $m->meeting_number,
                    $m->group->name ?? 'N/A',
                    $m->cycle->cycle_name ?? $m->cycle->name ?? 'N/A',
                    $m->meeting_date,
                    ucfirst($m->processing_status ?? 'unknown'),
                    $present,
                    $absent,
                    $rate,
                    number_format($savings, 2, '.', ''),
                    number_format($disbursed, 2, '.', ''),
                    number_format($repaid, 2, '.', ''),
                    number_format($fines, 2, '.', ''),
                    number_format($welfare, 2, '.', ''),
                    number_format($net, 2, '.', ''),
                    count($loans),
                    count($repayments),
                    $m->creator ? trim(($m->creator->first_name ?? '') . ' ' . ($m->creator->last_name ?? '')) : 'N/A',
                    $m->created_at ? $m->created_at->format('d/m/Y H:i') : '',
                ]);
            }

            $groupLabel = $request->filled('group_id') ? '-group' . $request->group_id : '';
            $filename = 'vsla-meetings' . $groupLabel . '-' . now()->format('Ymd-His') . '.csv';

            return response($writer->toString(), 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Meeting CSV export failed', ['error' => $e->getMessage()]);
            return response()->json(['code' => 0, 'message' => 'CSV export failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate a full cycle financial summary PDF.
     * GET /api/vsla/cycles/{cycleId}/report-pdf
     */
    public function cycleReport($cycleId)
    {
        try {
            $cycle = Project::where('id', $cycleId)->where('is_vsla_cycle', 'Yes')->first();
            if (!$cycle) {
                return response()->json(['code' => 0, 'message' => 'VSLA cycle not found'], 404);
            }

            $user = Auth::user();
            $group = FfsGroup::find($cycle->group_id);

            // Scope check for non-admins
            if ($user && !$user->isAdmin() && $user->group_id && (int)$cycle->group_id !== (int)$user->group_id) {
                return response()->json(['code' => 0, 'message' => 'Access denied'], 403);
            }

            $meetings = VslaMeeting::with(['creator'])
                ->where('cycle_id', $cycleId)
                ->where('processing_status', 'completed')
                ->orderBy('meeting_number')
                ->get();

            // ── Compute stats ──────────────────────────────────────
            $totalSavings = $totalDisbursed = $totalRepaid = $totalFines = $totalWelfare = 0;
            $totalPresent = $totalPossible = 0;
            $memberSavingsMap = [];
            $memberLoanMap    = [];

            foreach ($meetings as $m) {
                $attendance   = is_array($m->attendance_data)   ? $m->attendance_data   : (json_decode($m->attendance_data ?? '[]', true) ?? []);
                $transactions = is_array($m->transactions_data) ? $m->transactions_data : (json_decode($m->transactions_data ?? '[]', true) ?? []);
                $loans        = is_array($m->loans_data)        ? $m->loans_data        : (json_decode($m->loans_data ?? '[]', true) ?? []);
                $repayments   = is_array($m->loan_repayments_data) ? $m->loan_repayments_data : (json_decode($m->loan_repayments_data ?? '[]', true) ?? []);

                $present = 0;
                foreach ($attendance as $a) {
                    $isPresent = $a['isPresent'] ?? $a['is_present'] ?? false;
                    if ($isPresent === true || $isPresent === 1 || $isPresent === '1' || $isPresent === 'true') $present++;
                }
                $totalPresent   += $present;
                $totalPossible  += count($attendance);

                foreach ($transactions as $t) {
                    $type   = strtolower($t['accountType'] ?? $t['account_type'] ?? '');
                    $amount = (float)($t['amount'] ?? 0);
                    $mid    = $t['memberId'] ?? $t['member_id'] ?? 'unknown';
                    $mname  = $t['memberName'] ?? $t['member_name'] ?? $mid;

                    if ($type === 'saving') {
                        $totalSavings += $amount;
                        if (!isset($memberSavingsMap[$mid])) $memberSavingsMap[$mid] = ['name' => $mname, 'total' => 0, 'count' => 0];
                        $memberSavingsMap[$mid]['total'] += $amount;
                        $memberSavingsMap[$mid]['count']++;
                    } elseif (str_contains($type, 'welfare') || str_contains($type, 'social_fund') || str_contains($type, 'socialfund')) {
                        $totalWelfare += $amount;
                    } elseif ($type === 'fine') {
                        $totalFines += $amount;
                    }
                }

                foreach ($loans as $l) {
                    $amount = (float)($l['amount'] ?? $l['loanAmount'] ?? $l['principal'] ?? 0);
                    $totalDisbursed += $amount;
                    $mid   = $l['memberId'] ?? $l['member_id'] ?? 'unknown';
                    $mname = $l['memberName'] ?? $l['member_name'] ?? $mid;
                    if (!isset($memberLoanMap[$mid])) $memberLoanMap[$mid] = ['name' => $mname, 'disbursed' => 0, 'repaid' => 0, 'loans' => 0];
                    $memberLoanMap[$mid]['disbursed'] += $amount;
                    $memberLoanMap[$mid]['loans']++;
                }

                foreach ($repayments as $r) {
                    $total  = (float)($r['totalAmount'] ?? $r['total_amount']
                        ?? (($r['principalAmount'] ?? $r['principal_amount'] ?? $r['principal'] ?? 0)
                           + ($r['interestAmount'] ?? $r['interest_amount'] ?? 0)));
                    $totalRepaid += $total;
                    $mid = $r['memberId'] ?? $r['member_id'] ?? 'unknown';
                    if (isset($memberLoanMap[$mid])) $memberLoanMap[$mid]['repaid'] += $total;
                }
            }

            $avgAttendance = $totalPossible > 0 ? round(($totalPresent / $totalPossible) * 100, 1) : 0;

            // Sort member savings by total descending for leaderboard
            uasort($memberSavingsMap, fn($a, $b) => $b['total'] <=> $a['total']);
            $memberSavings = array_values($memberSavingsMap);

            // Loan portfolio: outstanding = disbursed - repaid
            $loanPortfolio = array_values(array_map(function ($row) {
                $row['outstanding'] = max(0, $row['disbursed'] - $row['repaid']);
                return $row;
            }, $memberLoanMap));
            usort($loanPortfolio, fn($a, $b) => $b['disbursed'] <=> $a['disbursed']);

            $stats = [
                'total_meetings'       => $meetings->count(),
                'total_savings'        => $totalSavings,
                'total_loans_disbursed'=> $totalDisbursed,
                'total_loans_repaid'   => $totalRepaid,
                'total_fines'          => $totalFines,
                'total_welfare'        => $totalWelfare,
                'avg_attendance_rate'  => $avgAttendance,
                'total_members'        => count($memberSavingsMap),
                'outstanding_loans'    => max(0, $totalDisbursed - $totalRepaid),
                'repayment_rate'       => $totalDisbursed > 0 ? round(($totalRepaid / $totalDisbursed) * 100, 1) : 0,
            ];

            $startDate = $meetings->min('meeting_date') ?? $cycle->start_date ?? 'N/A';
            $endDate   = $meetings->max('meeting_date') ?? now()->format('Y-m-d');
            $reportPeriod = ($startDate !== 'N/A')
                ? Carbon::parse($startDate)->format('d M Y') . ' – ' . Carbon::parse($endDate)->format('d M Y')
                : 'All Meetings';

            $generatedBy = $user
                ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
                : 'System';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.vsla-cycle-report', [
                'cycle'        => $cycle,
                'group'        => $group,
                'meetings'     => $meetings,
                'stats'        => $stats,
                'memberSavings'=> $memberSavings,
                'loanPortfolio'=> $loanPortfolio,
                'reportPeriod' => $reportPeriod,
                'generatedAt'  => now()->format('d/m/Y H:i'),
                'generatedBy'  => $generatedBy,
            ]);

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions(['dpi' => 110, 'defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true]);

            $cycleName = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $cycle->cycle_name ?? $cycle->name ?? 'cycle');
            $filename = 'vsla-cycle-report-' . $cycleName . '-' . now()->format('Ymd') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Cycle report PDF failed', ['cycleId' => $cycleId, 'error' => $e->getMessage()]);
            return response()->json(['code' => 0, 'message' => 'Report generation failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate meeting number for the cycle/group
     * Server-controlled field
     */
    private function generateMeetingNumber($cycleId, $groupId = null)
    {
        // Use lockForUpdate to prevent race conditions when two meetings
        // for the same cycle are submitted concurrently.
        $query = VslaMeeting::where('cycle_id', $cycleId);

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        $lastMeetingNumber = $query->lockForUpdate()->max('meeting_number') ?? 0;

        return $lastMeetingNumber + 1;
    }

    /**
     * Build server-authoritative totals from payload details.
     */
    private function calculateSubmittedTotals(
        array $attendanceData,
        array $transactionsData,
        array $loanRepaymentsData,
        array $socialFundContributionsData,
        array $loansData,
        array $sharePurchasesData
    ): array {
        $membersPresent = 0;
        $membersAbsent = 0;

        foreach ($attendanceData as $attendance) {
            if ($this->asBool($attendance['isPresent'] ?? $attendance['is_present'] ?? false)) {
                $membersPresent++;
            } else {
                $membersAbsent++;
            }
        }

        $totals = [
            'members_present' => $membersPresent,
            'members_absent' => $membersAbsent,
            'total_savings_collected' => 0.0,
            'total_welfare_collected' => 0.0,
            'total_social_fund_collected' => 0.0,
            'total_fines_collected' => 0.0,
            'total_loans_disbursed' => 0.0,
            'total_loans_repaid' => 0.0,
            'total_shares_sold' => 0,
            'total_share_value' => 0.0,
        ];

        $socialFromTransactions = 0.0;
        foreach ($transactionsData as $transaction) {
            $accountType = strtolower((string) ($transaction['accountType'] ?? $transaction['account_type'] ?? ''));
            $amount = $this->asAmount($transaction['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            if ($accountType === 'savings') {
                $totals['total_savings_collected'] += $amount;
            } elseif ($accountType === 'welfare') {
                $totals['total_welfare_collected'] += $amount;
            } elseif ($accountType === 'social_fund') {
                $socialFromTransactions += $amount;
            } elseif ($accountType === 'fine' || $accountType === 'penalty') {
                $totals['total_fines_collected'] += $amount;
            }
        }

        foreach ($loansData as $loan) {
            $totals['total_loans_disbursed'] += $this->asAmount($loan['loan_amount'] ?? $loan['loanAmount'] ?? 0);
        }

        foreach ($loanRepaymentsData as $repayment) {
            $totals['total_loans_repaid'] += $this->asAmount($repayment['amount'] ?? 0);
        }

        foreach ($sharePurchasesData as $sharePurchase) {
            $totals['total_shares_sold'] += (int) ($sharePurchase['number_of_shares'] ?? $sharePurchase['numberOfShares'] ?? 0);

            $totals['total_share_value'] += $this->asAmount(
                $sharePurchase['total_amount_paid']
                    ?? $sharePurchase['totalAmountPaid']
                    ?? 0
            );
        }

        $socialFromContributions = 0.0;
        foreach ($socialFundContributionsData as $contribution) {
            $contributed = $this->asBool($contribution['contributed'] ?? true);
            $amount = $this->asAmount($contribution['amount'] ?? 0);
            if ($contributed && $amount > 0) {
                $socialFromContributions += $amount;
            }
        }

        // Avoid double-counting by taking the richer of either representation.
        $totals['total_social_fund_collected'] = max($socialFromTransactions, $socialFromContributions);

        // Normalize numeric precision.
        foreach ([
            'total_savings_collected',
            'total_welfare_collected',
            'total_social_fund_collected',
            'total_fines_collected',
            'total_loans_disbursed',
            'total_loans_repaid',
            'total_share_value',
        ] as $key) {
            $totals[$key] = round((float) $totals[$key], 2);
        }

        return $totals;
    }

    private function asAmount($value): float
    {
        return round((float) $value, 2);
    }

    private function asBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
