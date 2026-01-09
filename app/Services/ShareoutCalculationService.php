<?php

namespace App\Services;

use App\Models\VslaShareout;
use App\Models\VslaShareoutDistribution;
use App\Models\Project;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\ProjectShare;
use App\Models\VslaLoan;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * VSLA Shareout Calculation Service
 * 
 * This service handles all complex financial calculations for distributing
 * cycle funds back to members when closing a savings cycle.
 * 
 * CALCULATION FLOW:
 * 1. Calculate Total Distributable Fund (savings + shares + interest + fines)
 * 2. Get all members and their share counts
 * 3. Calculate each member's proportional share
 * 4. Deduct outstanding loans from member payouts
 * 5. Generate distribution records for each member
 * 
 * FORMULA:
 * Member Payout = [(Member Shares / Total Shares) × Distributable Fund] - Outstanding Loans
 */
class ShareoutCalculationService
{
    /**
     * Calculate complete shareout for a cycle
     * 
     * @param int $cycleId
     * @param int $userId - User initiating the calculation
     * @return array ['success' => bool, 'shareout' => VslaShareout|null, 'message' => string]
     */
    public function calculateShareout(int $cycleId, int $userId): array
    {
        DB::beginTransaction();
        
        try {
            // 1. Validate cycle exists and is active
            $cycle = Project::where('id', $cycleId)
                ->where('is_vsla_cycle', 'Yes')
                ->first();
            
            if (!$cycle) {
                return [
                    'success' => false,
                    'shareout' => null,
                    'message' => 'Cycle not found or is not a VSLA cycle',
                ];
            }
            
            if ($cycle->is_active_cycle !== 'Yes') {
                return [
                    'success' => false,
                    'shareout' => null,
                    'message' => 'Only active cycles can be shared out',
                ];
            }
            
            // 2. Check if shareout already exists for this cycle
            $existingShareout = VslaShareout::where('cycle_id', $cycleId)
                ->whereNotIn('status', ['cancelled'])
                ->first();
            
            if ($existingShareout) {
                // Recalculate existing shareout
                $shareout = $existingShareout;
            } else {
                // Create new shareout
                $shareout = VslaShareout::create([
                    'cycle_id' => $cycleId,
                    'group_id' => $cycle->group_id,
                    'shareout_date' => now(),
                    'share_unit_value' => $cycle->share_value ?? 0,
                    'status' => 'draft',
                    'initiated_by_id' => $userId,
                ]);
            }
            
            // 3. Calculate financial totals
            $financials = $this->calculateFinancialTotals($cycleId);
            
            // 4. Get all members with shares in this cycle
            $memberData = $this->getMemberShareData($cycleId);
            
            if (empty($memberData['members'])) {
                DB::rollBack();
                return [
                    'success' => false,
                    'shareout' => null,
                    'message' => 'No members with shares found in this cycle',
                ];
            }
            
            // 5. Calculate final share value (original + profit per share)
            $totalShares = $memberData['total_shares'];
            $profitPerShare = $totalShares > 0 
                ? $financials['total_distributable_fund'] / $totalShares 
                : 0;
            
            // 6. Update shareout with calculated totals
            $shareout->update([
                'total_savings' => $financials['total_savings'],
                'total_share_value' => $financials['total_share_value'],
                'total_loan_interest_earned' => $financials['total_loan_interest_earned'],
                'total_fines_collected' => $financials['total_fines_collected'],
                'total_distributable_fund' => $financials['total_distributable_fund'],
                'total_outstanding_loans' => $financials['total_outstanding_loans'],
                'total_members' => count($memberData['members']),
                'total_shares' => $totalShares,
                'final_share_value' => $profitPerShare,
            ]);
            
            // 7. Delete existing distributions before recalculating
            // Use DB delete to ensure it runs immediately
            DB::table('vsla_shareout_distributions')
                ->where('shareout_id', $shareout->id)
                ->delete();
            
            // 8. Calculate distribution for each member
            $totalActualPayout = 0;
            foreach ($memberData['members'] as $memberShares) {
                $distribution = $this->calculateMemberDistribution(
                    $shareout,
                    $memberShares,
                    $financials,
                    $totalShares
                );
                
                VslaShareoutDistribution::create($distribution);
                $totalActualPayout += $distribution['final_payout'];
            }
            
            // 9. Update shareout with total actual payout
            $shareout->update([
                'total_actual_payout' => $totalActualPayout,
                'status' => 'calculated',
                'calculated_at' => now(),
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'shareout' => $shareout->fresh(['distributions', 'cycle', 'group']),
                'message' => 'Shareout calculated successfully',
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Shareout calculation failed: ' . $e->getMessage(), [
                'cycle_id' => $cycleId,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'shareout' => null,
                'message' => 'Calculation failed: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Calculate total financial amounts for the cycle
     */
    private function calculateFinancialTotals(int $cycleId): array
    {
        // Total member savings (via AccountTransaction)
        $totalSavings = AccountTransaction::where('cycle_id', $cycleId)
            ->where('owner_type', 'member')
            ->where('account_type', 'savings')
            ->sum('amount');
        
        // Total share purchases (via ProjectShare)
        $totalShareValue = ProjectShare::where('project_id', $cycleId)
            ->sum('total_amount_paid');
        
        // Total fines collected
        $totalFines = AccountTransaction::where('cycle_id', $cycleId)
            ->where('owner_type', 'member')
            ->where('account_type', 'fine')
            ->sum('amount');
        
        // Calculate loan interest earned
        $loans = VslaLoan::where('cycle_id', $cycleId)->get();
        $totalLoanInterestEarned = 0;
        $totalOutstandingLoans = 0;
        
        foreach ($loans as $loan) {
            // Interest = total_amount_due - loan_amount
            $interestDue = $loan->total_amount_due - $loan->loan_amount;
            
            // Calculate how much interest has been paid
            $principalPaid = $loan->loan_amount - $loan->balance;
            $totalPaid = $loan->amount_paid;
            $interestPaid = $totalPaid - $principalPaid;
            
            // Only count paid interest as earned
            $totalLoanInterestEarned += max(0, $interestPaid);
            
            // Outstanding loans (principal + interest)
            if ($loan->status === 'active') {
                $totalOutstandingLoans += $loan->balance;
            }
        }
        
        // Total distributable fund
        $totalDistributableFund = $totalSavings + $totalShareValue + $totalLoanInterestEarned + $totalFines;
        
        return [
            'total_savings' => $totalSavings,
            'total_share_value' => $totalShareValue,
            'total_loan_interest_earned' => $totalLoanInterestEarned,
            'total_fines_collected' => $totalFines,
            'total_distributable_fund' => $totalDistributableFund,
            'total_outstanding_loans' => $totalOutstandingLoans,
        ];
    }
    
    /**
     * Get member share data
     */
    private function getMemberShareData(int $cycleId): array
    {
        $shareRecords = ProjectShare::where('project_id', $cycleId)
            ->with('investor')
            ->get();
        
        // Group by member
        $memberShares = [];
        $totalShares = 0;
        
        foreach ($shareRecords as $record) {
            $memberId = $record->investor_id;
            
            if (!isset($memberShares[$memberId])) {
                $memberShares[$memberId] = [
                    'member_id' => $memberId,
                    'member_name' => $record->investor->name ?? 'Unknown',
                    'shares_count' => 0,
                    'shares_value' => 0,
                ];
            }
            
            $memberShares[$memberId]['shares_count'] += $record->number_of_shares;
            $memberShares[$memberId]['shares_value'] += $record->total_amount_paid;
            $totalShares += $record->number_of_shares;
        }
        
        return [
            'members' => array_values($memberShares),
            'total_shares' => $totalShares,
        ];
    }
    
    /**
     * Calculate distribution for a single member
     */
    private function calculateMemberDistribution(
        VslaShareout $shareout,
        array $memberShares,
        array $financials,
        int $totalShares
    ): array {
        $memberId = $memberShares['member_id'];
        $memberSharesCount = $memberShares['shares_count'];
        
        // Calculate share percentage
        $sharePercentage = $totalShares > 0 
            ? ($memberSharesCount / $totalShares) * 100 
            : 0;
        
        // Member's proportional distribution
        $proportionalDistribution = $totalShares > 0
            ? ($memberSharesCount / $totalShares) * $financials['total_distributable_fund']
            : 0;
        
        // Member's share of loan interest
        $loanInterestShare = $totalShares > 0
            ? ($memberSharesCount / $totalShares) * $financials['total_loan_interest_earned']
            : 0;
        
        // Member's share of fines
        $fineShare = $totalShares > 0
            ? ($memberSharesCount / $totalShares) * $financials['total_fines_collected']
            : 0;
        
        // Get member's savings
        $memberSavings = AccountTransaction::where('cycle_id', $shareout->cycle_id)
            ->where('owner_type', 'member')
            ->where('user_id', $memberId)
            ->where('account_type', 'savings')
            ->sum('amount');
        
        // Get member's fines paid
        $memberFinesPaid = AccountTransaction::where('cycle_id', $shareout->cycle_id)
            ->where('owner_type', 'member')
            ->where('user_id', $memberId)
            ->where('account_type', 'fine')
            ->sum('amount');
        
        // Get member's welfare contributions
        $memberWelfare = AccountTransaction::where('cycle_id', $shareout->cycle_id)
            ->where('owner_type', 'member')
            ->where('user_id', $memberId)
            ->where('account_type', 'welfare')
            ->sum('amount');
        
        // Get member's outstanding loans
        $memberLoans = VslaLoan::where('cycle_id', $shareout->cycle_id)
            ->where('borrower_id', $memberId)
            ->where('status', 'active')
            ->get();
        
        $outstandingPrincipal = 0;
        $outstandingInterest = 0;
        
        foreach ($memberLoans as $loan) {
            $outstandingPrincipal += $loan->balance;
            
            // Calculate interest on outstanding amount
            $interestDue = $loan->total_amount_due - $loan->loan_amount;
            $principalPaid = $loan->loan_amount - $loan->balance;
            $totalPaid = $loan->amount_paid;
            $interestPaid = $totalPaid - $principalPaid;
            $interestUnpaid = $interestDue - $interestPaid;
            
            $outstandingInterest += max(0, $interestUnpaid);
        }
        
        $outstandingLoanTotal = $outstandingPrincipal + $outstandingInterest;
        
        // Calculate totals
        $totalEntitled = $proportionalDistribution;
        $totalDeductions = $outstandingLoanTotal;
        $finalPayout = max(0, $totalEntitled - $totalDeductions);
        
        return [
            'shareout_id' => $shareout->id,
            'member_id' => $memberId,
            'member_savings' => $memberSavings,
            'member_shares' => $memberSharesCount,
            'member_share_value' => $memberShares['shares_value'],
            'member_fines_paid' => $memberFinesPaid,
            'member_welfare_contribution' => $memberWelfare,
            'share_percentage' => $sharePercentage,
            'proportional_distribution' => $proportionalDistribution,
            'loan_interest_share' => $loanInterestShare,
            'fine_share' => $fineShare,
            'outstanding_loan_principal' => $outstandingPrincipal,
            'outstanding_loan_interest' => $outstandingInterest,
            'outstanding_loan_total' => $outstandingLoanTotal,
            'total_entitled' => $totalEntitled,
            'total_deductions' => $totalDeductions,
            'final_payout' => $finalPayout,
            'payment_status' => 'pending',
        ];
    }
    
    /**
     * Complete shareout and close cycle
     */
    public function completeShareout(int $shareoutId, int $userId): array
    {
        DB::beginTransaction();
        
        try {
            $shareout = VslaShareout::with(['cycle', 'distributions'])->find($shareoutId);
            
            if (!$shareout) {
                return [
                    'success' => false,
                    'message' => 'Shareout not found',
                ];
            }
            
            // Allow completion from calculated or approved status
            if (!in_array($shareout->status, ['calculated', 'approved'])) {
                return [
                    'success' => false,
                    'message' => 'Shareout must be calculated or approved before completion',
                ];
            }
            
            // Auto-approve if still in calculated status
            if ($shareout->status === 'calculated') {
                $shareout->markAsApproved($userId);
            }
            
            // Mark all distributions as paid (in real scenario, this would be done individually)
            foreach ($shareout->distributions as $distribution) {
                $distribution->markAsPaid($userId, 'cash');
            }
            
            // Mark shareout as completed
            $shareout->markAsCompleted($userId);
            
            // Close the cycle
            $cycle = $shareout->cycle;
            $cycle->update([
                'is_active_cycle' => 'No',
                'status' => 'completed',
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'shareout' => $shareout->fresh(),
                'message' => 'Shareout completed successfully and cycle closed',
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Shareout completion failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Completion failed: ' . $e->getMessage(),
            ];
        }
    }
}
