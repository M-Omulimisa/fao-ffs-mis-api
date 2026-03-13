<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\User;
use App\Models\VslaMeeting;
use App\Traits\ApiResponser;
use App\Traits\PhoneNumberNormalization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * VSLA Facilitator Module Controller
 *
 * Powers the standalone Facilitator module in the mobile app.
 * All endpoints are scoped to the logged-in facilitator's groups.
 *
 *   Groups:
 *     GET  agent-vsla/my-groups               – list groups created by facilitator
 *     GET  agent-vsla/my-groups/{id}           – single group detail
 *     PUT  agent-vsla/my-groups/{id}           – update group basic info
 *
 *   Members:
 *     GET  agent-vsla/my-groups/{id}/members   – list group members
 *     POST agent-vsla/my-groups/{id}/members   – add member to group
 *     PUT  agent-vsla/members/{id}             – update member info
 *     PUT  agent-vsla/members/{id}/role        – assign/change member role
 *
 *   Dashboard:
 *     GET  agent-vsla/dashboard                – facilitator dashboard stats
 */
class VslaFacilitatorController extends Controller
{
    use ApiResponser, PhoneNumberNormalization;

    // ──────────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Return summary stats for the facilitator's groups.
     */
    public function dashboard(Request $request)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $groups = FfsGroup::where('facilitator_id', $officer->id)
                ->where('type', 'VSLA')
                ->get();

            $groupIds = $groups->pluck('id');

            $totalMembers = User::whereIn('group_id', $groupIds)->count();
            $totalMale = User::whereIn('group_id', $groupIds)->where('sex', 'Male')->count();
            $totalFemale = User::whereIn('group_id', $groupIds)->where('sex', 'Female')->count();

            $activeCycles = Project::whereIn('group_id', $groupIds)
                ->where('is_vsla_cycle', 'Yes')
                ->where('is_active_cycle', 'Yes')
                ->count();

            return $this->success([
                'total_groups'   => $groups->count(),
                'total_members'  => $totalMembers,
                'male_members'   => $totalMale,
                'female_members' => $totalFemale,
                'active_cycles'  => $activeCycles,
                'groups_summary' => $groups->map(function ($g) {
                    return [
                        'id'             => $g->id,
                        'name'           => $g->name,
                        'code'           => $g->code,
                        'status'         => $g->status ?? 'active',
                        'total_members'  => User::where('group_id', $g->id)->count(),
                        'district'       => $g->district_text,
                    ];
                }),
            ], 'Dashboard loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator dashboard error: ' . $e->getMessage());
            return $this->error('Failed to load dashboard: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // GROUPS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * List all VSLA groups created/facilitated by the logged-in officer.
     */
    public function myGroups(Request $request)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $query = FfsGroup::where('facilitator_id', $officer->id)
                ->where('type', 'VSLA');

            // Search filter
            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('code', 'like', "%{$s}%")
                      ->orWhere('district_text', 'like', "%{$s}%");
                });
            }

            $groups = $query->orderBy('created_at', 'desc')->get();

            $data = $groups->map(function ($g) {
                $members = User::where('group_id', $g->id)->get();
                $activeCycle = Project::where('group_id', $g->id)
                    ->where('is_vsla_cycle', 'Yes')
                    ->where('is_active_cycle', 'Yes')
                    ->first();

                return [
                    'id'                  => $g->id,
                    'name'                => $g->name,
                    'code'                => $g->code,
                    'status'              => $g->status ?? 'active',
                    'district'            => $g->district_text,
                    'subcounty'           => $g->subcounty_text,
                    'parish'              => $g->parish_text,
                    'village'             => $g->village,
                    'meeting_frequency'   => $g->meeting_frequency,
                    'meeting_day'         => $g->meeting_day,
                    'meeting_venue'       => $g->meeting_venue,
                    'total_members'       => $members->count(),
                    'male_members'        => $members->where('sex', 'Male')->count(),
                    'female_members'      => $members->where('sex', 'Female')->count(),
                    'chairperson_name'    => optional($g->admin)->name,
                    'chairperson_phone'   => optional($g->admin)->phone_number,
                    'secretary_name'      => optional($g->secretary)->name,
                    'treasurer_name'      => optional($g->treasurer)->name,
                    'active_cycle'        => $activeCycle ? [
                        'id'         => $activeCycle->id,
                        'name'       => $activeCycle->cycle_name ?? $activeCycle->name,
                        'start_date' => $activeCycle->cycle_start_date,
                        'end_date'   => $activeCycle->cycle_end_date,
                        'share_value'=> $activeCycle->share_value,
                    ] : null,
                    'registration_date'   => $g->registration_date,
                    'establishment_date'  => $g->establishment_date,
                    'created_at'          => $g->created_at?->format('Y-m-d'),
                ];
            });

            return $this->success($data, 'Groups loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator myGroups error: ' . $e->getMessage());
            return $this->error('Failed to load groups: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed info for a single group (must belong to this facilitator).
     */
    public function groupDetail(Request $request, $id)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $group = FfsGroup::where('id', $id)
                ->where('facilitator_id', $officer->id)
                ->first();

            if (!$group) {
                return $this->error('Group not found or access denied', 404);
            }

            $members = User::where('group_id', $group->id)
                ->select('id', 'name', 'first_name', 'last_name', 'phone_number', 'sex',
                         'is_group_admin', 'is_group_secretary', 'is_group_treasurer',
                         'status', 'created_at')
                ->orderBy('name')
                ->get()
                ->map(function ($m) {
                    $role = 'Member';
                    if ($m->is_group_admin === 'Yes') $role = 'Chairperson';
                    elseif ($m->is_group_secretary === 'Yes') $role = 'Secretary';
                    elseif ($m->is_group_treasurer === 'Yes') $role = 'Treasurer';

                    return [
                        'id'           => $m->id,
                        'name'         => $m->name,
                        'first_name'   => $m->first_name,
                        'last_name'    => $m->last_name,
                        'phone_number' => $m->phone_number,
                        'sex'          => $m->sex,
                        'role'         => $role,
                        'status'       => $m->status ?? 'active',
                        'created_at'   => $m->created_at?->format('Y-m-d'),
                    ];
                });

            $cycles = Project::where('group_id', $group->id)
                ->where('is_vsla_cycle', 'Yes')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($c) {
                    return [
                        'id'          => $c->id,
                        'name'        => $c->cycle_name ?? $c->name,
                        'start_date'  => $c->cycle_start_date,
                        'end_date'    => $c->cycle_end_date,
                        'is_active'   => $c->is_active_cycle === 'Yes',
                        'share_value' => $c->share_value,
                        'saving_type' => $c->saving_type,
                    ];
                });

            return $this->success([
                'group' => [
                    'id'                  => $group->id,
                    'name'                => $group->name,
                    'code'                => $group->code,
                    'description'         => $group->description,
                    'status'              => $group->status ?? 'active',
                    'type'                => $group->type,
                    'district'            => $group->district_text,
                    'district_id'         => $group->district_id,
                    'subcounty'           => $group->subcounty_text,
                    'subcounty_id'        => $group->subcounty_id,
                    'parish'              => $group->parish_text,
                    'parish_id'           => $group->parish_id,
                    'village'             => $group->village,
                    'meeting_frequency'   => $group->meeting_frequency,
                    'meeting_day'         => $group->meeting_day,
                    'meeting_venue'       => $group->meeting_venue,
                    'estimated_members'   => $group->estimated_members,
                    'registration_date'   => $group->registration_date,
                    'establishment_date'  => $group->establishment_date,
                    'ip_id'               => $group->ip_id,
                    'ip_name'             => optional($group->implementingPartner)->name,
                    'facilitator_name'    => optional($group->facilitator)->name,
                    'chairperson'         => $group->admin_id ? [
                        'id'    => $group->admin->id ?? null,
                        'name'  => $group->admin->name ?? null,
                        'phone' => $group->admin->phone_number ?? null,
                    ] : null,
                    'secretary'           => $group->secretary_id ? [
                        'id'    => $group->secretary->id ?? null,
                        'name'  => $group->secretary->name ?? null,
                        'phone' => $group->secretary->phone_number ?? null,
                    ] : null,
                    'treasurer'           => $group->treasurer_id ? [
                        'id'    => $group->treasurer->id ?? null,
                        'name'  => $group->treasurer->name ?? null,
                        'phone' => $group->treasurer->phone_number ?? null,
                    ] : null,
                    'created_at'          => $group->created_at?->format('Y-m-d H:i'),
                ],
                'members' => $members,
                'cycles'  => $cycles,
            ], 'Group detail loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator groupDetail error: ' . $e->getMessage());
            return $this->error('Failed to load group: ' . $e->getMessage());
        }
    }

    /**
     * Update basic group info (name, description, meeting details, location).
     */
    public function updateGroup(Request $request, $id)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $group = FfsGroup::where('id', $id)
                ->where('facilitator_id', $officer->id)
                ->first();

            if (!$group) {
                return $this->error('Group not found or access denied', 404);
            }

            $validator = Validator::make($request->all(), [
                'name'               => 'nullable|string|max:255',
                'description'        => 'nullable|string|max:1000',
                'meeting_frequency'  => 'nullable|string|in:Weekly,Bi-weekly,Monthly',
                'meeting_day'        => 'nullable|string',
                'meeting_venue'      => 'nullable|string|max:500',
                'village'            => 'nullable|string|max:255',
                'estimated_members'  => 'nullable|integer|min:5|max:100',
                'establishment_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $fillable = [
                'name', 'description', 'meeting_frequency', 'meeting_day',
                'meeting_venue', 'village', 'estimated_members', 'establishment_date',
            ];

            foreach ($fillable as $field) {
                if ($request->has($field)) {
                    $group->$field = $request->input($field);
                }
            }

            $group->save();

            return $this->success([
                'id'   => $group->id,
                'name' => $group->name,
            ], 'Group updated successfully');
        } catch (\Exception $e) {
            Log::error('Facilitator updateGroup error: ' . $e->getMessage());
            return $this->error('Failed to update group: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // MEMBERS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * List members of a specific group (must belong to this facilitator).
     */
    public function groupMembers(Request $request, $groupId)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $group = FfsGroup::where('id', $groupId)
                ->where('facilitator_id', $officer->id)
                ->first();

            if (!$group) {
                return $this->error('Group not found or access denied', 404);
            }

            $query = User::where('group_id', $groupId);

            // Search
            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('first_name', 'like', "%{$s}%")
                      ->orWhere('last_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%");
                });
            }

            // Role filter
            if ($request->filled('role')) {
                $role = $request->input('role');
                if ($role === 'Chairperson') {
                    $query->where('is_group_admin', 'Yes');
                } elseif ($role === 'Secretary') {
                    $query->where('is_group_secretary', 'Yes');
                } elseif ($role === 'Treasurer') {
                    $query->where('is_group_treasurer', 'Yes');
                } elseif ($role === 'Member') {
                    $query->where(function ($q) {
                        $q->where('is_group_admin', '!=', 'Yes')
                          ->where('is_group_secretary', '!=', 'Yes')
                          ->where('is_group_treasurer', '!=', 'Yes');
                    });
                }
            }

            $members = $query->orderBy('name')->get()->map(function ($m) {
                $role = 'Member';
                if ($m->is_group_admin === 'Yes') $role = 'Chairperson';
                elseif ($m->is_group_secretary === 'Yes') $role = 'Secretary';
                elseif ($m->is_group_treasurer === 'Yes') $role = 'Treasurer';

                return [
                    'id'           => $m->id,
                    'name'         => $m->name,
                    'first_name'   => $m->first_name,
                    'last_name'    => $m->last_name,
                    'phone_number' => $m->phone_number,
                    'sex'          => $m->sex,
                    'role'         => $role,
                    'status'       => $m->status ?? 'active',
                    'national_id'  => $m->national_id_number,
                    'created_at'   => $m->created_at?->format('Y-m-d'),
                ];
            });

            return $this->success([
                'group_id'   => $group->id,
                'group_name' => $group->name,
                'members'    => $members,
                'total'      => $members->count(),
            ], 'Members loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator groupMembers error: ' . $e->getMessage());
            return $this->error('Failed to load members: ' . $e->getMessage());
        }
    }

    /**
     * Add a new member to a group.
     * Creates a User account and assigns them to the group.
     */
    public function addMember(Request $request, $groupId)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $group = FfsGroup::where('id', $groupId)
                ->where('facilitator_id', $officer->id)
                ->first();

            if (!$group) {
                return $this->error('Group not found or access denied', 404);
            }

            $validator = Validator::make($request->all(), [
                'first_name'   => 'required|string|max:100',
                'last_name'    => 'required|string|max:100',
                'phone_number' => 'nullable|string|max:20',
                'sex'          => 'nullable|string|in:Male,Female',
                'role'         => 'nullable|string|in:Member,Chairperson,Secretary,Treasurer',
                'password'     => 'nullable|string|min:4',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            // Normalize phone (only if provided)
            $rawPhone = $request->input('phone_number');
            $phone    = ($rawPhone && trim($rawPhone) !== '')
                ? $this->normalizePhone(trim($rawPhone))
                : null;

            // Phone-based duplicate checks — only when a phone is actually supplied
            $existingUser = null;
            if ($phone) {
                // Check if phone already exists in this group
                $existingInGroup = User::where('group_id', $groupId)
                    ->where('phone_number', $phone)
                    ->first();

                if ($existingInGroup) {
                    return $this->error('A member with this phone number already exists in this group', 422);
                }

                // Check if user with this phone already exists in the system
                $existingUser = User::where('phone_number', $phone)->first();
            }

            DB::beginTransaction();

            if ($existingUser) {
                // Assign existing user to this group
                if ($existingUser->group_id && $existingUser->group_id != $groupId) {
                    DB::rollBack();
                    return $this->error(
                        'This phone number belongs to a user already in another group (' .
                        optional($existingUser->group)->name . '). They must leave that group first.',
                        422
                    );
                }
                $user = $existingUser;
                $user->group_id = $groupId;
            } else {
                // Create new user
                $firstName = trim($request->input('first_name'));
                $lastName  = trim($request->input('last_name'));

                $user = new User();
                $user->first_name    = $firstName;
                $user->last_name     = $lastName;
                $user->name          = $firstName . ' ' . $lastName;
                // Temporary username — replaced with member_code below if no phone
                $user->username      = $phone ?? ('tmp_' . uniqid());
                $user->phone_number  = $phone;
                $user->password      = Hash::make($request->input('password', '4321'));
                $user->sex           = $request->input('sex');
                $user->group_id      = $groupId;
                $user->ip_id         = $group->ip_id;
                $user->district_id   = $group->district_id;
                $user->subcounty_id  = $group->subcounty_id;
                $user->parish_id     = $group->parish_id;
                $user->status        = 'active';
                $user->user_type     = 'Customer';
            }

            // Handle role assignment
            $role = $request->input('role', 'Member');
            $user->is_group_admin     = ($role === 'Chairperson') ? 'Yes' : 'No';
            $user->is_group_secretary = ($role === 'Secretary')   ? 'Yes' : 'No';
            $user->is_group_treasurer = ($role === 'Treasurer')   ? 'Yes' : 'No';

            $user->save();

            // If no phone was provided, fall back to member_code as identifier
            if (!$phone && $user->member_code) {
                $user->updateQuietly([
                    'phone_number' => $user->member_code,
                    'username'     => $user->member_code,
                ]);
            }

            // Assign farmer_member role if not already assigned
            if (!$user->roles()->where('slug', 'farmer_member')->exists()) {
                $farmerRole = \Encore\Admin\Auth\Database\Role::where('slug', 'farmer_member')->first();
                if ($farmerRole) {
                    $user->roles()->syncWithoutDetaching([$farmerRole->id]);
                }
            }

            // Update group officer references if applicable
            if ($role === 'Chairperson') {
                $group->admin_id = $user->id;
                $group->save();
            } elseif ($role === 'Secretary') {
                $group->secretary_id = $user->id;
                $group->save();
            } elseif ($role === 'Treasurer') {
                $group->treasurer_id = $user->id;
                $group->save();
            }

            DB::commit();

            return $this->success([
                'id'           => $user->id,
                'name'         => $user->name,
                'phone_number' => $user->phone_number,
                'role'         => $role,
                'group_id'     => $groupId,
            ], 'Member added successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Facilitator addMember error: ' . $e->getMessage());
            return $this->error('Failed to add member: ' . $e->getMessage());
        }
    }

    /**
     * Update a member's basic info.
     */
    public function updateMember(Request $request, $memberId)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $member = User::find($memberId);
            if (!$member || !$member->group_id) {
                return $this->error('Member not found', 404);
            }

            // Verify the group belongs to this facilitator
            $group = FfsGroup::where('id', $member->group_id)
                ->where('facilitator_id', $officer->id)
                ->first();

            if (!$group) {
                return $this->error('Access denied', 403);
            }

            $validator = Validator::make($request->all(), [
                'first_name'   => 'nullable|string|max:100',
                'last_name'    => 'nullable|string|max:100',
                'phone_number' => 'nullable|string|min:10|max:15',
                'sex'          => 'nullable|string|in:Male,Female',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            if ($request->filled('first_name')) {
                $member->first_name = trim($request->input('first_name'));
            }
            if ($request->filled('last_name')) {
                $member->last_name = trim($request->input('last_name'));
            }
            if ($request->filled('first_name') || $request->filled('last_name')) {
                $member->name = trim($member->first_name . ' ' . $member->last_name);
            }
            if ($request->filled('phone_number')) {
                $phone = $this->normalizePhone($request->input('phone_number'));
                // Check uniqueness within group
                $dup = User::where('group_id', $member->group_id)
                    ->where('phone_number', $phone)
                    ->where('id', '!=', $member->id)
                    ->exists();
                if ($dup) {
                    return $this->error('Another member in this group has this phone number', 422);
                }
                $member->phone_number = $phone;
                $member->username     = $phone;
            }
            if ($request->filled('sex')) {
                $member->sex = $request->input('sex');
            }

            $member->save();

            return $this->success([
                'id'   => $member->id,
                'name' => $member->name,
            ], 'Member updated successfully');
        } catch (\Exception $e) {
            Log::error('Facilitator updateMember error: ' . $e->getMessage());
            return $this->error('Failed to update member: ' . $e->getMessage());
        }
    }

    /**
     * Change a member's group role (Chairperson, Secretary, Treasurer, Member).
     */
    public function updateMemberRole(Request $request, $memberId)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $member = User::find($memberId);
            if (!$member || !$member->group_id) {
                return $this->error('Member not found', 404);
            }

            $group = FfsGroup::where('id', $member->group_id)
                ->where('facilitator_id', $officer->id)
                ->first();

            if (!$group) {
                return $this->error('Access denied', 403);
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|in:Member,Chairperson,Secretary,Treasurer',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $newRole = $request->input('role');

            DB::beginTransaction();

            // If assigning an officer role, clear it from the previous holder
            if ($newRole === 'Chairperson') {
                User::where('group_id', $group->id)
                    ->where('is_group_admin', 'Yes')
                    ->where('id', '!=', $member->id)
                    ->update(['is_group_admin' => 'No']);
                $group->admin_id = $member->id;
            } elseif ($newRole === 'Secretary') {
                User::where('group_id', $group->id)
                    ->where('is_group_secretary', 'Yes')
                    ->where('id', '!=', $member->id)
                    ->update(['is_group_secretary' => 'No']);
                $group->secretary_id = $member->id;
            } elseif ($newRole === 'Treasurer') {
                User::where('group_id', $group->id)
                    ->where('is_group_treasurer', 'Yes')
                    ->where('id', '!=', $member->id)
                    ->update(['is_group_treasurer' => 'No']);
                $group->treasurer_id = $member->id;
            }

            // Clear all role flags for this member first
            $member->is_group_admin     = 'No';
            $member->is_group_secretary = 'No';
            $member->is_group_treasurer = 'No';

            // Set the new role
            if ($newRole === 'Chairperson') $member->is_group_admin = 'Yes';
            if ($newRole === 'Secretary')   $member->is_group_secretary = 'Yes';
            if ($newRole === 'Treasurer')   $member->is_group_treasurer = 'Yes';

            // If demoting from officer, clear group FK too
            if ($newRole === 'Member') {
                if ($group->admin_id == $member->id) $group->admin_id = null;
                if ($group->secretary_id == $member->id) $group->secretary_id = null;
                if ($group->treasurer_id == $member->id) $group->treasurer_id = null;
            }

            $member->save();
            $group->save();

            DB::commit();

            return $this->success([
                'id'   => $member->id,
                'name' => $member->name,
                'role' => $newRole,
            ], 'Role updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Facilitator updateMemberRole error: ' . $e->getMessage());
            return $this->error('Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * List all members across all facilitator's groups
     * (for the "All Members" view).
     */
    public function allMembers(Request $request)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) {
                return $this->error('Unauthorized', 401);
            }

            $groupIds = FfsGroup::where('facilitator_id', $officer->id)
                ->where('type', 'VSLA')
                ->pluck('id');

            $query = User::whereIn('group_id', $groupIds);

            // Search
            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%");
                });
            }

            // Group filter
            if ($request->filled('group_id')) {
                $query->where('group_id', $request->input('group_id'));
            }

            $members = $query->orderBy('name')->get()->map(function ($m) {
                $role = 'Member';
                if ($m->is_group_admin === 'Yes') $role = 'Chairperson';
                elseif ($m->is_group_secretary === 'Yes') $role = 'Secretary';
                elseif ($m->is_group_treasurer === 'Yes') $role = 'Treasurer';

                return [
                    'id'           => $m->id,
                    'name'         => $m->name,
                    'first_name'   => $m->first_name,
                    'last_name'    => $m->last_name,
                    'phone_number' => $m->phone_number,
                    'sex'          => $m->sex,
                    'role'         => $role,
                    'group_id'     => $m->group_id,
                    'group_name'   => optional($m->group)->name,
                    'status'       => $m->status ?? 'active',
                    'created_at'   => $m->created_at?->format('Y-m-d'),
                ];
            });

            return $this->success([
                'members' => $members,
                'total'   => $members->count(),
            ], 'All members loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator allMembers error: ' . $e->getMessage());
            return $this->error('Failed to load members: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // MEETINGS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * List all groups with their meeting summary for the facilitator.
     * Includes ongoing meeting count, total submitted meetings, active cycle info.
     */
    public function meetingGroups(Request $request)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) return $this->error('Unauthorized', 401);

            $groups = FfsGroup::where('facilitator_id', $officer->id)
                ->where('type', 'VSLA')
                ->orderBy('name')
                ->get();

            $result = $groups->map(function ($g) {
                $activeCycle = Project::where('group_id', $g->id)
                    ->where('is_vsla_cycle', 'Yes')
                    ->where('is_active_cycle', 'Yes')
                    ->first();

                $totalMeetings = 0;
                $lastMeetingDate = null;

                if ($activeCycle) {
                    $totalMeetings = VslaMeeting::where('cycle_id', $activeCycle->id)
                        ->where('group_id', $g->id)
                        ->count();

                    $lastMeeting = VslaMeeting::where('cycle_id', $activeCycle->id)
                        ->where('group_id', $g->id)
                        ->orderBy('meeting_date', 'desc')
                        ->first();
                    $lastMeetingDate = $lastMeeting?->meeting_date;
                }

                $memberCount = User::where('group_id', $g->id)->count();

                return [
                    'id'                => $g->id,
                    'name'              => $g->name,
                    'code'              => $g->code,
                    'member_count'      => $memberCount,
                    'active_cycle'      => $activeCycle ? [
                        'id'         => $activeCycle->id,
                        'name'       => $activeCycle->name,
                        'start_date' => $activeCycle->start_date,
                        'end_date'   => $activeCycle->end_date,
                        'share_value' => $activeCycle->share_value,
                        'saving_type' => $activeCycle->saving_type ?? 'shares',
                    ] : null,
                    'total_meetings'    => $totalMeetings,
                    'last_meeting_date' => $lastMeetingDate,
                ];
            });

            return $this->success($result, 'Meeting groups loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator meetingGroups error: ' . $e->getMessage());
            return $this->error('Failed to load meeting groups: ' . $e->getMessage());
        }
    }

    /**
     * List meetings for a specific group (scoped to facilitator).
     * Supports filtering by cycle_id and status.
     */
    public function groupMeetings(Request $request, $groupId)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) return $this->error('Unauthorized', 401);

            $group = FfsGroup::where('id', $groupId)
                ->where('facilitator_id', $officer->id)
                ->first();
            if (!$group) return $this->error('Group not found', 404);

            $query = VslaMeeting::where('group_id', $groupId)
                ->orderBy('meeting_date', 'desc');

            if ($request->filled('cycle_id')) {
                $query->where('cycle_id', $request->cycle_id);
            }

            if ($request->filled('status')) {
                $query->where('processing_status', $request->status);
            }

            $meetings = $query->get()->map(function ($m) {
                return [
                    'id'                => $m->id,
                    'local_id'          => $m->local_id,
                    'meeting_number'    => $m->meeting_number,
                    'meeting_date'      => $m->meeting_date,
                    'members_present'   => $m->members_present ?? 0,
                    'members_absent'    => $m->members_absent ?? 0,
                    'total_savings'     => $m->total_savings_collected ?? 0,
                    'total_loans'       => $m->total_loans_disbursed ?? 0,
                    'processing_status' => $m->processing_status,
                    'created_at'        => $m->created_at?->format('Y-m-d H:i'),
                ];
            });

            return $this->success([
                'group' => [
                    'id'   => $group->id,
                    'name' => $group->name,
                    'code' => $group->code,
                ],
                'meetings' => $meetings,
                'total'    => $meetings->count(),
            ], 'Group meetings loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator groupMeetings error: ' . $e->getMessage());
            return $this->error('Failed to load meetings: ' . $e->getMessage());
        }
    }

    /**
     * List ALL submitted meetings across every VSLA group the facilitator owns.
     * Returns a flat list sorted by meeting_date desc, each row includes group info.
     */
    public function allMeetings(Request $request)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) return $this->error('Unauthorized', 401);

            $groupIds = FfsGroup::where('facilitator_id', $officer->id)
                ->where('type', 'VSLA')
                ->pluck('id');

            if ($groupIds->isEmpty()) {
                return $this->success([], 'No meetings found');
            }

            // Pre-load group info keyed by id
            $groups = FfsGroup::whereIn('id', $groupIds)
                ->get()
                ->keyBy('id');

            $query = VslaMeeting::whereIn('group_id', $groupIds)
                ->orderBy('meeting_date', 'desc')
                ->orderBy('id', 'desc');

            if ($request->filled('status')) {
                $query->where('processing_status', $request->status);
            }

            $meetings = $query->get()->map(function ($m) use ($groups) {
                $g = $groups->get($m->group_id);
                return [
                    'id'                => $m->id,
                    'local_id'          => $m->local_id,
                    'group_id'          => $m->group_id,
                    'group_name'        => $g?->name ?? 'Unknown',
                    'group_code'        => $g?->code ?? '',
                    'cycle_id'          => $m->cycle_id,
                    'meeting_number'    => $m->meeting_number,
                    'meeting_date'      => $m->meeting_date?->format('Y-m-d'),
                    'members_present'   => $m->members_present ?? 0,
                    'members_absent'    => $m->members_absent ?? 0,
                    'total_savings'     => (float) ($m->total_savings_collected ?? 0),
                    'total_welfare'     => (float) ($m->total_welfare_collected ?? 0),
                    'total_social_fund' => (float) ($m->total_social_fund_collected ?? 0),
                    'total_fines'       => (float) ($m->total_fines_collected ?? 0),
                    'total_loans'       => (float) ($m->total_loans_disbursed ?? 0),
                    'total_loans_repaid'=> (float) ($m->total_loans_repaid ?? 0),
                    'processing_status' => $m->processing_status,
                    'has_errors'        => $m->has_errors ?? false,
                    'has_warnings'      => $m->has_warnings ?? false,
                    'created_at'        => $m->created_at?->format('Y-m-d H:i'),
                ];
            });

            return $this->success($meetings, 'All meetings loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator allMeetings error: ' . $e->getMessage());
            return $this->error('Failed to load meetings: ' . $e->getMessage());
        }
    }

    /**
     * Get manifest-like data for a specific group (for facilitator meeting flow).
     * Returns group info, active cycle, members list — everything the mobile
     * meeting hub needs to operate for any of the facilitator's groups.
     */
    public function groupManifest(Request $request, $groupId)
    {
        try {
            $officer = auth('api')->user();
            if (!$officer) return $this->error('Unauthorized', 401);

            $group = FfsGroup::where('id', $groupId)
                ->where('facilitator_id', $officer->id)
                ->first();
            if (!$group) return $this->error('Group not found', 404);

            $activeCycle = Project::where('group_id', $group->id)
                ->where('is_vsla_cycle', 'Yes')
                ->where('is_active_cycle', 'Yes')
                ->first();

            $members = User::where('group_id', $group->id)
                ->select('id', 'name', 'first_name', 'last_name', 'phone_number', 'sex',
                    'is_group_admin', 'is_group_secretary', 'is_group_treasurer', 'status')
                ->get()
                ->map(function ($m) {
                    $role = 'Member';
                    if ($m->is_group_admin === 'Yes') $role = 'Chairperson';
                    elseif ($m->is_group_secretary === 'Yes') $role = 'Secretary';
                    elseif ($m->is_group_treasurer === 'Yes') $role = 'Treasurer';

                    return [
                        'id'           => $m->id,
                        'name'         => $m->name,
                        'first_name'   => $m->first_name,
                        'last_name'    => $m->last_name,
                        'phone_number' => $m->phone_number,
                        'sex'          => $m->sex,
                        'role'         => $role,
                        'status'       => $m->status ?? 'active',
                    ];
                });

            // Active loans for the group
            $activeLoans = [];
            if ($activeCycle) {
                $activeLoans = DB::table('vsla_loans')
                    ->where('cycle_id', $activeCycle->id)
                    ->where('status', 'active')
                    ->get()
                    ->map(function ($loan) {
                        return [
                            'id'            => $loan->id,
                            'borrower_id'   => $loan->borrower_id,
                            'loan_amount'   => $loan->loan_amount,
                            'amount_paid'   => $loan->amount_paid ?? 0,
                            'balance'       => $loan->balance ?? $loan->loan_amount,
                            'interest_rate' => $loan->interest_rate ?? 0,
                            'status'        => $loan->status,
                        ];
                    })->toArray();
            }

            // Action plans
            $actionPlans = [];
            if ($activeCycle) {
                try {
                    $actionPlans = DB::table('vsla_action_plans')
                        ->where('cycle_id', $activeCycle->id)
                        ->where('status', 'pending')
                        ->get()
                        ->map(function ($plan) {
                            return [
                                'id'          => $plan->id,
                                'description' => $plan->description ?? '',
                                'status'      => $plan->status,
                                'due_date'    => $plan->due_date ?? null,
                            ];
                        })->toArray();
                } catch (\Exception $e) {
                    // Table may not exist
                }
            }

            return $this->success([
                'group_id'   => $group->id,
                'group_info' => [
                    'id'                 => $group->id,
                    'name'               => $group->name,
                    'code'               => $group->code,
                    'meeting_frequency'  => $group->meeting_frequency,
                    'meeting_day'        => $group->meeting_day,
                    'meeting_venue'      => $group->meeting_venue,
                ],
                'cycle_info' => $activeCycle ? [
                    'id'          => $activeCycle->id,
                    'name'        => $activeCycle->name,
                    'start_date'  => $activeCycle->start_date,
                    'end_date'    => $activeCycle->end_date,
                    'share_value' => $activeCycle->share_value ?? 1000,
                    'saving_type' => $activeCycle->saving_type ?? 'shares',
                ] : null,
                'members_list' => [
                    'members'       => $members,
                    'total_members' => $members->count(),
                ],
                'active_loans'  => $activeLoans,
                'action_plans'  => $actionPlans,
            ], 'Group manifest loaded');
        } catch (\Exception $e) {
            Log::error('Facilitator groupManifest error: ' . $e->getMessage());
            return $this->error('Failed to load group manifest: ' . $e->getMessage());
        }
    }
}
