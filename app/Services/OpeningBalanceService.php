<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\LoanTransaction;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\SocialFundTransaction;
use App\Models\VslaLoan;
use App\Models\VslaOpeningBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles the fan-out logic from a submitted opening balance into live
 * operational tables (project_shares, vsla_loans, account_transactions, etc.)
 *
 * Shared by:
 *   – VslaOpeningBalanceController  (HTTP submit + reprocess endpoints)
 *   – VslaOpeningBalance model boot hook  (automatic on-save processing)
 *   – ProcessPendingOpeningBalances artisan command  (scheduled sweep)
 */
class OpeningBalanceService
{
    /**
     * Process a single VslaOpeningBalance record, writing all fan-out rows.
     * Idempotent: clears previous "Opening balance" account_transactions first.
     *
     * @param  VslaOpeningBalance  $ob           The header record (must have status=submitted)
     * @param  int                 $processedById User ID to stamp on created records
     * @return array               Summary (shares_created, loans_created, social_fund_records, totals, log)
     * @throws \Exception          On any DB error (caller should wrap in try/catch + transaction)
     */
    public function process(VslaOpeningBalance $ob, int $processedById): array
    {
        if ($ob->memberEntries()->count() === 0) {
            throw new \RuntimeException("No member entries found for opening balance id={$ob->id}");
        }

        $members = $ob->memberEntries->map(fn($e) => [
            'member_id'          => $e->member_id,
            'total_shares'       => (float) $e->total_shares,
            'share_count'        => (float) $e->share_count,
            'total_loan_amount'  => (float) $e->total_loan_amount,
            'loan_balance'       => (float) $e->loan_balance,
            'total_social_fund'  => (float) $e->total_social_fund,
        ])->toArray();

        $cycle        = Project::findOrFail($ob->cycle_id);
        $shareValue   = (float) ($cycle->share_value   ?? 1);
        $interestRate = (float) ($cycle->loan_interest_rate ?? 10);

        // ── Remove any stale opening-balance account_transactions ─────────────
        AccountTransaction::where('group_id',  $ob->group_id)
            ->where('cycle_id',   $ob->cycle_id)
            ->whereNull('meeting_id')
            ->where('description', 'like', 'Opening balance%')
            ->delete();

        // ── Fan-out per member ────────────────────────────────────────────────
        return $this->fanOut($ob, $members, $cycle, $shareValue, $interestRate, $processedById);
    }

    /**
     * Fan-out loop: writes project_shares, vsla_loans, loan_transactions,
     * social_fund_transactions and account_transactions double-entry pairs.
     */
    private function fanOut(
        VslaOpeningBalance $ob,
        array $members,
        Project $cycle,
        float $shareValue,
        float $interestRate,
        int $submittedById
    ): array {
        $sharesCreated     = 0;
        $loansCreated      = 0;
        $socialFundRecords = 0;
        $totalSharesAmt    = 0.0;
        $totalLoanAmt      = 0.0;
        $totalLoanBalance  = 0.0;
        $totalSocialFundAmt = 0.0;
        $memberSummaries   = [];
        $log               = [];

        $submissionDate = $ob->submission_date ?? now();
        $groupId        = $ob->group_id;

        foreach ($members as $m) {
            $memberId        = (int)   $m['member_id'];
            $totalShares     = (float) $m['total_shares'];
            $shareCount      = (float) $m['share_count'];
            $totalLoanAmount = (float) $m['total_loan_amount'];
            $loanBalance     = (float) $m['loan_balance'];
            $totalSocialFund = (float) $m['total_social_fund'];
            $memberName      = $m['name'] ?? "Member #{$memberId}";

            $shareCreated  = false;
            $loanCreated   = false;
            $socialCreated = false;

            // ── A. Shares ─────────────────────────────────────────────────────
            if ($totalShares > 0) {
                $numShares = ($shareCount > 0)
                    ? (int) round($shareCount)
                    : ($shareValue > 0 ? (int) floor($totalShares / $shareValue) : 0);

                if ($numShares > 0) {
                    ProjectShare::create([
                        'project_id'              => $cycle->id,
                        'investor_id'             => $memberId,
                        'purchase_date'           => $submissionDate,
                        'number_of_shares'        => $numShares,
                        'total_amount_paid'       => $totalShares,
                        'share_price_at_purchase' => $shareValue,
                        'payment_id'              => null,
                    ]);

                    $gShareTx = AccountTransaction::create([
                        'user_id'          => $submittedById,
                        'owner_type'       => 'group',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'share',
                        'source'           => 'deposit',
                        'amount'           => $totalShares,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – {$memberName} opening shares ({$numShares} @ {$shareValue})",
                        'is_contra_entry'  => false,
                        'created_by_id'    => $submittedById,
                    ]);
                    $mShareTx = AccountTransaction::create([
                        'user_id'          => $memberId,
                        'owner_type'       => 'member',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'share',
                        'source'           => 'deposit',
                        'amount'           => $totalShares,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – {$memberName} purchased {$numShares} shares",
                        'is_contra_entry'  => true,
                        'contra_entry_id'  => $gShareTx->id,
                        'created_by_id'    => $submittedById,
                    ]);
                    $gShareTx->update(['contra_entry_id' => $mShareTx->id]);

                    $sharesCreated++;
                    $shareCreated = true;
                    $log[] = "Share: member {$memberId} => {$numShares} shares @ {$shareValue} = {$totalShares}";
                }
            }

            // ── B. Loan ───────────────────────────────────────────────────────
            if ($totalLoanAmount > 0) {
                $amountPaid = max(0.0, $totalLoanAmount - $loanBalance);
                $loanStatus = ($loanBalance > 0) ? 'active' : 'paid';
                $dueDate    = Carbon::parse($submissionDate)->addMonths(3);

                $loan = VslaLoan::create([
                    'cycle_id'          => $cycle->id,
                    'meeting_id'        => null,
                    'borrower_id'       => $memberId,
                    'loan_amount'       => $totalLoanAmount,
                    'interest_rate'     => $interestRate,
                    'duration_months'   => 3,
                    'total_amount_due'  => $totalLoanAmount,
                    'amount_paid'       => $amountPaid,
                    'balance'           => $loanBalance,
                    'disbursement_date' => $submissionDate,
                    'due_date'          => $dueDate,
                    'purpose'           => 'Opening balance carry-over',
                    'status'            => $loanStatus,
                    'created_by_id'     => $submittedById,
                ]);

                LoanTransaction::create([
                    'loan_id'          => $loan->id,
                    'amount'           => $totalLoanAmount,
                    'transaction_date' => $submissionDate,
                    'description'      => 'Opening balance – initial loan disbursement',
                    'type'             => 'principal',
                    'transaction_type' => 'opening_balance',
                    'payment_method'   => 'opening_balance',
                    'created_by_id'    => $submittedById,
                ]);

                if ($amountPaid > 0) {
                    LoanTransaction::create([
                        'loan_id'          => $loan->id,
                        'amount'           => $amountPaid,
                        'transaction_date' => $submissionDate,
                        'description'      => 'Opening balance – prior repayment reflected',
                        'type'             => 'payment',
                        'transaction_type' => 'opening_balance',
                        'payment_method'   => 'opening_balance',
                        'created_by_id'    => $submittedById,
                    ]);
                }

                // account_transactions: loan (both negative — cash out / debt created)
                $gLoanTx = AccountTransaction::create([
                    'user_id'                 => $submittedById,
                    'owner_type'              => 'group',
                    'group_id'                => $groupId,
                    'meeting_id'              => null,
                    'cycle_id'                => $cycle->id,
                    'account_type'            => 'loan',
                    'source'                  => 'disbursement',
                    'amount'                  => -$totalLoanAmount,
                    'transaction_date'        => $submissionDate,
                    'description'             => "Opening balance – loan carry-over disbursed to {$memberName}",
                    'related_disbursement_id' => $loan->id,
                    'is_contra_entry'         => false,
                    'created_by_id'           => $submittedById,
                ]);
                $mLoanTx = AccountTransaction::create([
                    'user_id'                 => $memberId,
                    'owner_type'              => 'member',
                    'group_id'                => $groupId,
                    'meeting_id'              => null,
                    'cycle_id'                => $cycle->id,
                    'account_type'            => 'loan',
                    'source'                  => 'disbursement',
                    'amount'                  => -$totalLoanAmount,
                    'transaction_date'        => $submissionDate,
                    'description'             => "Opening balance – {$memberName} carry-over loan of {$totalLoanAmount}",
                    'related_disbursement_id' => $loan->id,
                    'is_contra_entry'         => true,
                    'contra_entry_id'         => $gLoanTx->id,
                    'created_by_id'           => $submittedById,
                ]);
                $gLoanTx->update(['contra_entry_id' => $mLoanTx->id]);

                // account_transactions: prior repayment (both positive — cash back / debt reduced)
                if ($amountPaid > 0) {
                    $gRepTx = AccountTransaction::create([
                        'user_id'          => $submittedById,
                        'owner_type'       => 'group',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'loan_repayment',
                        'source'           => 'deposit',
                        'amount'           => $amountPaid,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – prior repayment from {$memberName}",
                        'is_contra_entry'  => true,
                        'created_by_id'    => $submittedById,
                    ]);
                    $mRepTx = AccountTransaction::create([
                        'user_id'          => $memberId,
                        'owner_type'       => 'member',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'loan_repayment',
                        'source'           => 'deposit',
                        'amount'           => $amountPaid,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – {$memberName} prior loan repayment",
                        'is_contra_entry'  => true,
                        'contra_entry_id'  => $gRepTx->id,
                        'created_by_id'    => $submittedById,
                    ]);
                    $gRepTx->update(['contra_entry_id' => $mRepTx->id]);
                }

                $loansCreated++;
                $loanCreated = true;
                $log[] = "Loan: member {$memberId} => amount={$totalLoanAmount}, balance={$loanBalance}, paid={$amountPaid}";
            }

            // ── C. Social Fund ────────────────────────────────────────────────
            if ($totalSocialFund > 0) {
                SocialFundTransaction::create([
                    'group_id'         => $groupId,
                    'cycle_id'         => $cycle->id,
                    'member_id'        => $memberId,
                    'meeting_id'       => null,
                    'transaction_type' => 'contribution',
                    'amount'           => $totalSocialFund,
                    'transaction_date' => $submissionDate,
                    'description'      => 'Opening balance – initial social fund contribution',
                    'reason'           => 'opening_balance',
                    'created_by_id'    => $submittedById,
                ]);

                $mSfTx = AccountTransaction::create([
                    'user_id'          => $memberId,
                    'owner_type'       => 'member',
                    'group_id'         => $groupId,
                    'meeting_id'       => null,
                    'cycle_id'         => $cycle->id,
                    'account_type'     => 'social_fund',
                    'source'           => 'deposit',
                    'amount'           => -$totalSocialFund,
                    'transaction_date' => $submissionDate,
                    'description'      => "Opening balance – {$memberName} social fund contribution",
                    'is_contra_entry'  => false,
                    'created_by_id'    => $submittedById,
                ]);
                $gSfTx = AccountTransaction::create([
                    'user_id'          => $submittedById,
                    'owner_type'       => 'group',
                    'group_id'         => $groupId,
                    'meeting_id'       => null,
                    'cycle_id'         => $cycle->id,
                    'account_type'     => 'social_fund',
                    'source'           => 'deposit',
                    'amount'           => $totalSocialFund,
                    'transaction_date' => $submissionDate,
                    'description'      => "Opening balance – group received social fund from {$memberName}",
                    'is_contra_entry'  => true,
                    'contra_entry_id'  => $mSfTx->id,
                    'created_by_id'    => $submittedById,
                ]);
                $mSfTx->update(['contra_entry_id' => $gSfTx->id]);

                $socialFundRecords++;
                $socialCreated = true;
                $log[] = "SocialFund: member {$memberId} => {$totalSocialFund}";
            }

            $totalSharesAmt     += $totalShares;
            $totalLoanAmt       += $totalLoanAmount;
            $totalLoanBalance   += $loanBalance;
            $totalSocialFundAmt += $totalSocialFund;

            $memberSummaries[] = [
                'member_id'          => $memberId,
                'name'               => $memberName,
                'total_shares'       => $totalShares,
                'share_count'        => ($shareCount > 0)
                    ? (int) $shareCount
                    : ($shareValue > 0 ? (int) floor($totalShares / $shareValue) : 0),
                'total_loan_amount'  => $totalLoanAmount,
                'loan_balance'       => $loanBalance,
                'total_social_fund'  => $totalSocialFund,
                'share_record'       => $shareCreated,
                'loan_record'        => $loanCreated,
                'social_fund_record' => $socialCreated,
            ];
        }

        return [
            'shares_created'      => $sharesCreated,
            'loans_created'       => $loansCreated,
            'social_fund_records' => $socialFundRecords,
            'totals' => [
                'total_shares_amount' => $totalSharesAmt,
                'total_loan_amount'   => $totalLoanAmt,
                'total_loan_balance'  => $totalLoanBalance,
                'total_social_fund'   => $totalSocialFundAmt,
            ],
            'member_summaries' => $memberSummaries,
            'log'              => $log,
        ];
    }
}
