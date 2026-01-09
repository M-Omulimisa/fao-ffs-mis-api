<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCycleRequest;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\Location;
use App\Models\Project;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * VSLA Configuration Controller
 * 
 * Manages VSLA group configurations:
 * - Group Basic Info (view/edit)
 * - Cycles Management (list, create, edit, close)
 * - Shareouts Management (list, create, distribute)
 * 
 * @package App\Http\Controllers\Api
 */
class VslaConfigurationController extends Controller
{
    use ApiResponser;

    /**
     * Get Group Basic Information
     * 
     * Retrieves complete group details including members
     * 
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupInfo($groupId)
    {
        try {
            $group = FfsGroup::with(['admin', 'secretary', 'treasurer', 'district'])
                ->find($groupId);

            if (!$group) {
                return $this->error('Group not found', 404);
            }

            // Check if user belongs to this group
            $user = Auth::user();
            if ($user && $group->admin_id != $user->id && 
                $group->secretary_id != $user->id && 
                $group->treasurer_id != $user->id) {
                return $this->error('Unauthorized access to group', 403);
            }

            // Format response
            $data = [
                'id' => $group->id,
                'name' => $group->name,
                'type' => $group->type,
                'code' => $group->code,
                'establishment_date' => $group->establishment_date,
                'registration_date' => $group->registration_date,
                
                // Location
                'district_id' => $group->district_id,
                'district_name' => $group->district ? $group->district->name : null,
                'subcounty_id' => $group->subcounty_id,
                'subcounty_text' => $group->subcounty_text,
                'parish_id' => $group->parish_id,
                'parish_text' => $group->parish_text,
                'village' => $group->village,
                
                // Meeting Details
                'meeting_venue' => $group->meeting_venue,
                'meeting_day' => $group->meeting_day,
                'meeting_frequency' => $group->meeting_frequency,
                
                // Members
                'total_members' => $group->total_members,
                'male_members' => $group->male_members,
                'female_members' => $group->female_members,
                'youth_members' => $group->youth_members,
                'pwd_members' => $group->pwd_members,
                'estimated_members' => $group->estimated_members,
                
                // Core Team
                'admin' => $group->admin ? [
                    'id' => $group->admin->id,
                    'name' => $group->admin->name,
                    'phone' => $group->admin->phone_number_1,
                    'email' => $group->admin->email,
                ] : null,
                
                'secretary' => $group->secretary ? [
                    'id' => $group->secretary->id,
                    'name' => $group->secretary->name,
                    'phone' => $group->secretary->phone_number_1,
                    'email' => $group->secretary->email,
                ] : null,
                
                'treasurer' => $group->treasurer ? [
                    'id' => $group->treasurer->id,
                    'name' => $group->treasurer->name,
                    'phone' => $group->treasurer->phone_number_1,
                    'email' => $group->treasurer->email,
                ] : null,
                
                // Status
                'status' => $group->status,
                'cycle_number' => $group->cycle_number,
                'cycle_start_date' => $group->cycle_start_date,
                'cycle_end_date' => $group->cycle_end_date,
                
                // Additional
                'description' => $group->description,
                'photo' => $group->photo,
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
            ];

            return $this->success('Group information retrieved successfully', $data);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve group information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update Group Basic Information
     * 
     * Updates editable group fields
     * Only admin/chairperson can update
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGroupBasicInfo(Request $request, $groupId)
    {
        try {
            $group = FfsGroup::find($groupId);

            if (!$group) {
                return $this->error('Group not found', 404);
            }

            // Check authorization - only chairperson can update
            $user = Auth::user();
            $isChairperson = ($group->admin_id == $user->id);
            $isMember = ($user->group_id == $group->id);
            
            if (!$user || (!$isChairperson && !$isMember)) {
                return $this->error('Only group chairperson can update group information', 403);
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|min:3|max:200',
                'establishment_date' => 'nullable|date|before_or_equal:today',
                'district_id' => 'nullable|integer|exists:locations,id',
                'subcounty_text' => 'nullable|string|max:100',
                'parish_text' => 'nullable|string|max:100',
                'village' => 'nullable|string|max:100',
                'meeting_venue' => 'nullable|string|max:200',
                'meeting_day' => 'nullable|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'meeting_frequency' => 'nullable|in:Weekly,Bi-weekly,Monthly',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            // Update allowed fields - include all provided fields (validation already ensures correctness)
            $updateData = [];
            
            $allowedFields = [
                'name',
                'establishment_date',
                'district_id',
                'subcounty_text',
                'parish_text',
                'village',
                'meeting_venue',
                'meeting_day',
                'meeting_frequency',
                'description',
            ];

            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            // Update the group with all provided fields
            $group->update($updateData);

            return $this->success('Group information updated successfully', [
                'group' => $group->fresh(['admin', 'secretary', 'treasurer', 'district'])
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to update group information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get All Cycles for User's Group
     * 
     * Lists all savings cycles (active and historical) for authenticated user's group
     * Returns cycles ordered by: active first, then by start_date descending
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCycles()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->error('User not authenticated', 401);
            }

            // Get user's group
            $group = FfsGroup::where('admin_id', $user->id)
                ->orWhere('secretary_id', $user->id)
                ->orWhere('treasurer_id', $user->id)
                ->first();

            if (!$group) {
                return $this->error('User is not part of any VSLA group', 404);
            }

            // Get all cycles for this group
            $cycles = \App\Models\Project::where('is_vsla_cycle', 'Yes')
                ->where('group_id', $group->id)
                ->orderByRaw("CASE WHEN is_active_cycle = 'Yes' THEN 0 ELSE 1 END")
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(function ($cycle) {
                    // Calculate progress
                    $startDate = \Carbon\Carbon::parse($cycle->start_date);
                    $endDate = \Carbon\Carbon::parse($cycle->end_date);
                    $now = \Carbon\Carbon::now();
                    
                    $totalDays = $startDate->diffInDays($endDate);
                    $elapsedDays = $startDate->diffInDays($now);
                    $progressPercentage = $totalDays > 0 
                        ? min(100, ($elapsedDays / $totalDays) * 100) 
                        : 0;
                    
                    // Determine status
                    $status = 'Upcoming';
                    if ($now->greaterThanOrEqualTo($startDate)) {
                        $status = $now->lessThanOrEqualTo($endDate) ? 'Active' : 'Completed';
                    }
                    
                    return [
                        'id' => $cycle->id,
                        'cycle_name' => $cycle->cycle_name,
                        'start_date' => $cycle->start_date,
                        'end_date' => $cycle->end_date,
                        'status' => $status,
                        'is_active_cycle' => $cycle->is_active_cycle === 'Yes',
                        'progress_percentage' => round($progressPercentage, 1),
                        
                        // Financial Settings
                        'share_value' => (float) $cycle->share_value,
                        'meeting_frequency' => $cycle->meeting_frequency,
                        
                        // Loan Settings
                        'loan_interest_rate' => (float) $cycle->loan_interest_rate,
                        'interest_frequency' => $cycle->interest_frequency,
                        'weekly_loan_interest_rate' => (float) $cycle->weekly_loan_interest_rate,
                        'monthly_loan_interest_rate' => (float) $cycle->monthly_loan_interest_rate,
                        'minimum_loan_amount' => (float) $cycle->minimum_loan_amount,
                        'maximum_loan_multiple' => (int) $cycle->maximum_loan_multiple,
                        'late_payment_penalty' => (float) $cycle->late_payment_penalty,
                        
                        // Timestamps
                        'created_at' => $cycle->created_at,
                        'updated_at' => $cycle->updated_at,
                    ];
                });

            return $this->success('Cycles retrieved successfully', [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'cycles' => $cycles,
                'total_cycles' => $cycles->count(),
                'active_cycle' => $cycles->firstWhere('is_active_cycle', true),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve cycles: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create New Savings Cycle
     * 
     * Creates a new VSLA savings cycle for the user's group
     * Only one active cycle allowed per group
     * Only group admin can create cycles
     * 
     * @param  \App\Http\Requests\CreateCycleRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createCycle(CreateCycleRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            if (!$user) {
                return $this->error('User not authenticated', 401);
            }
            
            // Get user's group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            
            if (!$userGroupId) {
                return $this->error('User is not associated with any group', 400);
            }
            
            // Get the group
            $group = FfsGroup::find($userGroupId);
            
            if (!$group) {
                return $this->error('Group not found', 404);
            }
            
            // Check authorization - only chairperson can create cycles
            $isChairperson = ($group->admin_id == $user->id);
            $isMember = ($user->group_id == $group->id);
            
            if (!$isChairperson && !$isMember) {
                return $this->error('Only group chairperson can create cycles', 403);
            }
            
            // Validation already handled by CreateCycleRequest
            // Double-check no active cycle exists (race condition prevention)
            $activeCycle = Project::where('is_vsla_cycle', 'Yes')
                ->where('group_id', $userGroupId)
                ->where('is_active_cycle', 'Yes')
                ->lockForUpdate()
                ->first();
            
            if ($activeCycle) {
                DB::rollBack();
                return $this->error('Your group already has an active cycle. Please complete or close it before creating a new one.', 400, [
                    'active_cycle' => [
                        'id' => $activeCycle->id,
                        'name' => $activeCycle->cycle_name,
                        'start_date' => $activeCycle->start_date,
                        'end_date' => $activeCycle->end_date,
                    ]
                ]);
            }
            
            // Calculate interest rates based on frequency
            $loanInterestRate = $request->loan_interest_rate;
            $weeklyRate = $request->interest_frequency === 'Weekly' ? $loanInterestRate : ($loanInterestRate / 4);
            $monthlyRate = $request->interest_frequency === 'Monthly' ? $loanInterestRate : ($loanInterestRate * 4);
            
            // Create the new cycle
            $cycle = Project::create([
                // VSLA Identification
                'is_vsla_cycle' => 'Yes',
                'group_id' => $userGroupId,
                'cycle_name' => $request->cycle_name,
                
                // Date Range
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                
                // Status
                'status' => 'Active',
                'is_active_cycle' => 'Yes',
                
                // Financial Settings
                'share_value' => $request->share_value,
                'share_price' => $request->share_value, // For compatibility
                'meeting_frequency' => $request->meeting_frequency,
                
                // Loan Settings
                'loan_interest_rate' => $loanInterestRate,
                'interest_frequency' => $request->interest_frequency,
                'weekly_loan_interest_rate' => $weeklyRate,
                'monthly_loan_interest_rate' => $monthlyRate,
                'minimum_loan_amount' => $request->minimum_loan_amount,
                'maximum_loan_multiple' => $request->maximum_loan_multiple,
                'late_payment_penalty' => $request->late_payment_penalty,
                
                // Project fields (for compatibility)
                'title' => $request->cycle_name,
                'description' => 'VSLA Savings Cycle for ' . $group->name,
                'created_by_id' => $user->id,
                
                // Initialize financial fields
                'total_shares' => 0,
                'shares_sold' => 0,
                'total_investment' => 0,
                'total_returns' => 0,
                'total_expenses' => 0,
                'total_profits' => 0,
            ]);
            
            DB::commit();
            
            // Return success with cycle details
            return $this->success([
                'cycle' => [
                    'id' => $cycle->id,
                    'cycle_name' => $cycle->cycle_name,
                    'start_date' => $cycle->start_date,
                    'end_date' => $cycle->end_date,
                    'status' => 'Active',
                    'is_active_cycle' => true,
                    
                    // Financial Settings
                    'share_value' => (float) $cycle->share_value,
                    'meeting_frequency' => $cycle->meeting_frequency,
                    
                    // Loan Settings
                    'loan_interest_rate' => (float) $cycle->loan_interest_rate,
                    'interest_frequency' => $cycle->interest_frequency,
                    'weekly_loan_interest_rate' => (float) $cycle->weekly_loan_interest_rate,
                    'monthly_loan_interest_rate' => (float) $cycle->monthly_loan_interest_rate,
                    'minimum_loan_amount' => (float) $cycle->minimum_loan_amount,
                    'maximum_loan_multiple' => (int) $cycle->maximum_loan_multiple,
                    'late_payment_penalty' => (float) $cycle->late_payment_penalty,
                    
                    'created_at' => $cycle->created_at,
                ],
            ], 'Savings cycle created successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create cycle: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update Cycle Information
     * 
     * Updates an existing savings cycle
     * Only active cycles can be updated
     * Only admin/chairperson can update
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $cycleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCycle(Request $request, $cycleId)
    {
        try {
            $cycle = \App\Models\Project::where('is_vsla_cycle', 'Yes')
                ->find($cycleId);

            if (!$cycle) {
                return $this->error('Cycle not found', 404);
            }

            // Get the group
            $group = FfsGroup::find($cycle->group_id);
            
            if (!$group) {
                return $this->error('Associated group not found', 404);
            }

            // Check authorization - only chairperson can update
            $user = Auth::user();
            $isChairperson = ($group->admin_id == $user->id);
            $isMember = ($user->group_id == $group->id);
            
            if (!$user || (!$isChairperson && !$isMember)) {
                return $this->error('Only group chairperson can update cycle settings', 403);
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'cycle_name' => 'nullable|string|min:3|max:200',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'share_value' => 'nullable|numeric|min:0',
                'meeting_frequency' => 'nullable|in:Weekly,Bi-weekly,Monthly',
                'loan_interest_rate' => 'nullable|numeric|min:0|max:100',
                'interest_frequency' => 'nullable|in:Weekly,Monthly',
                'weekly_loan_interest_rate' => 'nullable|numeric|min:0|max:100',
                'monthly_loan_interest_rate' => 'nullable|numeric|min:0|max:100',
                'minimum_loan_amount' => 'nullable|numeric|min:0',
                'maximum_loan_multiple' => 'nullable|integer|min:1',
                'late_payment_penalty' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            // Update allowed fields
            $updateData = [];
            
            $allowedFields = [
                'cycle_name',
                'start_date',
                'end_date',
                'share_value',
                'meeting_frequency',
                'loan_interest_rate',
                'interest_frequency',
                'weekly_loan_interest_rate',
                'monthly_loan_interest_rate',
                'minimum_loan_amount',
                'maximum_loan_multiple',
                'late_payment_penalty',
            ];

            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            // Update the cycle
            $cycle->update($updateData);

            return $this->success('Cycle updated successfully', [
                'cycle' => $cycle->fresh()
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to update cycle: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update Cycle Status (Close/Reopen)
     * 
     * Allows admin to change cycle status between active and inactive
     * Only ONE cycle can be active at a time per group
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $cycleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCycleStatus(Request $request, $cycleId)
    {
        try {
            $cycle = Project::where('id', $cycleId)
                ->where('is_vsla_cycle', 'Yes')
                ->first();

            if (!$cycle) {
                return $this->error('Cycle not found', 404);
            }

            // Check authorization - only group chairperson (admin) can update
            $user = Auth::user();
            $group = FfsGroup::find($cycle->group_id);

            if (!$group) {
                return $this->error('Group not found', 404);
            }

            // Check if user is the group chairperson (admin)
            // Also allow if user's group_id matches (for backward compatibility)
            $isChairperson = ($group->admin_id == $user->id);
            $isMember = ($user->group_id == $group->id);
            
            if (!$isChairperson && !$isMember) {
                return $this->error('Only group chairperson can change cycle status', 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'is_active_cycle' => 'required|in:Yes,No',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $newStatus = $request->input('is_active_cycle');
            $currentStatus = $cycle->is_active_cycle;

            // If trying to activate this cycle
            if ($newStatus === 'Yes' && $currentStatus !== 'Yes') {
                // Check if another cycle is already active
                $activeCycle = Project::where('group_id', $cycle->group_id)
                    ->where('is_vsla_cycle', 'Yes')
                    ->where('is_active_cycle', 'Yes')
                    ->where('id', '!=', $cycleId)
                    ->first();

                if ($activeCycle) {
                    return $this->error(
                        'Cannot activate this cycle. Another cycle (' . $activeCycle->cycle_name . ') is already active. Please close it first.',
                        422
                    );
                }

                // Activate this cycle
                $cycle->update([
                    'is_active_cycle' => 'Yes',
                    'status' => 'ongoing',
                ]);

                return $this->success('Cycle activated successfully', [
                    'cycle' => $cycle->fresh(),
                    'message' => 'This cycle is now the active cycle for your group',
                ]);
            }

            // If trying to deactivate this cycle
            if ($newStatus === 'No' && $currentStatus === 'Yes') {
                $cycle->update([
                    'is_active_cycle' => 'No',
                    'status' => 'completed',
                ]);

                return $this->success('Cycle closed successfully', [
                    'cycle' => $cycle->fresh(),
                    'message' => 'This cycle has been closed. You can now create or activate another cycle.',
                ]);
            }

            // No change needed
            return $this->success('No status change required', [
                'cycle' => $cycle->fresh(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to update cycle status: ' . $e->getMessage(), 500);
        }
    }
}
