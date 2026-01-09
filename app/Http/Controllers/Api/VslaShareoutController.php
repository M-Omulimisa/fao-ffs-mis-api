<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateShareoutRequest;
use App\Models\VslaShareout;
use App\Models\VslaShareoutDistribution;
use App\Models\Project;
use App\Services\ShareoutCalculationService;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * VSLA Shareout API Controller
 * 
 * Implements a 6-step wizard for cycle shareout process:
 * 
 * Step 1: Get active cycles available for shareout
 * Step 2: Initiate shareout for selected cycle
 * Step 3: Calculate distributions
 * Step 4: Get member-by-member breakdown
 * Step 5: Get summary statistics
 * Step 6: Approve and complete shareout
 */
class VslaShareoutController extends Controller
{
    use ApiResponser;
    
    protected $calculationService;
    
    public function __construct(ShareoutCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }
    
    /**
     * STEP 1: Get list of active cycles that can be shared out
     * 
     * GET /api/vsla/shareouts/available-cycles
     */
    public function getAvailableCycles(Request $request)
    {
        try {
            // Use auth()->user() (default guard) or $request->userModel set by EnsureTokenIsValid middleware
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            // Get the user's group_id (check multiple possible field names)
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            
            // Get active cycles for user's group
            $cycles = Project::where('is_vsla_cycle', 'Yes')
                ->where('is_active_cycle', 'Yes')
                ->when($userGroupId, function ($query) use ($userGroupId) {
                    return $query->where('group_id', $userGroupId);
                })
                ->with('group')
                ->get()
                ->map(function ($cycle) {
                    // Check if shareout already exists
                    $existingShareout = VslaShareout::where('cycle_id', $cycle->id)
                        ->whereNotIn('status', ['cancelled', 'completed'])
                        ->first();
                    
                    return [
                        'cycle_id' => $cycle->id,
                        'cycle_name' => $cycle->cycle_name,
                        'group_id' => $cycle->group_id,
                        'group_name' => $cycle->group->name ?? 'N/A',
                        'start_date' => $cycle->start_date->format('Y-m-d'),
                        'end_date' => $cycle->end_date->format('Y-m-d'),
                        'share_value' => (float) $cycle->share_value,
                        'has_existing_shareout' => $existingShareout ? true : false,
                        'existing_shareout_id' => $existingShareout ? $existingShareout->id : null,
                        'existing_shareout_status' => $existingShareout ? $existingShareout->status : null,
                    ];
                });
            
            return $this->success('Available cycles retrieved successfully', $cycles);            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve cycles: ' . $e->getMessage(), 500);        }
    }
    
    /**
     * STEP 2: Initiate shareout (create draft)
     * 
     * POST /api/vsla/shareouts/initiate
     * Body: { cycle_id: 123 }
     */
    public function initiateShareout(InitiateShareoutRequest $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            // Validation already handled by InitiateShareoutRequest
            $cycleId = $request->cycle_id;
            
            // Check if cycle is valid
            $cycle = Project::where('id', $cycleId)
                ->where('is_vsla_cycle', 'Yes')
                ->where('is_active_cycle', 'Yes')
                ->first();
            
            if (!$cycle) {
                return $this->error('Invalid cycle or cycle is not active', 400);
            }
            
            // Check if shareout already exists
            $existingShareout = VslaShareout::where('cycle_id', $cycleId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->first();
            
            if ($existingShareout) {
                return $this->success([
                    'shareout_id' => $existingShareout->id,
                    'status' => $existingShareout->status,
                    'message' => 'Shareout already exists for this cycle',
                ], 'Existing shareout found');
            }
            
            // Create new shareout
            $shareout = VslaShareout::create([
                'cycle_id' => $cycleId,
                'group_id' => $cycle->group_id,
                'shareout_date' => now(),
                'share_unit_value' => $cycle->share_value ?? 0,
                'status' => 'draft',
                'initiated_by_id' => $user->id,
            ]);
            
            return $this->success([
                'shareout_id' => $shareout->id,
                'cycle_name' => $cycle->cycle_name,
                'status' => $shareout->status,
            ], 'Shareout initiated successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to initiate shareout: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * STEP 3: Calculate distributions
     * 
     * POST /api/vsla/shareouts/{shareout_id}/calculate
     */
    public function calculateDistributions($shareoutId)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            $shareout = VslaShareout::find($shareoutId);
            
            if (!$shareout) {
                return $this->error('Shareout not found', 404);
            }
            
            // Verify ownership - user must belong to the same group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            if (!$userGroupId || $shareout->group_id != $userGroupId) {
                return $this->error('Unauthorized: Shareout belongs to a different group', 403);
            }
            
            if (!$shareout->canRecalculate()) {
                return $this->error('Shareout cannot be recalculated in current status: ' . $shareout->status, 400);
            }
            
            // Perform calculation
            $result = $this->calculationService->calculateShareout(
                $shareout->cycle_id,
                $user->id
            );
            
            if (!$result['success']) {
                return $this->error($result['message'], 400);
            }
            
            $calculatedShareout = $result['shareout'];
            
            return $this->success([
                'shareout_id' => $calculatedShareout->id,
                'status' => $calculatedShareout->status,
                'summary' => $calculatedShareout->getSummary(),
            ], 'Distributions calculated successfully');
            
        } catch (\Exception $e) {
            return $this->error('Calculation failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * STEP 4: Get member-by-member breakdown
     * 
     * GET /api/vsla/shareouts/{shareout_id}/distributions
     */
    public function getMemberDistributions($shareoutId)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            $shareout = VslaShareout::with(['distributions.member'])->find($shareoutId);
            
            if (!$shareout) {
                return $this->error('Shareout not found', 404);
            }
            
            // Verify ownership - user must belong to the same group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            if (!$userGroupId || $shareout->group_id != $userGroupId) {
                return $this->error('Unauthorized: Shareout belongs to a different group', 403);
            }
            
            $distributions = $shareout->distributions->map(function ($dist) {
                return $dist->getBreakdown();
            });
            
            return $this->success([
                'shareout_id' => $shareout->id,
                'status' => $shareout->status,
                'distributions' => $distributions,
                'total_members' => $distributions->count(),
            ], 'Member distributions retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve distributions: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * STEP 5: Get summary statistics
     * 
     * GET /api/vsla/shareouts/{shareout_id}/summary
     */
    public function getShareoutSummary($shareoutId)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            $shareout = VslaShareout::with(['cycle', 'group', 'distributions'])->find($shareoutId);
            
            if (!$shareout) {
                return $this->error('Shareout not found', 404);
            }
            
            // Verify ownership - user must belong to the same group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            if (!$userGroupId || $shareout->group_id != $userGroupId) {
                return $this->error('Unauthorized: Shareout belongs to a different group', 403);
            }
            
            $summary = $shareout->getSummary();
            
            // Add additional statistics
            $summary['cycle_info'] = [
                'cycle_name' => $shareout->cycle->cycle_name,
                'start_date' => $shareout->cycle->start_date->format('Y-m-d'),
                'end_date' => $shareout->cycle->end_date->format('Y-m-d'),
                'duration_months' => $shareout->cycle->start_date->diffInMonths($shareout->cycle->end_date),
            ];
            
            $summary['group_info'] = [
                'group_name' => $shareout->group->name,
                'group_id' => $shareout->group->id,
            ];
            
            // Calculate additional stats
            $distributions = $shareout->distributions;
            $summary['distribution_stats'] = [
                'members_with_positive_payout' => $distributions->where('final_payout', '>', 0)->count(),
                'members_with_loans' => $distributions->where('outstanding_loan_total', '>', 0)->count(),
                'highest_payout' => (float) $distributions->max('final_payout'),
                'lowest_payout' => (float) $distributions->min('final_payout'),
                'average_payout' => (float) $distributions->avg('final_payout'),
            ];
            
            return $this->success($summary, 'Shareout summary retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve summary: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * STEP 6a: Approve shareout (ready for completion)
     * 
     * POST /api/vsla/shareouts/{shareout_id}/approve
     */
    public function approveShareout(Request $request, $shareoutId)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            $shareout = VslaShareout::find($shareoutId);
            
            if (!$shareout) {
                return $this->error('Shareout not found', 404);
            }
            
            // Verify ownership - user must belong to the same group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            if (!$userGroupId || $shareout->group_id != $userGroupId) {
                return $this->error('Unauthorized: Shareout belongs to a different group', 403);
            }
            
            if (!$shareout->canApprove()) {
                return $this->error('Shareout cannot be approved in current status: ' . $shareout->status, 400);
            }
            
            // Verify distributions exist
            if ($shareout->distributions()->count() === 0) {
                return $this->error('Cannot approve: No distributions calculated yet', 400);
            }
            
            // Add optional notes
            if ($request->has('notes')) {
                $shareout->update(['admin_notes' => $request->notes]);
            }
            
            $shareout->markAsApproved($user->id);
            
            return $this->success([
                'shareout_id' => $shareout->id,
                'status' => $shareout->status,
            ], 'Shareout approved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Approval failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * STEP 6b: Complete shareout and close cycle
     * 
     * POST /api/vsla/shareouts/{shareout_id}/complete
     */
    public function completeShareout($shareoutId)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            $shareout = VslaShareout::find($shareoutId);
            
            if (!$shareout) {
                return $this->error('Shareout not found', 404);
            }
            
            // Verify ownership - user must belong to the same group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            if (!$userGroupId || $shareout->group_id != $userGroupId) {
                return $this->error('Unauthorized: Shareout belongs to a different group', 403);
            }
            
            $result = $this->calculationService->completeShareout($shareoutId, $user->id);
            
            if (!$result['success']) {
                return $this->error($result['message'], 400);
            }
            
            return $this->success([
                'shareout_id' => $result['shareout']->id,
                'status' => $result['shareout']->status,
                'cycle_closed' => true,
            ], $result['message']);
            
        } catch (\Exception $e) {
            return $this->error('Completion failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get shareout details
     * 
     * GET /api/vsla/shareouts/{shareout_id}
     */
    public function getShareout($shareoutId)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            $shareout = VslaShareout::with(['cycle', 'group', 'distributions.member'])
                ->find($shareoutId);
            
            if (!$shareout) {
                return $this->error('Shareout not found', 404);
            }
            
            // Verify ownership - user must belong to the same group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            if (!$userGroupId || $shareout->group_id != $userGroupId) {
                return $this->error('Unauthorized: Shareout belongs to a different group', 403);
            }
            
            return $this->success([
                'shareout' => $shareout->getSummary(),
                'distributions' => $shareout->distributions->map(fn($d) => $d->getBreakdown()),
            ], 'Shareout details retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve shareout: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Cancel shareout
     * 
     * POST /api/vsla/shareouts/{shareout_id}/cancel
     */
    public function cancelShareout($shareoutId)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }
            
            $shareout = VslaShareout::find($shareoutId);
            
            if (!$shareout) {
                return $this->error('Shareout not found', 404);
            }
            
            // Verify ownership - user must belong to the same group
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            if (!$userGroupId || $shareout->group_id != $userGroupId) {
                return $this->error('Unauthorized: Shareout belongs to a different group', 403);
            }
            
            if (!$shareout->canCancel()) {
                return $this->error('Shareout cannot be cancelled in current status: ' . $shareout->status, 400);
            }
            
            $shareout->markAsCancelled();
            
            return $this->success([
                'shareout_id' => $shareout->id,
                'status' => $shareout->status,
            ], 'Shareout cancelled successfully');
            
        } catch (\Exception $e) {
            return $this->error('Cancellation failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get shareout history for a group
     * 
     * GET /api/vsla/shareouts/history
     */
    public function getShareoutHistory(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Get the user's group_id (check multiple possible field names)
            $userGroupId = $user->group_id ?? $user->ffs_group_id ?? null;
            
            if (!$user || !$userGroupId) {
                return $this->error('User not associated with a group', 400);
            }
            
            $shareouts = VslaShareout::where('group_id', $userGroupId)
                ->with(['cycle'])
                ->orderBy('shareout_date', 'desc')
                ->get()
                ->map(function ($shareout) {
                    return $shareout->getSummary();
                });
            
            return $this->success('Shareout history retrieved successfully', $shareouts);
            
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve history: ' . $e->getMessage(), 500);
        }
    }
}
