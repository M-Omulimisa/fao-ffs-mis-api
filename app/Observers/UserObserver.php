<?php

namespace App\Observers;

use App\Models\User;
use App\Models\DtehmMembership;
use App\Models\MembershipPayment;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * This fires AFTER a new user is created
     */
    public function created(User $user)
    {
        Log::info('============ USER OBSERVER: CREATED EVENT ============', [
            'user_id' => $user->id,
            'is_dtehm_member' => $user->is_dtehm_member,
            'is_dip_member' => $user->is_dip_member,
        ]);
        
        $this->handleMembershipCreation($user);
    }

    /**
     * Handle the User "updated" event.
     * This fires AFTER an existing user is updated
     */
    public function updated(User $user)
    {
        Log::info('============ USER OBSERVER: UPDATED EVENT ============', [
            'user_id' => $user->id,
            'is_dtehm_member' => $user->is_dtehm_member,
            'is_dip_member' => $user->is_dip_member,
            'isDirty_dtehm' => $user->isDirty('is_dtehm_member'),
            'isDirty_dip' => $user->isDirty('is_dip_member'),
        ]);
        
        // Only create memberships if the membership status changed
        // OR if it's set to Yes but no membership exists yet
        if ($user->isDirty('is_dtehm_member') || $user->isDirty('is_dip_member')) {
            $this->handleMembershipCreation($user);
        } else {
            // Even if not dirty, check if membership should exist but doesn't
            $this->ensureMembershipsExist($user);
        }
    }

    /**
     * Handle membership creation for both DTEHM and DIP
     */
    protected function handleMembershipCreation(User $user)
    {
        $adminUser = \Admin::user();
        $adminId = $adminUser ? $adminUser->id : 1; // Fallback to admin ID 1
        
        // Handle DTEHM Membership (76,000 UGX)
        if ($user->is_dtehm_member == 'Yes') {
            $this->createDtehmMembershipIfNeeded($user, $adminId);
        }
        
        // Handle DIP Membership (20,000 UGX)
        if ($user->is_dip_member == 'Yes') {
            $this->createDipMembershipIfNeeded($user, $adminId);
        }
    }

    /**
     * Ensure memberships exist even if status hasn't changed
     */
    protected function ensureMembershipsExist(User $user)
    {
        $adminUser = \Admin::user();
        $adminId = $adminUser ? $adminUser->id : 1;
        
        if ($user->is_dtehm_member == 'Yes') {
            $this->createDtehmMembershipIfNeeded($user, $adminId);
        }
        
        if ($user->is_dip_member == 'Yes') {
            $this->createDipMembershipIfNeeded($user, $adminId);
        }
    }

    /**
     * Create DTEHM membership if it doesn't exist
     */
    protected function createDtehmMembershipIfNeeded(User $user, int $adminId)
    {
        // Check if DTEHM membership already exists
        $existingDtehm = DtehmMembership::where('user_id', $user->id)
            ->where('status', 'CONFIRMED')
            ->first();
        
        if (!$existingDtehm) {
            Log::info('OBSERVER: Creating DTEHM membership', ['user_id' => $user->id]);
            
            try {
                // Create DTEHM Membership (76,000 UGX)
                $dtehm = DtehmMembership::create([
                    'user_id' => $user->id,
                    'amount' => 76000,
                    'status' => 'CONFIRMED',
                    'payment_method' => 'CASH',
                    'registered_by_id' => $adminId,
                    'created_by' => $adminId,
                    'confirmed_by' => $adminId,
                    'confirmed_at' => now(),
                    'payment_date' => now(),
                    'description' => 'Auto-created by Observer during user registration',
                ]);
                
                // Update user model with DTEHM membership info (use saveQuietly to avoid infinite loop)
                $user->dtehm_membership_paid_at = now();
                $user->dtehm_membership_amount = 76000;
                $user->dtehm_membership_payment_id = $dtehm->id;
                $user->dtehm_membership_is_paid = 'Yes';
                $user->dtehm_membership_paid_date = now();
                $user->dtehm_member_membership_date = now();
                $user->saveQuietly(); // Save without triggering observers again
                
                Log::info('OBSERVER: DTEHM membership created successfully', [
                    'dtehm_id' => $dtehm->id,
                    'user_id' => $user->id,
                ]);
                
                admin_success('Success', 'DTEHM membership (UGX 76,000) created and marked as PAID');
            } catch (\Exception $e) {
                Log::error('OBSERVER: Failed to create DTEHM membership', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('OBSERVER: DTEHM membership already exists', [
                'user_id' => $user->id,
                'dtehm_id' => $existingDtehm->id,
            ]);
        }
    }

    /**
     * Create DIP membership if it doesn't exist
     */
    protected function createDipMembershipIfNeeded(User $user, int $adminId)
    {
        // Check if DIP membership already exists
        $existingDip = MembershipPayment::where('user_id', $user->id)
            ->where('status', 'CONFIRMED')
            ->first();
        
        if (!$existingDip) {
            Log::info('OBSERVER: Creating DIP membership', ['user_id' => $user->id]);
            
            try {
                // Create Regular DIP Membership (20,000 UGX)
                $membership = MembershipPayment::create([
                    'user_id' => $user->id,
                    'amount' => 20000,
                    'membership_type' => 'LIFE',
                    'status' => 'CONFIRMED',
                    'payment_method' => 'CASH',
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                    'registered_by_id' => $adminId,
                    'description' => 'Auto-created by Observer during user registration',
                ]);
                
                Log::info('OBSERVER: DIP membership created successfully', [
                    'membership_id' => $membership->id,
                    'user_id' => $user->id,
                ]);
                
                admin_success('Success', 'DIP membership (UGX 20,000) created and marked as PAID');
            } catch (\Exception $e) {
                Log::error('OBSERVER: Failed to create DIP membership', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('OBSERVER: DIP membership already exists', [
                'user_id' => $user->id,
                'membership_id' => $existingDip->id,
            ]);
        }
    }
}
