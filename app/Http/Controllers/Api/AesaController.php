<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AesaSession;
use App\Models\AesaObservation;
use App\Models\AesaCropObservation;
use App\Models\FfsGroup;
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
    // ACCESS TIER HELPERS
    // ========================================================================

    /**
     * Determine the access tier of the authenticated user.
     *
     * Returns an array with keys:
     *   tier  => 'facilitator' | 'ip_manager' | 'super_admin'
     *   ip_id => int|null
     *   user  => User|null
     */
    private function resolveUserTier(): array
    {
        $user = Auth::user();
        if (!$user) {
            return ['tier' => 'super_admin', 'ip_id' => null, 'user' => null];
        }

        // A facilitator is a Customer user who is assigned to a group as facilitator
        // OR has a facilitator_start_date set.
        $isFacilitator = ($user->facilitator_start_date !== null)
            || DB::table('ffs_groups')
                ->where('facilitator_id', $user->id)
                ->exists();

        if ($isFacilitator) {
            return ['tier' => 'facilitator', 'ip_id' => $user->ip_id, 'user' => $user];
        }

        if ($user->ip_id) {
            return ['tier' => 'ip_manager', 'ip_id' => $user->ip_id, 'user' => $user];
        }

        return ['tier' => 'super_admin', 'ip_id' => null, 'user' => $user];
    }

    /**
     * Apply the correct ownership WHERE clause to an AesaSession query.
     */
    private function applySessionScope($query, array $tier): void
    {
        switch ($tier['tier']) {
            case 'facilitator':
                $uid = $tier['user']->id;
                $query->where(function ($q) use ($uid) {
                    $q->where('facilitator_id', $uid)
                      ->orWhere('created_by_id', $uid);
                });
                break;

            case 'ip_manager':
                $query->where('ip_id', $tier['ip_id']);
                break;

            // super_admin: no filter — sees everything
        }
    }

    /**
     * Check whether the authenticated user can access the given session.
     */
    private function canAccessSession(AesaSession $session, array $tier): bool
    {
        $user = $tier['user'];
        switch ($tier['tier']) {
            case 'facilitator':
                return $session->facilitator_id == $user->id
                    || $session->created_by_id == $user->id;

            case 'ip_manager':
                return $session->ip_id == $tier['ip_id'];

            default: // super_admin
                return true;
        }
    }

    // ========================================================================
    // SESSION ENDPOINTS
    // ========================================================================

    /**
     * List AESA sessions with filters.
     * GET /api/aesa-sessions
     *
     * Access tiers:
     *   Facilitator  → only sessions they created or are linked to as facilitator
     *   IP Manager   → all sessions for their IP
     *   Super Admin  → all sessions
     */
    public function index(Request $request)
    {
        try {
            $tier  = $this->resolveUserTier();
            $query = AesaSession::with(['group', 'facilitator', 'createdBy'])
                ->withCount('observations');

            // Apply role-based access scope (server-enforced)
            $this->applySessionScope($query, $tier);

            // Optional additional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }
            if ($request->filled('facilitator_id') && $tier['tier'] !== 'facilitator') {
                // facilitator tier already scoped — don't override
                $query->where('facilitator_id', $request->facilitator_id);
            }
            if ($request->filled('date_from')) {
                $query->where('observation_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('observation_date', '<=', $request->date_to);
            }
            // my_sessions still honoured as an explicit sub-filter (useful for IP managers)
            if ($request->get('my_sessions') == '1' && $tier['user']) {
                $uid = $tier['user']->id;
                $query->where(function ($q) use ($uid) {
                    $q->where('facilitator_id', $uid)
                      ->orWhere('created_by_id', $uid);
                });
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('data_sheet_number', 'like', "%{$search}%")
                        ->orWhere('facilitator_name', 'like', "%{$search}%")
                        ->orWhere('mini_group_name', 'like', "%{$search}%")
                        ->orWhere('observation_location', 'like', "%{$search}%")
                        ->orWhere('district_text', 'like', "%{$search}%")
                        ->orWhere('sub_county_text', 'like', "%{$search}%")
                        ->orWhere('village_text', 'like', "%{$search}%")
                        ->orWhereHas('group', fn($gq) => $gq->where('name', 'like', "%{$search}%"));
                });
            }

            $sortBy  = $request->get('sort_by', 'observation_date');
            $sortDir = $request->get('sort_dir', 'desc');
            $query->orderBy($sortBy, $sortDir);

            $sessions = $request->filled('per_page')
                ? $query->paginate((int) $request->per_page)
                : $query->get();

            return $this->success($sessions, 'AESA sessions retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve AESA sessions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a single AESA session with all observations.
     * GET /api/aesa-sessions/{id}
     */
    public function show($id)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::with([
                'observations',
                'observations.owner',
                'cropObservations',
                'cropObservations.farmer',
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

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            return $this->success($session, 'AESA session retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve AESA session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new AESA session.
     * POST /api/aesa-sessions
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id'                   => 'nullable|exists:ffs_groups,id',
            'group_name_other'           => 'nullable|string|max:255',
            'district_id'                => 'nullable|integer',
            'district_text'              => 'nullable|string|max:255',
            'sub_county_id'              => 'nullable|integer',
            'sub_county_text'            => 'nullable|string|max:255',
            'village_id'                 => 'nullable|integer',
            'village_text'               => 'nullable|string|max:255',
            'observation_date'           => 'required|date',
            'observation_time'           => 'nullable|string',
            'facilitator_id'             => 'nullable|exists:users,id',
            'facilitator_name'           => 'nullable|string|max:255',
            'mini_group_name'            => 'nullable|string|max:255',
            'observation_location'       => 'nullable|string|max:255',
            'observation_location_other' => 'nullable|string|max:255',
            'gps_latitude'               => 'nullable|numeric',
            'gps_longitude'              => 'nullable|numeric',
            'status'                     => 'nullable|in:draft,submitted,reviewed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error: ' . $validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->only([
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
            ]);

            // Auto-assign facilitator_id to the current user when not provided
            $user = Auth::user();
            if (empty($data['facilitator_id']) && $user) {
                $data['facilitator_id'] = $user->id;
            }

            $session = AesaSession::create($data);

            // Auto-populate location fields from the selected group
            if ($session->group_id) {
                $group = FfsGroup::find($session->group_id);
                if ($group) {
                    $session->district_text    = $group->district_text;
                    $session->sub_county_text  = $group->subcounty_text;
                    $session->village_text     = $group->village;
                    $session->district_id      = $group->district_id;
                    $session->save();
                }
            }

            // Bulk observations in same request
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
     * Update an AESA session.
     * PUT /api/aesa-sessions/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::find($id);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($session->status !== 'draft') {
                return $this->error(
                    'Only draft sessions can be edited. This session is ' . $session->status . '.',
                    422
                );
            }

            $validator = Validator::make($request->all(), [
                'group_id'             => 'nullable|exists:ffs_groups,id',
                'observation_date'     => 'nullable|date',
                'observation_time'     => 'nullable|string',
                'facilitator_id'       => 'nullable|exists:users,id',
                'mini_group_name'      => 'nullable|string|max:255',
                'observation_location' => 'nullable|string|max:255',
                'status'               => 'nullable|in:draft,submitted,reviewed',
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

            // Re-sync location from group
            if ($session->group_id) {
                $group = FfsGroup::find($session->group_id);
                if ($group) {
                    $session->district_text   = $group->district_text;
                    $session->sub_county_text = $group->subcounty_text;
                    $session->village_text    = $group->village;
                    $session->district_id     = $group->district_id;
                    $session->save();
                }
            }

            $session->load(['observations', 'group', 'facilitator', 'createdBy']);

            return $this->success($session, 'AESA session updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update AESA session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an AESA session.
     * DELETE /api/aesa-sessions/{id}
     *
     * Rules:
     *   - Only DRAFT sessions can be deleted (no exceptions).
     *   - The user must own the session (facilitator or creator) or be an IP manager / super admin.
     */
    public function destroy($id)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::find($id);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($session->status !== 'draft') {
                return $this->error(
                    'Only draft sessions can be deleted. Submitted or reviewed sessions are locked.',
                    403
                );
            }

            $session->delete(); // Soft delete; observations cascade via FK

            return $this->success(null, 'AESA session deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete AESA session: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit a session (draft → submitted).
     * POST /api/aesa-sessions/{id}/submit
     */
    public function submit($id)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::find($id);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($session->status !== 'draft') {
                return $this->error('Only draft sessions can be submitted', 400);
            }

            if ($session->observations()->count() === 0 && $session->cropObservations()->count() === 0) {
                return $this->error('Cannot submit a session without any observations (animal or crop)', 400);
            }

            $session->update(['status' => 'submitted']);
            $session->load(['observations', 'cropObservations', 'group', 'facilitator', 'createdBy']);

            return $this->success($session, 'AESA session submitted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to submit AESA session: ' . $e->getMessage(), 500);
        }
    }

    // ========================================================================
    // OBSERVATION ENDPOINTS
    // ========================================================================

    /**
     * List observations for a session.
     * GET /api/aesa-sessions/{sessionId}/observations
     */
    public function observations($sessionId)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::find($sessionId);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
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
     * Show a single observation.
     * GET /api/aesa-observations/{id}
     */
    public function showObservation($id)
    {
        try {
            $tier        = $this->resolveUserTier();
            $observation = AesaObservation::with(['session', 'session.group', 'owner', 'createdBy'])
                ->find($id);

            if (!$observation) {
                return $this->error('Observation not found', 404);
            }

            if ($observation->session && !$this->canAccessSession($observation->session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            return $this->success($observation, 'Observation retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new observation for a session.
     * POST /api/aesa-sessions/{sessionId}/observations
     */
    public function storeObservation(Request $request, $sessionId)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::find($sessionId);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($session->status !== 'draft') {
                return $this->error('Cannot add observations to a ' . $session->status . ' session.', 422);
            }

            $validator = Validator::make($request->all(), [
                'animal_id_tag'      => 'nullable|string|max:255',
                'animal_type'        => 'nullable|string|max:255',
                'breed'              => 'nullable|string|max:255',
                'sex'                => 'nullable|string|max:50',
                'age_category'       => 'nullable|string|max:50',
                'weight_kg'          => 'nullable|numeric|min:0',
                'height_cm'          => 'nullable|numeric|min:0',
                'owner_name'         => 'nullable|string|max:255',
                'animal_health_status' => 'nullable|string|max:255',
                'follow_up_date'     => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error: ' . $validator->errors()->first(), 422);
            }

            DB::beginTransaction();

            $data = $request->all();
            $data['aesa_session_id'] = $sessionId;

            if ($request->hasFile('photo')) {
                $photoPath = \App\Models\Utils::uploadMedia(
                    $request->file('photo'),
                    ['jpg', 'jpeg', 'png', 'webp'],
                    5
                );
                $data['photos'] = [$photoPath];
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
     * Update an observation.
     * PUT /api/aesa-observations/{id}
     */
    public function updateObservation(Request $request, $id)
    {
        try {
            $tier        = $this->resolveUserTier();
            $observation = AesaObservation::with('session')->find($id);

            if (!$observation) {
                return $this->error('Observation not found', 404);
            }

            if ($observation->session && !$this->canAccessSession($observation->session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($observation->session && $observation->session->status !== 'draft') {
                return $this->error(
                    'Cannot edit observations in a ' . $observation->session->status . ' session.',
                    422
                );
            }

            $observation->update($request->except(['_method', 'aesa_session_id']));
            $observation->load(['session', 'owner', 'createdBy']);

            return $this->success($observation, 'Observation updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an observation.
     * DELETE /api/aesa-observations/{id}
     */
    public function destroyObservation($id)
    {
        try {
            $tier        = $this->resolveUserTier();
            $observation = AesaObservation::with('session')->find($id);

            if (!$observation) {
                return $this->error('Observation not found', 404);
            }

            if ($observation->session && !$this->canAccessSession($observation->session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($observation->session && $observation->session->status !== 'draft') {
                return $this->error(
                    'Cannot delete observations from a ' . $observation->session->status . ' session.',
                    422
                );
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
     * Get AESA dashboard statistics.
     * GET /api/aesa-sessions/stats
     *
     * Stats are scoped to the same access tier as the list view.
     */
    public function stats(Request $request)
    {
        try {
            $tier = $this->resolveUserTier();

            $sessionQuery = AesaSession::query();
            $this->applySessionScope($sessionQuery, $tier);

            $totalSessions     = (clone $sessionQuery)->count();
            $draftSessions     = (clone $sessionQuery)->where('status', 'draft')->count();
            $submittedSessions = (clone $sessionQuery)->where('status', 'submitted')->count();
            $reviewedSessions  = (clone $sessionQuery)->where('status', 'reviewed')->count();

            // Observations are scoped through their parent sessions
            $sessionIds = (clone $sessionQuery)->pluck('id');

            $obsQuery = AesaObservation::whereIn('aesa_session_id', $sessionIds);

            $totalObservations = (clone $obsQuery)->count();
            $sickAnimals       = (clone $obsQuery)->whereIn('animal_health_status', ['Suspected Sick', 'Sick'])->count();
            $healthyAnimals    = (clone $obsQuery)->where('animal_health_status', 'Healthy')->count();
            $highRisk          = (clone $obsQuery)->where('risk_level', 'High')->count();

            $animalTypeDistribution = (clone $obsQuery)
                ->select('animal_type', DB::raw('count(*) as count'))
                ->whereNotNull('animal_type')
                ->groupBy('animal_type')
                ->get();

            $healthStatusDistribution = (clone $obsQuery)
                ->select('animal_health_status', DB::raw('count(*) as count'))
                ->whereNotNull('animal_health_status')
                ->groupBy('animal_health_status')
                ->get();

            $recentSessionsQuery = AesaSession::with(['group', 'facilitator'])
                ->withCount('observations');
            $this->applySessionScope($recentSessionsQuery, $tier);
            $recentSessions = $recentSessionsQuery
                ->orderBy('observation_date', 'desc')
                ->limit(5)
                ->get();

            return $this->success([
                'total_sessions'            => $totalSessions,
                'draft_sessions'            => $draftSessions,
                'submitted_sessions'        => $submittedSessions,
                'reviewed_sessions'         => $reviewedSessions,
                'total_observations'        => $totalObservations,
                'sick_animals'              => $sickAnimals,
                'healthy_animals'           => $healthyAnimals,
                'high_risk_observations'    => $highRisk,
                'animal_type_distribution'  => $animalTypeDistribution,
                'health_status_distribution' => $healthStatusDistribution,
                'recent_sessions'           => $recentSessions,
            ], 'AESA statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all dropdown options for the AESA forms.
     * GET /api/aesa-sessions/dropdown-options
     */
    public function dropdownOptions(Request $request)
    {
        try {
            $tier = $this->resolveUserTier();
            $user = $tier['user'];
            $ipId = $tier['ip_id'];

            $options = AesaObservation::getDropdownOptions();

            // Groups scoped to IP
            $groupQuery = FfsGroup::select('id', 'name', 'district_text', 'subcounty_text', 'village')
                ->orderBy('name');
            if ($ipId) {
                $groupQuery->where('ip_id', $ipId);
            }
            $options['groups'] = $groupQuery->get()->map(fn($g) => [
                'id'         => $g->id,
                'name'       => $g->name,
                'district'   => $g->district_text,
                'sub_county' => $g->subcounty_text,
                'village'    => $g->village,
            ]);

            // Facilitators: Customer-type users who are active facilitators
            // (have facilitator_start_date set OR are assigned as ffs_groups.facilitator_id)
            $facilitatorIds = DB::table('ffs_groups')
                ->whereNotNull('facilitator_id')
                ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                ->distinct()
                ->pluck('facilitator_id');

            $facilitatorQuery = User::select('id', 'first_name', 'last_name', 'name')
                ->where('user_type', 'Customer')
                ->where(function ($q) use ($facilitatorIds) {
                    $q->whereNotNull('facilitator_start_date')
                      ->orWhereIn('id', $facilitatorIds);
                })
                ->orderBy('first_name');
            if ($ipId) {
                $facilitatorQuery->where('ip_id', $ipId);
            }
            $options['facilitators'] = $facilitatorQuery->get()->map(fn($f) => [
                'id'   => $f->id,
                'name' => trim(($f->first_name . ' ' . $f->last_name) ?: $f->name),
            ]);

            // Current user's ID so the mobile app can pre-select them
            $options['current_user_id'] = $user ? $user->id : null;

            // Crop-specific dropdown options
            $options = array_merge($options, AesaCropObservation::getCropDropdownOptions());

            // Registered farmers for owner selection (scoped to IP)
            $farmerQuery = User::select('id', 'first_name', 'last_name')
                ->where('user_type', 'Customer')
                ->orderBy('first_name');
            if ($ipId) {
                $farmerQuery->where('ip_id', $ipId);
            }
            $options['farmers'] = $farmerQuery->limit(500)->get()->map(fn($f) => [
                'id'   => $f->id,
                'name' => trim($f->first_name . ' ' . $f->last_name),
            ]);

            return $this->success($options, 'Dropdown options retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve dropdown options: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export AESA data as CSV-ready arrays.
     * GET /api/aesa-sessions/export
     */
    public function export(Request $request)
    {
        try {
            $tier  = $this->resolveUserTier();
            $query = AesaSession::with(['observations', 'group', 'facilitator']);

            $this->applySessionScope($query, $tier);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('date_from')) {
                $query->where('observation_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('observation_date', '<=', $request->date_to);
            }
            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            $sessions = $query->orderBy('observation_date', 'desc')->get();

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
                'headers'            => $headers,
                'rows'               => $rows,
                'total_sessions'     => $sessions->count(),
                'total_observations' => count($rows),
            ], 'Export data generated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to export data: ' . $e->getMessage(), 500);
        }
    }

    // ========================================================================
    // CROP OBSERVATION ENDPOINTS
    // ========================================================================

    /**
     * List crop observations for a session.
     * GET /api/aesa-sessions/{sessionId}/crop-observations
     */
    public function cropObservations($sessionId)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::find($sessionId);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            $observations = $session->cropObservations()
                ->with(['farmer', 'createdBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success($observations, 'Crop observations retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve crop observations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new crop observation for a session.
     * POST /api/aesa-sessions/{sessionId}/crop-observations
     */
    public function storeCropObservation(Request $request, $sessionId)
    {
        try {
            $tier    = $this->resolveUserTier();
            $session = AesaSession::find($sessionId);

            if (!$session) {
                return $this->error('AESA session not found', 404);
            }

            if (!$this->canAccessSession($session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($session->status !== 'draft') {
                return $this->error('Cannot add observations to a ' . $session->status . ' session.', 422);
            }

            $validator = Validator::make($request->all(), [
                'crop_type'       => 'nullable|string|max:255',
                'variety'         => 'nullable|string|max:255',
                'planting_date'   => 'nullable|date',
                'follow_up_date'  => 'nullable|date',
                'farmer_id'       => 'nullable|exists:users,id',
                'risk_level'      => 'nullable|in:Low,Medium,High',
                'plot_size_acres' => 'nullable|numeric|min:0',
                'plant_height_cm' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error: ' . $validator->errors()->first(), 422);
            }

            DB::beginTransaction();
            $data                   = $request->all();
            $data['aesa_session_id'] = $sessionId;

            $observation = AesaCropObservation::create($data);

            DB::commit();

            $observation->load(['session', 'farmer', 'createdBy']);

            return $this->success($observation, 'Crop observation created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create crop observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a single crop observation.
     * GET /api/aesa-crop-observations/{id}
     */
    public function showCropObservation($id)
    {
        try {
            $tier        = $this->resolveUserTier();
            $observation = AesaCropObservation::with(['session', 'session.group', 'farmer', 'createdBy'])
                ->find($id);

            if (!$observation) {
                return $this->error('Crop observation not found', 404);
            }

            if ($observation->session && !$this->canAccessSession($observation->session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            return $this->success($observation, 'Crop observation retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve crop observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a crop observation.
     * PUT /api/aesa-crop-observations/{id}
     */
    public function updateCropObservation(Request $request, $id)
    {
        try {
            $tier        = $this->resolveUserTier();
            $observation = AesaCropObservation::with('session')->find($id);

            if (!$observation) {
                return $this->error('Crop observation not found', 404);
            }

            if ($observation->session && !$this->canAccessSession($observation->session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($observation->session && $observation->session->status !== 'draft') {
                return $this->error(
                    'Cannot edit observations in a ' . $observation->session->status . ' session.',
                    422
                );
            }

            $observation->update($request->except(['_method', 'aesa_session_id']));
            $observation->load(['session', 'farmer', 'createdBy']);

            return $this->success($observation, 'Crop observation updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update crop observation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a crop observation.
     * DELETE /api/aesa-crop-observations/{id}
     */
    public function destroyCropObservation($id)
    {
        try {
            $tier        = $this->resolveUserTier();
            $observation = AesaCropObservation::with('session')->find($id);

            if (!$observation) {
                return $this->error('Crop observation not found', 404);
            }

            if ($observation->session && !$this->canAccessSession($observation->session, $tier)) {
                return $this->error('Unauthorized access', 403);
            }

            if ($observation->session && $observation->session->status !== 'draft') {
                return $this->error(
                    'Cannot delete observations from a ' . $observation->session->status . ' session.',
                    422
                );
            }

            $observation->delete();

            return $this->success(null, 'Crop observation deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete crop observation: ' . $e->getMessage(), 500);
        }
    }
}
