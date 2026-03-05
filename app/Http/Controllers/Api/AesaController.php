<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AesaSession;
use App\Models\AesaObservation;
use App\Models\FfsGroup;
use App\Models\Location;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AesaController extends Controller
{
    use ApiResponser;

    // ========================================================================
    // SESSION ENDPOINTS
    // ========================================================================

    /**
     * List AESA sessions with filters
     * GET /api/aesa-sessions
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = AesaSession::with(['group', 'facilitator', 'createdBy'])
                ->withCount('observations');

            // IP-based access scoping
            if ($user && $user->ip_id) {
                $query->where('ip_id', $user->ip_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by group
            if ($request->has('group_id') && $request->group_id) {
                $query->where('group_id', $request->group_id);
            }

            // Filter by facilitator
            if ($request->has('facilitator_id') && $request->facilitator_id) {
                $query->where('facilitator_id', $request->facilitator_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->where('observation_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->where('observation_date', '<=', $request->date_to);
            }

            // Filter by created_by (for mobile: "my sessions")
            if ($request->has('my_sessions') && $request->my_sessions == '1') {
                $query->where('created_by_id', $user->id);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('data_sheet_number', 'like', "%{$search}%")
                        ->orWhere('facilitator_name', 'like', "%{$search}%")
                        ->orWhere('mini_group_name', 'like', "%{$search}%")
                        ->orWhere('observation_location', 'like', "%{$search}%")
                        ->orWhere('district_text', 'like', "%{$search}%")
                        ->orWhere('sub_county_text', 'like', "%{$search}%")
                        ->orWhere('village_text', 'like', "%{$search}%")
                        ->orWhereHas('group', function ($gq) use ($search) {
                            $gq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Sort
            $sortBy = $request->get('sort_by', 'observation_date');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination or all
            if ($request->has('per_page')) {
                $sessions = $query->paginate($request->per_page);
            } else {
                $sessions = $query->get();
            }

            return $this->success($sessions, 'AESA sessions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve AESA sessions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a single AESA session with all observations
     * GET /api/aesa-sessions/{id}
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $session = AesaSession::with([
                'observations',
                'observations.owner',
                'group',
                'facilitator',
                'district',
                'subCounty',
                'village',
                'implementingPartner',
                'createdBy',
            ])->find($id);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            // IP-based access check
            if ($user && $user->ip_id && $session->ip_id !== $user->ip_id) {
                return $this->error('Unauthorized access', 403);
            }

            return $this->success($session, 'AESA session retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve AESA session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new AESA session
     * POST /api/aesa-sessions
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'nullable|exists:ffs_groups,id',
            'group_name_other' => 'nullable|string|max:255',
            'district_id' => 'nullable|integer',
            'district_text' => 'nullable|string|max:255',
            'sub_county_id' => 'nullable|integer',
            'sub_county_text' => 'nullable|string|max:255',
            'village_id' => 'nullable|integer',
            'village_text' => 'nullable|string|max:255',
            'observation_date' => 'required|date',
            'observation_time' => 'nullable|string',
            'facilitator_id' => 'nullable|exists:users,id',
            'facilitator_name' => 'nullable|string|max:255',
            'mini_group_name' => 'nullable|string|max:255',
            'observation_location' => 'nullable|string|max:255',
            'observation_location_other' => 'nullable|string|max:255',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
            'status' => 'nullable|in:draft,submitted,reviewed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error: ' . $validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $session = AesaSession::create($request->only([
                'group_id', 'group_name_other',
                'district_id', 'district_text',
                'sub_county_id', 'sub_county_text',
                'village_id', 'village_text',
                'observation_date', 'observation_time',
                'facilitator_id', 'facilitator_name',
                'mini_group_name',
                'observation_location', 'observation_location_other',
                'gps_latitude', 'gps_longitude',
                'status',
            ]));

            // If observations are included in the same request (bulk create)
            if ($request->has('observations') && is_array($request->observations)) {
                foreach ($request->observations as $obsData) {
                    $obsData['aesa_session_id'] = $session->id;
                    AesaObservation::create($obsData);
                }
            }

            DB::commit();

            $session->load(['observations', 'group', 'facilitator', 'createdBy']);

            return $this->success($session, 'AESA session created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create AESA session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an AESA session
     * PUT /api/aesa-sessions/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $session = AesaSession::find($id);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            // IP-based access check
            if ($user && $user->ip_id && $session->ip_id !== $user->ip_id) {
                return $this->error('Unauthorized access', 403);
            }

            // Only draft sessions can be edited
            if ($session->status !== 'draft') {
                return $this->error('Only draft sessions can be edited. This session is ' . $session->status . '.', 422);
            }

            $validator = Validator::make($request->all(), [
                'group_id' => 'nullable|exists:ffs_groups,id',
                'observation_date' => 'nullable|date',
                'observation_time' => 'nullable|string',
                'facilitator_id' => 'nullable|exists:users,id',
                'mini_group_name' => 'nullable|string|max:255',
                'observation_location' => 'nullable|string|max:255',
                'status' => 'nullable|in:draft,submitted,reviewed',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error: ' . $validator->errors()->first(), 422);
            }

            $session->update($request->only([
                'group_id', 'group_name_other',
                'district_id', 'district_text',
                'sub_county_id', 'sub_county_text',
                'village_id', 'village_text',
                'observation_date', 'observation_time',
                'facilitator_id', 'facilitator_name',
                'mini_group_name',
                'observation_location', 'observation_location_other',
                'gps_latitude', 'gps_longitude',
                'status',
            ]));

            $session->load(['observations', 'group', 'facilitator', 'createdBy']);

            return $this->success($session, 'AESA session updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update AESA session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an AESA session
     * DELETE /api/aesa-sessions/{id}
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $session = AesaSession::find($id);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            // Only allow deleting draft sessions or by the creator
            if ($session->status !== 'draft' && $session->created_by_id !== $user->id) {
                return $this->error('Only draft sessions can be deleted', 403);
            }

            $session->delete(); // Soft delete; observations cascade via FK

            return $this->success(null, 'AESA session deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete AESA session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit a session (change status from draft to submitted)
     * POST /api/aesa-sessions/{id}/submit
     */
    public function submit($id)
    {
        try {
            $user = Auth::user();
            $session = AesaSession::find($id);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if ($session->status !== 'draft') {
                return $this->error('Only draft sessions can be submitted', 400);
            }

            // Ensure at least one observation exists
            if ($session->observations()->count() === 0) {
                return $this->error('Cannot submit a session without any observations', 400);
            }

            $session->update(['status' => 'submitted']);
            $session->load(['observations', 'group', 'facilitator', 'createdBy']);

            return $this->success($session, 'AESA session submitted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to submit AESA session: ' . $e->getMessage(), 500);
        }
    }

    // ========================================================================
    // OBSERVATION ENDPOINTS
    // ========================================================================

    /**
     * List observations for a session
     * GET /api/aesa-sessions/{sessionId}/observations
     */
    public function observations($sessionId)
    {
        try {
            $session = AesaSession::find($sessionId);
            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            $observations = $session->observations()
                ->with(['owner', 'createdBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success($observations, 'Observations retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve observations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a single observation
     * GET /api/aesa-observations/{id}
     */
    public function showObservation($id)
    {
        try {
            $observation = AesaObservation::with(['session', 'session.group', 'owner', 'createdBy'])->find($id);

            if (!$observation) {
                return $this->error('Observation not found', 404);
            }

            return $this->success($observation, 'Observation retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new observation for a session
     * POST /api/aesa-sessions/{sessionId}/observations
     */
    public function storeObservation(Request $request, $sessionId)
    {
        try {
            $session = AesaSession::find($sessionId);
            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            // Only allow adding observations to draft sessions
            if ($session->status !== 'draft') {
                return $this->error('Cannot add observations to a ' . $session->status . ' session.', 422);
            }

            $validator = Validator::make($request->all(), [
                'animal_id_tag' => 'nullable|string|max:255',
                'animal_type' => 'nullable|string|max:255',
                'breed' => 'nullable|string|max:255',
                'sex' => 'nullable|string|max:50',
                'age_category' => 'nullable|string|max:50',
                'weight_kg' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'owner_name' => 'nullable|string|max:255',
                'animal_health_status' => 'nullable|string|max:255',
                'follow_up_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error: ' . $validator->errors()->first(), 422);
            }

            DB::beginTransaction();

            $data = $request->all();
            $data['aesa_session_id'] = $sessionId;

            // Handle photo uploads
            if ($request->hasFile('photo')) {
                $photoPath = \App\Models\Utils::uploadMedia(
                    $request->file('photo'),
                    ['jpg', 'jpeg', 'png', 'webp'],
                    5
                );
                $existingPhotos = [];
                $existingPhotos[] = $photoPath;
                $data['photos'] = $existingPhotos;
            }

            $observation = AesaObservation::create($data);

            DB::commit();

            $observation->load(['session', 'owner', 'createdBy']);

            return $this->success($observation, 'Observation created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an observation
     * PUT /api/aesa-observations/{id}
     */
    public function updateObservation(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $observation = AesaObservation::with('session')->find($id);
            if (!$observation) {
                return $this->error('Observation not found', 404);
            }

            // IP-based access check
            if ($user && $user->ip_id && $observation->ip_id !== $user->ip_id) {
                return $this->error('Unauthorized access', 403);
            }

            // Only observations in draft sessions can be edited
            if ($observation->session && $observation->session->status !== 'draft') {
                return $this->error('Cannot edit observations in a ' . $observation->session->status . ' session.', 422);
            }

            $observation->update($request->all());
            $observation->load(['session', 'owner', 'createdBy']);

            return $this->success($observation, 'Observation updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an observation
     * DELETE /api/aesa-observations/{id}
     */
    public function destroyObservation($id)
    {
        try {
            $user = Auth::user();
            $observation = AesaObservation::with('session')->find($id);
            if (!$observation) {
                return $this->error('Observation not found', 404);
            }

            // IP-based access check
            if ($user && $user->ip_id && $observation->ip_id !== $user->ip_id) {
                return $this->error('Unauthorized access', 403);
            }

            // Only observations in draft sessions can be deleted
            if ($observation->session && $observation->session->status !== 'draft') {
                return $this->error('Cannot delete observations from a ' . $observation->session->status . ' session.', 422);
            }

            $observation->delete();

            return $this->success(null, 'Observation deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete observation: ' . $e->getMessage(), 500);
        }
    }

    // ========================================================================
    // STATS & DROPDOWN OPTIONS
    // ========================================================================

    /**
     * Get AESA dashboard statistics
     * GET /api/aesa-sessions/stats
     */
    public function stats(Request $request)
    {
        try {
            $user = Auth::user();

            $query = AesaSession::query();
            if ($user && $user->ip_id) {
                $query->where('ip_id', $user->ip_id);
            }

            $totalSessions = (clone $query)->count();
            $draftSessions = (clone $query)->where('status', 'draft')->count();
            $submittedSessions = (clone $query)->where('status', 'submitted')->count();
            $reviewedSessions = (clone $query)->where('status', 'reviewed')->count();

            $obsQuery = AesaObservation::query();
            if ($user && $user->ip_id) {
                $obsQuery->where('ip_id', $user->ip_id);
            }

            $totalObservations = (clone $obsQuery)->count();
            $sickAnimals = (clone $obsQuery)->whereIn('animal_health_status', ['Suspected Sick', 'Sick'])->count();
            $healthyAnimals = (clone $obsQuery)->where('animal_health_status', 'Healthy')->count();
            $highRisk = (clone $obsQuery)->where('risk_level', 'High')->count();

            // Animal type distribution
            $animalTypeDistribution = (clone $obsQuery)
                ->select('animal_type', DB::raw('count(*) as count'))
                ->whereNotNull('animal_type')
                ->groupBy('animal_type')
                ->get();

            // Health status distribution
            $healthStatusDistribution = (clone $obsQuery)
                ->select('animal_health_status', DB::raw('count(*) as count'))
                ->whereNotNull('animal_health_status')
                ->groupBy('animal_health_status')
                ->get();

            // Recent sessions
            $recentSessions = AesaSession::with(['group', 'facilitator'])
                ->withCount('observations')
                ->when($user && $user->ip_id, function ($q) use ($user) {
                    $q->where('ip_id', $user->ip_id);
                })
                ->orderBy('observation_date', 'desc')
                ->limit(5)
                ->get();

            return $this->success([
                'total_sessions' => $totalSessions,
                'draft_sessions' => $draftSessions,
                'submitted_sessions' => $submittedSessions,
                'reviewed_sessions' => $reviewedSessions,
                'total_observations' => $totalObservations,
                'sick_animals' => $sickAnimals,
                'healthy_animals' => $healthyAnimals,
                'high_risk_observations' => $highRisk,
                'animal_type_distribution' => $animalTypeDistribution,
                'health_status_distribution' => $healthStatusDistribution,
                'recent_sessions' => $recentSessions,
            ], 'AESA statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all dropdown options for the AESA forms
     * GET /api/aesa-sessions/dropdown-options
     */
    public function dropdownOptions(Request $request)
    {
        try {
            $user = Auth::user();

            $options = AesaObservation::getDropdownOptions();

            // Add dynamic data
            $groupQuery = FfsGroup::select('id', 'name', 'district_text', 'subcounty_text', 'village')->orderBy('name');
            if ($user && $user->ip_id) {
                $groupQuery->where('ip_id', $user->ip_id);
            }
            $options['groups'] = $groupQuery->get()->map(function ($g) {
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'district' => $g->district_text,
                    'sub_county' => $g->subcounty_text,
                    'village' => $g->village,
                ];
            });

            // Add facilitators (Admin users act as facilitators)
            $facilitatorQuery = User::select('id', 'first_name', 'last_name')
                ->where('user_type', 'Admin')
                ->orderBy('first_name');
            if ($user && $user->ip_id) {
                $facilitatorQuery->where('ip_id', $user->ip_id);
            }
            $options['facilitators'] = $facilitatorQuery->get()->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => trim($f->first_name . ' ' . $f->last_name),
                ];
            });

            // Add registered farmers for owner selection
            $farmerQuery = User::select('id', 'first_name', 'last_name')
                ->orderBy('first_name');
            if ($user && $user->ip_id) {
                $farmerQuery->where('ip_id', $user->ip_id);
            }
            $options['farmers'] = $farmerQuery->limit(500)->get()->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => trim($f->first_name . ' ' . $f->last_name),
                ];
            });

            // Add districts (all districts — primarily Northern Uganda / Karamoja)
            $options['districts'] = Location::where('type', 'District')
                ->orderBy('name', 'ASC')
                ->get()
                ->map(function ($d) {
                    return [
                        'id'   => $d->id,
                        'name' => $d->name,
                    ];
                })
                ->values();

            return $this->success($options, 'Dropdown options retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dropdown options: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export AESA data as CSV
     * GET /api/aesa-sessions/export
     */
    public function export(Request $request)
    {
        try {
            $user = Auth::user();

            $query = AesaSession::with(['observations', 'group', 'facilitator'])
                ->when($user && $user->ip_id, function ($q) use ($user) {
                    $q->where('ip_id', $user->ip_id);
                });

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('date_from')) {
                $query->where('observation_date', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->where('observation_date', '<=', $request->date_to);
            }
            if ($request->has('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            $sessions = $query->orderBy('observation_date', 'desc')->get();

            // Build CSV data
            $headers = [
                'Data Sheet #', 'Group', 'District', 'Sub-County', 'Village',
                'Date', 'Time', 'Facilitator', 'Mini-Group', 'Location', 'Status',
                'Animal ID', 'Animal Type', 'Breed', 'Sex', 'Age', 'Weight (kg)', 'Height (cm)',
                'Owner', 'Health Status', 'Body Condition', 'Risk Level',
                'Main Problem', 'Immediate Action', 'Follow-up Date',
                'Health Score',
            ];

            $rows = [];
            foreach ($sessions as $session) {
                foreach ($session->observations as $obs) {
                    $rows[] = [
                        $session->data_sheet_number,
                        $session->group_name_display,
                        $session->district_name_display,
                        $session->sub_county_name_display,
                        $session->village_name_display,
                        $session->formatted_date,
                        $session->formatted_time,
                        $session->facilitator_name_display,
                        $session->mini_group_name,
                        $session->location_display,
                        $session->status_text,
                        $obs->animal_id_tag,
                        $obs->animal_type_display,
                        $obs->breed_display,
                        $obs->sex,
                        $obs->age_category,
                        $obs->weight_kg,
                        $obs->height_cm,
                        $obs->owner_display,
                        $obs->health_status_display,
                        $obs->body_condition,
                        $obs->risk_level,
                        $obs->main_problem ?? $obs->main_problem_other,
                        $obs->immediate_action ?? $obs->immediate_action_other,
                        $obs->follow_up_date ? $obs->follow_up_date->format('d/m/Y') : '',
                        $obs->health_score,
                    ];
                }
            }

            return $this->success([
                'headers' => $headers,
                'rows' => $rows,
                'total_sessions' => $sessions->count(),
                'total_observations' => collect($rows)->count(),
            ], 'Export data generated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to export data: ' . $e->getMessage(), 500);
        }
    }
}
