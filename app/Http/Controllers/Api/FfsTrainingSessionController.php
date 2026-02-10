<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsTrainingSession;
use App\Models\FfsSessionParticipant;
use App\Models\FfsSessionResolution;
use App\Models\FfsGroup;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FfsTrainingSessionController extends Controller
{
    use ApiResponser;

    // ─────────────────────────────────────────────────────────────
    //  HELPER: Check if user has access to a session
    // ─────────────────────────────────────────────────────────────

    /**
     * Determine whether the given user can access a session.
     * Access is granted if:
     *   1. User is admin
     *   2. User's group_id is one of the session's target groups
     *   3. User is the facilitator or co-facilitator
     *   4. User is the session creator
     */
    private function userCanAccessSession($user, FfsTrainingSession $session): bool
    {
        if (!$user) return false;
        if ($user->isAdmin()) return true;

        // Check target groups (pivot)
        if ($user->group_id) {
            $sessionGroupIds = $session->targetGroups->pluck('id')->toArray();
            // Also check legacy group_id column
            if (in_array($user->group_id, $sessionGroupIds) || $session->group_id == $user->group_id) {
                return true;
            }
        }

        // Facilitator / co-facilitator / creator
        if ($session->facilitator_id == $user->id) return true;
        if ($session->co_facilitator_id == $user->id) return true;
        if ($session->created_by_id == $user->id) return true;

        return false;
    }

    /**
     * Apply role-based scope to a query builder so non-admin users
     * only see sessions they have access to.
     */
    private function applyAccessScope($query, $user)
    {
        // IP scoping: admin users can only see their IP's sessions
        if ($user && $user->ip_id) {
            $query->where('ip_id', $user->ip_id);
        }

        if (!$user || $user->isAdmin()) return;

        $query->where(function ($q) use ($user) {
            // Sessions whose target groups include the user's group
            if ($user->group_id) {
                $q->whereHas('targetGroups', function ($gq) use ($user) {
                    $gq->where('ffs_groups.id', $user->group_id);
                });
                // Also check legacy group_id column
                $q->orWhere('group_id', $user->group_id);
            }
            $q->orWhere('facilitator_id', $user->id)
              ->orWhere('co_facilitator_id', $user->id)
              ->orWhere('created_by_id', $user->id);
        });
    }

    /**
     * Standard serialization for a session (used in index/show).
     */
    private function serializeSession(FfsTrainingSession $session, bool $includeNested = false): array
    {
        $data = [
            'id' => $session->id,
            'group_id' => $session->group_id, // backward compat
            'group_ids' => $session->group_ids,
            'group_names' => $session->group_names,
            'group_name' => $session->group_name, // backward compat
            'facilitator_id' => $session->facilitator_id,
            'facilitator_name' => $session->facilitator_name,
            'co_facilitator_id' => $session->co_facilitator_id,
            'co_facilitator_name' => $session->co_facilitator_name,
            'title' => $session->title,
            'description' => $session->description,
            'topic' => $session->topic,
            'session_date' => $session->session_date,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'venue' => $session->venue,
            'session_type' => $session->session_type,
            'session_type_text' => $session->session_type_text,
            'status' => $session->status,
            'status_text' => $session->status_text,
            'report_status' => $session->report_status ?? 'draft',
            'report_status_text' => $session->report_status_text,
            'expected_participants' => $session->expected_participants,
            'actual_participants' => $session->actual_participants,
            'materials_used' => $session->materials_used,
            'notes' => $session->notes,
            'photo' => $session->photo,
            'participants_count' => $session->participants_count,
            'resolutions_count' => $session->resolutions_count,
            'created_by_id' => $session->created_by_id,
            'created_at' => $session->created_at,
            'updated_at' => $session->updated_at,
        ];

        if ($includeNested) {
            $data['challenges'] = $session->challenges;
            $data['recommendations'] = $session->recommendations;
            $data['submitted_at'] = $session->submitted_at;
            $data['submitted_by_id'] = $session->submitted_by_id;

            $data['participants'] = $session->participants->map(function ($p) {
                return [
                    'id' => $p->id,
                    'user_id' => $p->user_id,
                    'user_name' => $p->user ? $p->user->name : null,
                    'attendance_status' => $p->attendance_status,
                    'attendance_status_text' => $p->attendance_status_text,
                    'remarks' => $p->remarks,
                ];
            });

            $data['resolutions'] = $session->resolutions->map(function ($r) {
                return [
                    'id' => $r->id,
                    'resolution' => $r->resolution,
                    'description' => $r->description,
                    'gap_category' => $r->gap_category,
                    'gap_category_text' => $r->gap_category_text,
                    'responsible_person_id' => $r->responsible_person_id,
                    'responsible_person_name' => $r->responsible_person_name,
                    'target_date' => $r->target_date,
                    'status' => $r->status,
                    'status_text' => $r->status_text,
                    'follow_up_notes' => $r->follow_up_notes,
                    'completed_at' => $r->completed_at,
                    'is_overdue' => $r->is_overdue,
                ];
            });
        }

        return $data;
    }

    // ─────────────────────────────────────────────
    //  TRAINING SESSIONS
    // ─────────────────────────────────────────────

    /**
     * List training sessions (with filters)
     * GET /api/ffs-training-sessions
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = FfsTrainingSession::with(['group', 'facilitator', 'coFacilitator', 'createdBy', 'targetGroups']);

            // Role-based filtering
            $this->applyAccessScope($query, $user);

            // Filters
            if ($request->filled('group_id')) {
                $groupId = $request->group_id;
                $query->where(function ($q) use ($groupId) {
                    $q->whereHas('targetGroups', function ($gq) use ($groupId) {
                        $gq->where('ffs_groups.id', $groupId);
                    })->orWhere('group_id', $groupId);
                });
            }
            if ($request->filled('facilitator_id')) {
                $query->where('facilitator_id', $request->facilitator_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('report_status')) {
                $query->where('report_status', $request->report_status);
            }
            if ($request->filled('session_type')) {
                $query->where('session_type', $request->session_type);
            }
            if ($request->filled('date_from')) {
                $query->where('session_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('session_date', '<=', $request->date_to);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhere('venue', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'session_date');
            $sortDir = $request->get('sort_dir', 'desc');
            $allowedSorts = ['session_date', 'title', 'status', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
            }

            $sessions = $query->get()->map(function ($session) {
                return $this->serializeSession($session);
            });

            return response()->json([
                'code' => 1,
                'message' => 'Training sessions retrieved successfully',
                'data' => $sessions,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve training sessions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a single training session with participants and resolutions
     * GET /api/ffs-training-sessions/{id}
     */
    public function show($id)
    {
        try {
            $session = FfsTrainingSession::with([
                'group',
                'facilitator',
                'coFacilitator',
                'createdBy',
                'submittedBy',
                'targetGroups',
                'participants.user',
                'resolutions.responsiblePerson',
            ])->find($id);

            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            // Permission check
            $user = Auth::user();
            if (!$this->userCanAccessSession($user, $session)) {
                return $this->error('You do not have permission to view this session', 403);
            }

            return response()->json([
                'code' => 1,
                'message' => 'Training session retrieved successfully',
                'data' => $this->serializeSession($session, true),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve training session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new training session
     * POST /api/ffs-training-sessions
     *
     * Accepts group_ids[] (array of target group IDs) OR group_id (single, backward compat).
     * Auto-creates pending participant records for all members of target groups.
     */
    public function store(Request $request)
    {
        try {
            // Accept either group_ids array or single group_id
            $hasGroupIds = $request->has('group_ids') && is_array($request->group_ids) && count($request->group_ids) > 0;

            $rules = [
                'title' => 'required|string|max:255',
                'topic' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'session_date' => 'required|date|after_or_equal:today',
                'start_time' => 'nullable',
                'end_time' => 'nullable|after:start_time',
                'venue' => 'nullable|string|max:255',
                'session_type' => 'required|in:classroom,field,demonstration,workshop,other',
                'expected_participants' => 'nullable|integer|min:0',
                'materials_used' => 'nullable|string',
                'notes' => 'nullable|string',
                'photo' => 'nullable|string',
                'co_facilitator_id' => 'nullable|exists:users,id',
                'facilitator_id' => 'nullable|exists:users,id',
            ];

            if ($hasGroupIds) {
                $rules['group_ids'] = 'required|array|min:1';
                $rules['group_ids.*'] = 'exists:ffs_groups,id';
            } else {
                $rules['group_id'] = 'required|exists:ffs_groups,id';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();

            DB::beginTransaction();

            // Determine the group_ids to use
            $groupIds = $hasGroupIds
                ? array_map('intval', $request->group_ids)
                : [intval($request->group_id)];

            $session = new FfsTrainingSession();
            $session->group_id = $groupIds[0]; // backward compat: primary group
            $session->facilitator_id = $request->get('facilitator_id', $user ? $user->id : null);
            $session->co_facilitator_id = $request->co_facilitator_id;
            $session->title = $request->title;
            $session->description = $request->description;
            $session->topic = $request->topic;
            $session->session_date = $request->session_date;
            $session->start_time = $request->start_time;
            $session->end_time = $request->end_time;
            $session->venue = $request->venue;
            $session->session_type = $request->session_type;
            $session->status = $request->get('status', 'scheduled');
            $session->report_status = FfsTrainingSession::REPORT_STATUS_DRAFT;
            $session->expected_participants = $request->get('expected_participants', 0);
            $session->materials_used = $request->materials_used;
            $session->notes = $request->notes;
            $session->photo = $request->photo;
            $session->created_by_id = $user ? $user->id : null;
            // Inherit IP from creating user
            if ($user && $user->ip_id) {
                $session->ip_id = $user->ip_id;
            }
            $session->save();

            // Sync target groups pivot
            $session->targetGroups()->sync($groupIds);

            // Auto-create pending participant records for all members of target groups
            $memberUserIds = User::whereIn('group_id', $groupIds)
                ->pluck('id')
                ->unique()
                ->toArray();

            if (!empty($memberUserIds)) {
                $pendingRecords = [];
                $now = now();
                foreach ($memberUserIds as $userId) {
                    $pendingRecords[] = [
                        'session_id' => $session->id,
                        'user_id' => $userId,
                        'attendance_status' => FfsSessionParticipant::STATUS_PENDING,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                FfsSessionParticipant::insert($pendingRecords);

                // Set expected_participants from member count if not provided
                if (!$request->expected_participants) {
                    $session->expected_participants = count($memberUserIds);
                    $session->save();
                }
            }

            DB::commit();

            $session->load(['group', 'facilitator', 'coFacilitator', 'targetGroups']);

            return response()->json([
                'code' => 1,
                'message' => 'Training session created successfully',
                'data' => $this->serializeSession($session),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create training session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a training session
     * PUT /api/ffs-training-sessions/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $session = FfsTrainingSession::with('targetGroups')->find($id);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            // Permission check
            $user = Auth::user();
            if (!$this->userCanAccessSession($user, $session)) {
                return $this->error('You do not have permission to update this session', 403);
            }

            // Cannot update a submitted report (unless unsubmitting)
            if ($session->report_status === 'submitted' && !$request->has('report_status')) {
                // Allow only status changes on submitted reports
                $allowedFieldsWhenSubmitted = ['status'];
                $otherFields = array_diff(array_keys($request->except(['_method', '_token'])), $allowedFieldsWhenSubmitted);
                if (!empty($otherFields)) {
                    return $this->error('Cannot modify a submitted report. Unsubmit first.', 422);
                }
            }

            $rules = [
                'title' => 'sometimes|string|max:255',
                'topic' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'session_date' => 'sometimes|date',
                'start_time' => 'nullable',
                'end_time' => 'nullable',
                'venue' => 'nullable|string|max:255',
                'session_type' => 'sometimes|in:classroom,field,demonstration,workshop,other',
                'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
                'expected_participants' => 'nullable|integer|min:0',
                'actual_participants' => 'nullable|integer|min:0',
                'materials_used' => 'nullable|string',
                'notes' => 'nullable|string',
                'challenges' => 'nullable|string',
                'recommendations' => 'nullable|string',
                'photo' => 'nullable|string',
                'co_facilitator_id' => 'nullable|exists:users,id',
                'facilitator_id' => 'sometimes|exists:users,id',
                'group_ids' => 'sometimes|array|min:1',
                'group_ids.*' => 'exists:ffs_groups,id',
                'group_id' => 'sometimes|exists:ffs_groups,id',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validate status transition
            if ($request->has('status') && $request->status !== $session->status) {
                if (!$session->canTransitionTo($request->status)) {
                    return response()->json([
                        'code' => 0,
                        'message' => "Cannot change status from '{$session->status}' to '{$request->status}'. Allowed transitions: " . implode(', ', FfsTrainingSession::getAllowedTransitions()[$session->status] ?? []),
                    ], 422);
                }
            }

            DB::beginTransaction();

            $fillable = [
                'facilitator_id', 'co_facilitator_id', 'title', 'description', 'topic',
                'session_date', 'start_time', 'end_time', 'venue', 'session_type',
                'status', 'expected_participants', 'actual_participants',
                'materials_used', 'notes', 'challenges', 'recommendations', 'photo',
            ];

            foreach ($fillable as $field) {
                if ($request->has($field)) {
                    $session->{$field} = $request->{$field};
                }
            }

            // Handle group_ids change (pivot sync + pending participants for new groups)
            if ($request->has('group_ids') && is_array($request->group_ids)) {
                $newGroupIds = array_map('intval', $request->group_ids);
                $oldGroupIds = $session->targetGroups->pluck('id')->toArray();

                // Update legacy group_id to first in list
                $session->group_id = $newGroupIds[0];

                // Sync pivot
                $session->targetGroups()->sync($newGroupIds);

                // Find newly added groups
                $addedGroupIds = array_diff($newGroupIds, $oldGroupIds);
                if (!empty($addedGroupIds)) {
                    // Create pending participants for members of newly added groups
                    // who don't already have a participant record
                    $existingUserIds = $session->participants()->pluck('user_id')->toArray();
                    $newMemberIds = User::whereIn('group_id', $addedGroupIds)
                        ->whereNotIn('id', $existingUserIds)
                        ->pluck('id')
                        ->toArray();

                    if (!empty($newMemberIds)) {
                        $now = now();
                        $pendingRecords = [];
                        foreach ($newMemberIds as $userId) {
                            $pendingRecords[] = [
                                'session_id' => $session->id,
                                'user_id' => $userId,
                                'attendance_status' => FfsSessionParticipant::STATUS_PENDING,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                        FfsSessionParticipant::insert($pendingRecords);
                    }
                }

                // Optionally remove pending-only participants from removed groups
                $removedGroupIds = array_diff($oldGroupIds, $newGroupIds);
                if (!empty($removedGroupIds)) {
                    $removedUserIds = User::whereIn('group_id', $removedGroupIds)->pluck('id')->toArray();
                    if (!empty($removedUserIds)) {
                        $session->participants()
                            ->whereIn('user_id', $removedUserIds)
                            ->where('attendance_status', FfsSessionParticipant::STATUS_PENDING)
                            ->delete();
                    }
                }
            } elseif ($request->has('group_id')) {
                // Backward compat: single group_id update
                $session->group_id = $request->group_id;
                $session->targetGroups()->sync([$request->group_id]);
            }

            $session->save();

            DB::commit();

            $session->load(['group', 'facilitator', 'coFacilitator', 'targetGroups']);

            return response()->json([
                'code' => 1,
                'message' => 'Training session updated successfully',
                'data' => $this->serializeSession($session),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update training session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a training session
     * DELETE /api/ffs-training-sessions/{id}
     */
    public function destroy($id)
    {
        try {
            $session = FfsTrainingSession::with('targetGroups')->find($id);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            // Permission check
            $user = Auth::user();
            if (!$this->userCanAccessSession($user, $session)) {
                return $this->error('You do not have permission to delete this session', 403);
            }

            // Only allow deleting scheduled/cancelled sessions
            if (!in_array($session->status, ['scheduled', 'cancelled'])) {
                return $this->error('Cannot delete a session that is ongoing or completed', 400);
            }

            DB::beginTransaction();
            $session->participants()->delete();
            $session->resolutions()->delete();
            $session->targetGroups()->detach();
            $session->delete();
            DB::commit();

            return response()->json([
                'code' => 1,
                'message' => 'Training session deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to delete training session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get session stats
     * GET /api/ffs-training-sessions/stats
     */
    public function stats(Request $request)
    {
        try {
            $user = Auth::user();
            $query = FfsTrainingSession::query();

            $this->applyAccessScope($query, $user);

            if ($request->filled('group_id')) {
                $groupId = $request->group_id;
                $query->where(function ($q) use ($groupId) {
                    $q->whereHas('targetGroups', function ($gq) use ($groupId) {
                        $gq->where('ffs_groups.id', $groupId);
                    })->orWhere('group_id', $groupId);
                });
            }

            $total = (clone $query)->count();
            $scheduled = (clone $query)->where('status', 'scheduled')->count();
            $ongoing = (clone $query)->where('status', 'ongoing')->count();
            $completed = (clone $query)->where('status', 'completed')->count();
            $cancelled = (clone $query)->where('status', 'cancelled')->count();
            $totalParticipants = (clone $query)->sum('actual_participants');
            $avgParticipants = $completed > 0 ? round((clone $query)->where('status', 'completed')->avg('actual_participants'), 1) : 0;
            $draftReports = (clone $query)->where('report_status', 'draft')->count();
            $submittedReports = (clone $query)->where('report_status', 'submitted')->count();

            return response()->json([
                'code' => 1,
                'message' => 'Training session stats retrieved',
                'data' => [
                    'total_sessions' => $total,
                    'scheduled' => $scheduled,
                    'ongoing' => $ongoing,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'total_participants' => (int) $totalParticipants,
                    'avg_participants_per_session' => $avgParticipants,
                    'draft_reports' => $draftReports,
                    'submitted_reports' => $submittedReports,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve stats: ' . $e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────
    //  EXPECTED MEMBERS & REPORT WORKFLOW
    // ─────────────────────────────────────────────

    /**
     * Get expected members for a session based on its target groups.
     * GET /api/ffs-training-sessions/{sessionId}/expected-members
     */
    public function expectedMembers($sessionId)
    {
        try {
            $session = FfsTrainingSession::with('targetGroups')->find($sessionId);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            $groupIds = $session->targetGroups->pluck('id')->toArray();
            if (empty($groupIds) && $session->group_id) {
                $groupIds = [$session->group_id];
            }

            $members = User::whereIn('group_id', $groupIds)
                ->select('id', 'first_name', 'last_name', 'name', 'phone_number', 'group_id')
                ->get()
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name ?? trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                        'phone_number' => $u->phone_number,
                        'group_id' => $u->group_id,
                    ];
                });

            return response()->json([
                'code' => 1,
                'message' => 'Expected members retrieved',
                'data' => $members,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve expected members: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit the training session report.
     * POST /api/ffs-training-sessions/{id}/submit-report
     */
    public function submitReport($id)
    {
        try {
            $session = FfsTrainingSession::with('targetGroups')->find($id);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            $user = Auth::user();
            if (!$this->userCanAccessSession($user, $session)) {
                return $this->error('You do not have permission to submit this report', 403);
            }

            if ($session->report_status === 'submitted') {
                return $this->error('Report has already been submitted', 422);
            }

            // Ensure session is completed or ongoing before allowing report submission
            if (!in_array($session->status, ['ongoing', 'completed'])) {
                return $this->error('Session must be ongoing or completed to submit report', 422);
            }

            $session->report_status = FfsTrainingSession::REPORT_STATUS_SUBMITTED;
            $session->submitted_at = now();
            $session->submitted_by_id = $user ? $user->id : null;
            $session->save();

            return response()->json([
                'code' => 1,
                'message' => 'Report submitted successfully',
                'data' => [
                    'report_status' => $session->report_status,
                    'submitted_at' => $session->submitted_at,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to submit report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Un-submit (revert to draft) the training session report.
     * POST /api/ffs-training-sessions/{id}/unsubmit-report
     */
    public function unsubmitReport($id)
    {
        try {
            $session = FfsTrainingSession::with('targetGroups')->find($id);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            $user = Auth::user();
            if (!$this->userCanAccessSession($user, $session)) {
                return $this->error('You do not have permission to unsubmit this report', 403);
            }

            if ($session->report_status !== 'submitted') {
                return $this->error('Report is not submitted', 422);
            }

            $session->report_status = FfsTrainingSession::REPORT_STATUS_DRAFT;
            $session->submitted_at = null;
            $session->submitted_by_id = null;
            $session->save();

            return response()->json([
                'code' => 1,
                'message' => 'Report reverted to draft',
                'data' => [
                    'report_status' => $session->report_status,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to unsubmit report: ' . $e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────
    //  PARTICIPANTS
    // ─────────────────────────────────────────────

    /**
     * List participants for a session
     * GET /api/ffs-training-sessions/{sessionId}/participants
     */
    public function participants($sessionId)
    {
        try {
            $session = FfsTrainingSession::find($sessionId);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            $participants = FfsSessionParticipant::with('user')
                ->where('session_id', $sessionId)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'session_id' => $p->session_id,
                        'user_id' => $p->user_id,
                        'user_name' => $p->user ? $p->user->name : null,
                        'attendance_status' => $p->attendance_status,
                        'attendance_status_text' => $p->attendance_status_text,
                        'remarks' => $p->remarks,
                        'created_at' => $p->created_at,
                    ];
                });

            return response()->json([
                'code' => 1,
                'message' => 'Participants retrieved successfully',
                'data' => $participants,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve participants: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add/update participants for a session (bulk)
     * POST /api/ffs-training-sessions/{sessionId}/participants
     *
     * Expects: { "participants": [ { "user_id": 1, "attendance_status": "present", "remarks": "" }, ... ] }
     */
    public function syncParticipants(Request $request, $sessionId)
    {
        try {
            $session = FfsTrainingSession::find($sessionId);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            // Cannot modify participants of cancelled sessions
            if ($session->status === 'cancelled') {
                return $this->error('Cannot modify participants of a cancelled session', 400);
            }

            $validator = Validator::make($request->all(), [
                'participants' => 'required|array|min:1',
                'participants.*.user_id' => 'required|exists:users,id',
                'participants.*.attendance_status' => 'required|in:pending,present,absent,excused,late',
                'participants.*.remarks' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $results = [];
            foreach ($request->participants as $pData) {
                $participant = FfsSessionParticipant::updateOrCreate(
                    [
                        'session_id' => $sessionId,
                        'user_id' => $pData['user_id'],
                    ],
                    [
                        'attendance_status' => $pData['attendance_status'],
                        'remarks' => $pData['remarks'] ?? null,
                    ]
                );
                $results[] = $participant;
            }

            // Update actual_participants count
            $session->refreshParticipantCount();

            DB::commit();

            return response()->json([
                'code' => 1,
                'message' => count($results) . ' participant(s) synced successfully',
                'data' => [
                    'synced_count' => count($results),
                    'actual_participants' => $session->actual_participants,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to sync participants: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove a participant from a session
     * DELETE /api/ffs-training-sessions/{sessionId}/participants/{participantId}
     */
    public function removeParticipant($sessionId, $participantId)
    {
        try {
            $participant = FfsSessionParticipant::where('session_id', $sessionId)
                ->where('id', $participantId)
                ->first();

            if (!$participant) {
                return $this->error('Participant not found in this session', 404);
            }

            $session = FfsTrainingSession::find($sessionId);

            DB::beginTransaction();
            $participant->delete();
            if ($session) {
                $session->refreshParticipantCount();
            }
            DB::commit();

            return response()->json([
                'code' => 1,
                'message' => 'Participant removed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to remove participant: ' . $e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────
    //  RESOLUTIONS (GAP)
    // ─────────────────────────────────────────────

    /**
     * List resolutions for a session
     * GET /api/ffs-training-sessions/{sessionId}/resolutions
     */
    public function resolutions($sessionId)
    {
        try {
            $session = FfsTrainingSession::find($sessionId);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            $resolutions = FfsSessionResolution::with('responsiblePerson')
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'session_id' => $r->session_id,
                        'resolution' => $r->resolution,
                        'description' => $r->description,
                        'gap_category' => $r->gap_category,
                        'gap_category_text' => $r->gap_category_text,
                        'responsible_person_id' => $r->responsible_person_id,
                        'responsible_person_name' => $r->responsible_person_name,
                        'target_date' => $r->target_date,
                        'status' => $r->status,
                        'status_text' => $r->status_text,
                        'follow_up_notes' => $r->follow_up_notes,
                        'completed_at' => $r->completed_at,
                        'is_overdue' => $r->is_overdue,
                        'created_at' => $r->created_at,
                    ];
                });

            return response()->json([
                'code' => 1,
                'message' => 'Resolutions retrieved successfully',
                'data' => $resolutions,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve resolutions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a resolution for a session
     * POST /api/ffs-training-sessions/{sessionId}/resolutions
     */
    public function storeResolution(Request $request, $sessionId)
    {
        try {
            $session = FfsTrainingSession::find($sessionId);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            // Cannot add resolutions to cancelled sessions
            if ($session->status === 'cancelled') {
                return $this->error('Cannot add resolutions to a cancelled session', 400);
            }

            $validator = Validator::make($request->all(), [
                'resolution' => 'required|string|max:255',
                'description' => 'nullable|string',
                'gap_category' => 'required|in:soil,water,seeds,pest,harvest,storage,marketing,livestock,other',
                'responsible_person_id' => 'nullable|exists:users,id',
                'target_date' => 'nullable|date',
                'status' => 'nullable|in:pending,in_progress,completed,cancelled',
                'follow_up_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();

            $resolution = new FfsSessionResolution();
            $resolution->session_id = $sessionId;
            $resolution->resolution = $request->resolution;
            $resolution->description = $request->description;
            $resolution->gap_category = $request->gap_category;
            $resolution->responsible_person_id = $request->responsible_person_id;
            $resolution->target_date = $request->target_date;
            $resolution->status = $request->get('status', 'pending');
            $resolution->follow_up_notes = $request->follow_up_notes;
            $resolution->created_by_id = $user ? $user->id : null;
            $resolution->save();

            return response()->json([
                'code' => 1,
                'message' => 'Resolution created successfully',
                'data' => $resolution->load('responsiblePerson'),
            ], 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create resolution: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a resolution
     * PUT /api/ffs-training-sessions/{sessionId}/resolutions/{resolutionId}
     */
    public function updateResolution(Request $request, $sessionId, $resolutionId)
    {
        try {
            $resolution = FfsSessionResolution::where('session_id', $sessionId)
                ->where('id', $resolutionId)
                ->first();

            if (!$resolution) {
                return $this->error('Resolution not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'resolution' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'gap_category' => 'sometimes|in:soil,water,seeds,pest,harvest,storage,marketing,livestock,other',
                'responsible_person_id' => 'nullable|exists:users,id',
                'target_date' => 'nullable|date',
                'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
                'follow_up_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $fillable = [
                'resolution', 'description', 'gap_category',
                'responsible_person_id', 'target_date', 'status', 'follow_up_notes',
            ];

            foreach ($fillable as $field) {
                if ($request->has($field)) {
                    $resolution->{$field} = $request->{$field};
                }
            }

            // Auto-set completed_at when marking as completed
            if ($request->has('status') && $request->status === 'completed' && !$resolution->completed_at) {
                $resolution->completed_at = now();
            }
            // Clear completed_at if status changed away from completed
            if ($request->has('status') && $request->status !== 'completed') {
                $resolution->completed_at = null;
            }

            $resolution->save();

            return response()->json([
                'code' => 1,
                'message' => 'Resolution updated successfully',
                'data' => $resolution->load('responsiblePerson'),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to update resolution: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a resolution
     * DELETE /api/ffs-training-sessions/{sessionId}/resolutions/{resolutionId}
     */
    public function destroyResolution($sessionId, $resolutionId)
    {
        try {
            $resolution = FfsSessionResolution::where('session_id', $sessionId)
                ->where('id', $resolutionId)
                ->first();

            if (!$resolution) {
                return $this->error('Resolution not found', 404);
            }

            $resolution->delete();

            return response()->json([
                'code' => 1,
                'message' => 'Resolution deleted successfully',
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to delete resolution: ' . $e->getMessage(), 500);
        }
    }
}
