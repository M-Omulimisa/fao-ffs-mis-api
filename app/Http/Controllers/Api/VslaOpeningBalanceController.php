<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\VslaOpeningBalance;
use App\Models\VslaOpeningBalanceMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * VSLA Opening Balance Controller
 *
 * Allows a group chairperson to record the initial/opening financial
 * position of each member at the start of a cycle.
 *
 * Endpoints:
 *   GET  vsla/opening-balance/members   – list group members for data entry
 *   GET  vsla/opening-balance/status    – check if submitted for this cycle
 *   POST vsla/opening-balance/submit    – submit opening balances
 *   GET  vsla/opening-balance/{id}      – retrieve a submitted record with member entries
 */
class VslaOpeningBalanceController extends Controller
{
    // ─── List members for opening balance data entry ───────────────────────────

    /**
     * Return group members for the given project (cycle) so the chairperson
     * can fill in per-member opening figures.
     *
     * GET vsla/opening-balance/members?project_id=X
     */
    public function getMembers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code'    => 0,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['code' => 0, 'message' => 'Unauthorized'], 401);
            }

            $projectId = $request->input('project_id');
            $project   = Project::find($projectId);

            if (!$project) {
                return response()->json(['code' => 0, 'message' => 'Project not found'], 404);
            }

            // Fetch ALL active members belonging to the same group as the
            // authenticated user.  Filtering by projectShares would return empty
            // for a brand-new cycle (no transactions yet), so we use group_id
            // which is set when a member is registered into the group.
            $members = User::where('group_id', $user->group_id)
                ->where('status', 1)
                ->whereNotNull('group_id')
                ->select('id', 'name', 'first_name', 'last_name', 'member_code', 'phone_number')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($m) {
                    return [
                        'id'           => $m->id,
                        'name'         => $m->name,
                        'first_name'   => $m->first_name,
                        'last_name'    => $m->last_name,
                        'member_code'  => $m->member_code ?? '',
                        'phone_number' => $m->phone_number ?? '',
                        // Default opening figures (empty — chairperson fills these in)
                        'total_shares'      => 0,
                        'share_count'       => 0,
                        'total_loan_amount' => 0,
                        'loan_balance'      => 0,
                        'total_social_fund' => 0,
                    ];
                });

            return response()->json([
                'code'    => 1,
                'message' => 'Members retrieved successfully',
                'data'    => [
                    'project_id'   => $project->id,
                    'project_name' => $project->name,
                    'members'      => $members,
                    'total'        => $members->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Opening balance getMembers error: ' . $e->getMessage());
            return response()->json(['code' => 0, 'message' => 'Failed to load members: ' . $e->getMessage()], 500);
        }
    }

    // ─── Check submission status ────────────────────────────────────────────────

    /**
     * Check whether an opening balance has already been submitted for a cycle.
     *
     * GET vsla/opening-balance/status?project_id=X
     */
    public function getStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code'    => 0,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['code' => 0, 'message' => 'Unauthorized'], 401);
            }

            $projectId = $request->input('project_id');

            $existing = VslaOpeningBalance::where('cycle_id', $projectId)
                ->where('status', 'submitted')
                ->with('submittedBy:id,name')
                ->first();

            return response()->json([
                'code'    => 1,
                'message' => $existing ? 'Opening balance already submitted' : 'No opening balance submitted yet',
                'data'    => [
                    'submitted'       => (bool) $existing,
                    'opening_balance' => $existing ? [
                        'id'              => $existing->id,
                        'status'          => $existing->status,
                        'submission_date' => $existing->submission_date?->toDateTimeString(),
                        'submitted_by'    => $existing->submittedBy?->name,
                        'notes'           => $existing->notes,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Opening balance getStatus error: ' . $e->getMessage());
            return response()->json(['code' => 0, 'message' => 'Failed to check status: ' . $e->getMessage()], 500);
        }
    }

    // ─── Submit opening balances ────────────────────────────────────────────────

    /**
     * Store opening balance figures for all members.
     *
     * POST vsla/opening-balance/submit
     * Body: {
     *   "group_id":  1,
     *   "cycle_id":  2,
     *   "notes":     "optional text",
     *   "members": [
     *     {
     *       "member_id": 10,
     *       "total_shares":      150000,
     *       "share_count":       3,
     *       "total_loan_amount": 200000,
     *       "loan_balance":      100000,
     *       "total_social_fund": 50000
     *     }, ...
     *   ]
     * }
     */
    public function store(Request $request)
    {
        // ── 1. Decode members (sent as JSON string by Flutter / FormData) ──────
        $membersRaw = $request->input('members');
        $members = is_string($membersRaw) ? json_decode($membersRaw, true) : $membersRaw;

        if (!is_array($members) || empty($members)) {
            return response()->json([
                'code'    => 0,
                'message' => 'members must be a non-empty JSON array.',
            ], 422);
        }

        // Inject decoded array back so Laravel validation can access it normally
        $request->merge(['members' => $members]);

        // ── 2. Validate ────────────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'group_id'                    => 'required|integer|exists:ffs_groups,id',
            'cycle_id'                    => 'required|integer|exists:projects,id',
            'notes'                       => 'nullable|string|max:1000',
            'members'                     => 'required|array|min:1',
            'members.*.member_id'         => 'required|integer|exists:users,id',
            'members.*.total_shares'      => 'required|numeric|min:0',
            'members.*.share_count'       => 'nullable|numeric|min:0',
            'members.*.total_loan_amount' => 'required|numeric|min:0',
            'members.*.loan_balance'      => 'required|numeric|min:0',
            'members.*.total_social_fund' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code'    => 0,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['code' => 0, 'message' => 'Unauthorized'], 401);
            }

            $cycleId  = $request->input('cycle_id');
            $groupId  = $request->input('group_id');

            // Prevent duplicate submissions
            $existingSubmitted = VslaOpeningBalance::where('cycle_id', $cycleId)
                ->where('status', 'submitted')
                ->first();

            if ($existingSubmitted) {
                return response()->json([
                    'code'    => 0,
                    'message' => 'Opening balance for this cycle has already been submitted.',
                    'data'    => ['opening_balance_id' => $existingSubmitted->id],
                ], 409);
            }

            DB::beginTransaction();

            // Create header record
            $openingBalance = VslaOpeningBalance::create([
                'group_id'        => $groupId,
                'cycle_id'        => $cycleId,
                'submitted_by_id' => $user->id,
                'status'          => 'submitted',
                'submission_date' => now(),
                'notes'           => $request->input('notes'),
            ]);

            // Create per-member entries
            $memberRows = [];
            foreach ($members as $m) {
                $memberRows[] = [
                    'opening_balance_id' => $openingBalance->id,
                    'member_id'          => $m['member_id'],
                    'total_shares'       => $m['total_shares'] ?? 0,
                    'share_count'        => $m['share_count'] ?? 0,
                    'total_loan_amount'  => $m['total_loan_amount'] ?? 0,
                    'loan_balance'       => $m['loan_balance'] ?? 0,
                    'total_social_fund'  => $m['total_social_fund'] ?? 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            VslaOpeningBalanceMember::insert($memberRows);

            DB::commit();

            return response()->json([
                'code'    => 1,
                'message' => 'Opening balances submitted successfully.',
                'data'    => [
                    'opening_balance_id' => $openingBalance->id,
                    'members_saved'      => count($memberRows),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opening balance store error: ' . $e->getMessage());
            return response()->json(['code' => 0, 'message' => 'Failed to submit: ' . $e->getMessage()], 500);
        }
    }

    // ─── Retrieve a submitted record ────────────────────────────────────────────

    /**
     * Get a specific opening balance record with all member entries.
     *
     * GET vsla/opening-balance/{id}
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['code' => 0, 'message' => 'Unauthorized'], 401);
            }

            $ob = VslaOpeningBalance::with([
                'memberEntries.member:id,name,first_name,last_name,member_code',
                'submittedBy:id,name',
            ])->find($id);

            if (!$ob) {
                return response()->json(['code' => 0, 'message' => 'Opening balance not found'], 404);
            }

            return response()->json([
                'code'    => 1,
                'message' => 'Opening balance retrieved',
                'data'    => $ob,
            ]);
        } catch (\Exception $e) {
            Log::error('Opening balance show error: ' . $e->getMessage());
            return response()->json(['code' => 0, 'message' => 'Failed to retrieve: ' . $e->getMessage()], 500);
        }
    }
}
