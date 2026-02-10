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
            $query = FfsTrainingSession::with(['group', 'facilitator', 'createdBy']);

            // Role-based filtering: non-admin users see only their group's sessions
            if ($user && !$user->isAdmin()) {
                if ($user->group_id) {
                    $query->where('group_id', $user->group_id);
                } else {
                    // Facilitators see sessions they facilitate
                    $query->where('facilitator_id', $user->id);
                }
            }

            // Filters
            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }
            if ($request->filled('facilitator_id')) {
                $query->where('facilitator_id', $request->facilitator_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
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
                return [
                    'id' => $session->id,
                    'group_id' => $session->group_id,
                    'group_name' => $session->group ? $session->group->name : null,
                    'facilitator_id' => $session->facilitator_id,
                    'facilitator_name' => $session->facilitator ? $session->facilitator->name : null,
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
                    'expected_participants' => $session->expected_participants,
                    'actual_participants' => $session->actual_participants,
                    'materials_used' => $session->materials_used,
                    'notes' => $session->notes,
                    'photo' => $session->photo,
                    'participants_count' => $session->participants()->count(),
                    'resolutions_count' => $session->resolutions()->count(),
                    'created_at' => $session->created_at,
                ];
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
                'createdBy',
                'participants.user',
                'resolutions.responsiblePerson',
            ])->find($id);

            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            // Permission check
            $user = Auth::user();
            if ($user && !$user->isAdmin()) {
                if ($user->group_id && $session->group_id !== $user->group_id) {
                    return $this->error('You do not have permission to view this session', 403);
                }
            }

            $data = [
                'id' => $session->id,
                'group_id' => $session->group_id,
                'group_name' => $session->group ? $session->group->name : null,
                'facilitator_id' => $session->facilitator_id,
                'facilitator_name' => $session->facilitator ? $session->facilitator->name : null,
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
                'expected_participants' => $session->expected_participants,
                'actual_participants' => $session->actual_participants,
                'materials_used' => $session->materials_used,
                'notes' => $session->notes,
                'challenges' => $session->challenges,
                'recommendations' => $session->recommendations,
                'photo' => $session->photo,
                'created_by_id' => $session->created_by_id,
                'created_at' => $session->created_at,
                'updated_at' => $session->updated_at,
                'participants' => $session->participants->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'user_id' => $p->user_id,
                        'user_name' => $p->user ? $p->user->name : null,
                        'attendance_status' => $p->attendance_status,
                        'attendance_status_text' => $p->attendance_status_text,
                        'remarks' => $p->remarks,
                    ];
                }),
                'resolutions' => $session->resolutions->map(function ($r) {
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
                }),
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Training session retrieved successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve training session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new training session
     * POST /api/ffs-training-sessions
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'group_id' => 'required|exists:ffs_groups,id',
                'title' => 'required|string|max:255',
                'topic' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'session_date' => 'required|date|after_or_equal:today',
                'start_time' => 'nullable',
                'end_time' => 'nullable|after:start_time',
                'venue' => 'nullable|string|max:255',
                'session_type' => 'required|in:classroom,field,demonstration,workshop',
                'expected_participants' => 'nullable|integer|min:0',
                'materials_used' => 'nullable|string',
                'notes' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = Auth::user();

            DB::beginTransaction();

            $session = new FfsTrainingSession();
            $session->group_id = $request->group_id;
            $session->facilitator_id = $request->get('facilitator_id', $user ? $user->id : null);
            $session->title = $request->title;
            $session->description = $request->description;
            $session->topic = $request->topic;
            $session->session_date = $request->session_date;
            $session->start_time = $request->start_time;
            $session->end_time = $request->end_time;
            $session->venue = $request->venue;
            $session->session_type = $request->session_type;
            $session->status = $request->get('status', 'scheduled');
            $session->expected_participants = $request->expected_participants;
            $session->materials_used = $request->materials_used;
            $session->notes = $request->notes;
            $session->photo = $request->photo;
            $session->created_by_id = $user ? $user->id : null;
            $session->save();

            DB::commit();

            return response()->json([
                'code' => 1,
                'message' => 'Training session created successfully',
                'data' => $session->load(['group', 'facilitator']),
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
            $session = FfsTrainingSession::find($id);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'group_id' => 'sometimes|exists:ffs_groups,id',
                'title' => 'sometimes|string|max:255',
                'topic' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'session_date' => 'sometimes|date',
                'start_time' => 'nullable',
                'end_time' => 'nullable',
                'venue' => 'nullable|string|max:255',
                'session_type' => 'sometimes|in:classroom,field,demonstration,workshop',
                'status' => 'sometimes|in:scheduled,ongoing,completed,cancelled',
                'expected_participants' => 'nullable|integer|min:0',
                'actual_participants' => 'nullable|integer|min:0',
                'materials_used' => 'nullable|string',
                'notes' => 'nullable|string',
                'challenges' => 'nullable|string',
                'recommendations' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

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
                'group_id', 'facilitator_id', 'title', 'description', 'topic',
                'session_date', 'start_time', 'end_time', 'venue', 'session_type',
                'status', 'expected_participants', 'actual_participants',
                'materials_used', 'notes', 'challenges', 'recommendations', 'photo',
            ];

            foreach ($fillable as $field) {
                if ($request->has($field)) {
                    $session->{$field} = $request->{$field};
                }
            }

            $session->save();

            DB::commit();

            return response()->json([
                'code' => 1,
                'message' => 'Training session updated successfully',
                'data' => $session->load(['group', 'facilitator']),
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
            $session = FfsTrainingSession::find($id);
            if (!$session) {
                return $this->error('Training session not found', 404);
            }

            $user = Auth::user();
            if ($user && !$user->isAdmin() && $session->facilitator_id !== $user->id) {
                return $this->error('You do not have permission to delete this session', 403);
            }

            // Only allow deleting scheduled/cancelled sessions
            if (!in_array($session->status, ['scheduled', 'cancelled'])) {
                return $this->error('Cannot delete a session that is ongoing or completed', 400);
            }

            DB::beginTransaction();
            $session->participants()->delete();
            $session->resolutions()->delete();
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

            if ($user && !$user->isAdmin()) {
                if ($user->group_id) {
                    $query->where('group_id', $user->group_id);
                } else {
                    $query->where('facilitator_id', $user->id);
                }
            }

            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            $total = (clone $query)->count();
            $scheduled = (clone $query)->where('status', 'scheduled')->count();
            $ongoing = (clone $query)->where('status', 'ongoing')->count();
            $completed = (clone $query)->where('status', 'completed')->count();
            $cancelled = (clone $query)->where('status', 'cancelled')->count();
            $totalParticipants = (clone $query)->sum('actual_participants');
            $avgParticipants = $completed > 0 ? round((clone $query)->where('status', 'completed')->avg('actual_participants'), 1) : 0;

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
                ],
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve stats: ' . $e->getMessage(), 500);
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
                'participants.*.attendance_status' => 'required|in:present,absent,excused,late',
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
