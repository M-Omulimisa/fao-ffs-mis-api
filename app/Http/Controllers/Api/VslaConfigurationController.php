<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\Location;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

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

            // Check authorization - only admin can update
            $user = Auth::user();
            if (!$user || $group->admin_id != $user->id) {
                return $this->error('Only group administrator can update group information', 403);
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
}
