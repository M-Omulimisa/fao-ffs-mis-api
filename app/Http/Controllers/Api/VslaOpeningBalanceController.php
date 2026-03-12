<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\LoanTransaction;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\SocialFundTransaction;
use App\Models\User;
use App\Models\VslaLoan;
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
 * Records each member's opening financial position at the start of a cycle,
 * then fans that data out into the live operational tables:
 *   • project_shares          – converted savings as share purchases
 *   • vsla_loans              – outstanding loans at cycle start
 *   • loan_transactions       – initial disbursement & any prior payments
 *   • social_fund_transactions – initial social-fund contributions
 *
 * Endpoints:
 *   GET  vsla/opening-balance/members   – list group members for data entry
 *   GET  vsla/opening-balance/status    – check if submitted for this cycle
 *   POST vsla/opening-balance/submit    – submit + process opening balances
 *   GET  vsla/opening-balance/{id}      – retrieve a submitted record
 */
class VslaOpeningBalanceController extends Controller
{
    // ─── 1. List members ───────────────────────────────────────────────────────

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

            $members = User::where('group_id', $user->group_id)
                ->where('status', 1)
                ->whereNotNull('group_id')
                ->select('id', 'name', 'first_name', 'last_name', 'member_code', 'phone_number')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($m) {
                    return [
                        'id'                => $m->id,
                        'name'              => $m->name,
                        'first_name'        => $m->first_name,
                        'last_name'         => $m->last_name,
                        'member_code'       => $m->member_code ?? '',
                        'phone_number'      => $m->phone_number ?? '',
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
                    'project_name' => $project->cycle_name ?? $project->title,
                    'share_value'  => (float) $project->share_value,
                    'members'      => $members,
                    'total'        => $members->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Opening balance getMembers error: ' . $e->getMessage());
            return response()->json(['code' => 0, 'message' => 'Failed to load members: ' . $e->getMessage()], 500);
        }
    }

    // ─── 2. Check status ───────────────────────────────────────────────────────

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
                ->whereIn('status', ['submitted', 'processed'])
                ->with('submittedBy:id,name')
                ->first();

            return response()->json([
                'code'    => 1,
                'message' => $existing
                    ? 'Opening balance already submitted'
                    : 'No opening balance submitted yet',
                'data'    => [
                    'submitted'       => (bool) $existing,
                    'is_processed'    => $existing ? (bool) $existing->is_processed : false,
                    'opening_balance' => $existing ? [
                        'id'              => $existing->id,
                        'status'          => $existing->status,
                        'is_processed'    => (bool) $existing->is_processed,
                        'submission_date' => $existing->submission_date?->toDateTimeString(),
                        'processed_at'    => $existing->processed_at?->toDateTimeString(),
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

    // ─── 3. Submit + Process ───────────────────────────────────────────────────

    /**
     * Validates the payload, saves the opening-balance records, then
     * immediately fans the data out into the live operational tables.
     *
     * A cycle can only have ONE opening-balance submission.  Any subsequent
     * attempt is rejected with HTTP 409 Conflict so data integrity is preserved.
     */
    public function store(Request $request)
    {
        // Decode members JSON string sent by Flutter FormData
        $membersRaw = $request->input('members');
        $members    = is_string($membersRaw)
            ? json_decode($membersRaw, true)
            : $membersRaw;

        if (!is_array($members) || empty($members)) {
            return response()->json([
                'code'    => 0,
                'message' => 'members must be a non-empty JSON array.',
            ], 422);
        }

        $request->merge(['members' => $members]);

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

            $cycleId = (int) $request->input('cycle_id');
            $groupId = (int) $request->input('group_id');

            // ── Deduplication guard ─────────────────────────────────────────────
            $existingSubmitted = VslaOpeningBalance::where('cycle_id', $cycleId)
                ->whereIn('status', ['submitted', 'processed'])
                ->first();

            if ($existingSubmitted) {
                return response()->json([
                    'code'    => 0,
                    'message' => 'Opening balances for this cycle have already been submitted'
                        . ($existingSubmitted->is_processed ? ' and processed.' : '. Please wait for processing to complete.'),
                    'data'    => [
                        'opening_balance_id' => $existingSubmitted->id,
                        'is_processed'       => (bool) $existingSubmitted->is_processed,
                        'submitted_at'       => $existingSubmitted->submission_date?->toDateTimeString(),
                    ],
                ], 409);
            }

            // ── Fetch cycle config (share value, interest rate) ─────────────────
            $cycle = Project::findOrFail($cycleId);
            $shareValue   = (float) ($cycle->share_value ?? 1);
            $interestRate = (float) ($cycle->loan_interest_rate ?? 10);

            DB::beginTransaction();

            // ── Create header record ────────────────────────────────────────────
            $openingBalance = VslaOpeningBalance::create([
                'group_id'        => $groupId,
                'cycle_id'        => $cycleId,
                'submitted_by_id' => $user->id,
                'status'          => 'submitted',
                'submission_date' => now(),
                'notes'           => $request->input('notes'),
                'is_processed'    => false,
            ]);

            // ── Save per-member snapshot ────────────────────────────────────────
            $memberRows = [];
            foreach ($members as $m) {
                $memberRows[] = [
                    'opening_balance_id' => $openingBalance->id,
                    'member_id'          => $m['member_id'],
                    'total_shares'       => $m['total_shares']      ?? 0,
                    'share_count'        => $m['share_count']        ?? 0,
                    'total_loan_amount'  => $m['total_loan_amount']  ?? 0,
                    'loan_balance'       => $m['loan_balance']       ?? 0,
                    'total_social_fund'  => $m['total_social_fund']  ?? 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
            VslaOpeningBalanceMember::insert($memberRows);

            // ── Fan out into operational tables ─────────────────────────────────
            $summary = $this->processOpeningBalance(
                $openingBalance,
                $members,
                $cycle,
                $shareValue,
                $interestRate,
                $user->id
            );

            // Mark as processed
            $openingBalance->update([
                'status'           => 'processed',
                'is_processed'     => true,
                'processed_at'     => now(),
                'processing_notes' => json_encode($summary['log']),
            ]);

            DB::commit();

            return response()->json([
                'code'    => 1,
                'message' => 'Opening balances submitted and processed successfully.',
                'data'    => [
                    'opening_balance_id'    => $openingBalance->id,
                    'members_saved'         => count($memberRows),
                    'shares_created'        => $summary['shares_created'],
                    'loans_created'         => $summary['loans_created'],
                    'social_fund_records'   => $summary['social_fund_records'],
                    'totals'                => $summary['totals'],
                    'member_summaries'      => $summary['member_summaries'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opening balance store error: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return response()->json([
                'code'    => 0,
                'message' => 'Failed to submit: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── 4. Retrieve ───────────────────────────────────────────────────────────

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
            return response()->json([
                'code'    => 0,
                'message' => 'Failed to retrieve: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─── 5. Reprocess stuck / skipped ──────────────────────────────────────────

    /**
     * Re-runs fan-out processing for any opening-balance records that were
     * submitted but never fully processed (e.g. due to a prior error, or
     * because the user skipped and re-submitted later).
     *
     * POST vsla/opening-balance/reprocess
     * Body: { "cycle_id": 123 }
     */
    public function reprocess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cycle_id' => 'required|integer|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 0, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['code' => 0, 'message' => 'Unauthorized'], 401);
            }

            $cycleId = (int) $request->input('cycle_id');

            $ob = VslaOpeningBalance::where('cycle_id', $cycleId)
                ->whereIn('status', ['submitted', 'processed'])
                ->with('memberEntries')
                ->first();

            if (!$ob) {
                return response()->json(['code' => 0, 'message' => 'No opening balance found for this cycle.'], 404);
            }

            // Build members array from stored snapshot
            $members = $ob->memberEntries->map(fn($e) => [
                'member_id'          => $e->member_id,
                'total_shares'       => (float) $e->total_shares,
                'share_count'        => (float) $e->share_count,
                'total_loan_amount'  => (float) $e->total_loan_amount,
                'loan_balance'       => (float) $e->loan_balance,
                'total_social_fund'  => (float) $e->total_social_fund,
            ])->toArray();

            $cycle        = Project::findOrFail($cycleId);
            $shareValue   = (float) ($cycle->share_value ?? 1);
            $interestRate = (float) ($cycle->loan_interest_rate ?? 10);

            DB::beginTransaction();

            // Remove any previously created opening-balance account_transactions
            // to avoid duplicates before re-fanning.
            AccountTransaction::where('group_id', $ob->group_id)
                ->where('cycle_id', $cycleId)
                ->where('source', 'opening_balance')
                ->delete();

            $summary = $this->processOpeningBalance(
                $ob,
                $members,
                $cycle,
                $shareValue,
                $interestRate,
                $user->id
            );

            $ob->update([
                'status'           => 'processed',
                'is_processed'     => true,
                'processed_at'     => now(),
                'processing_notes' => json_encode($summary['log']),
            ]);

            DB::commit();

            return response()->json([
                'code'    => 1,
                'message' => 'Opening balances reprocessed successfully.',
                'data'    => [
                    'opening_balance_id'  => $ob->id,
                    'shares_created'      => $summary['shares_created'],
                    'loans_created'       => $summary['loans_created'],
                    'social_fund_records' => $summary['social_fund_records'],
                    'totals'              => $summary['totals'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Opening balance reprocess error: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return response()->json(['code' => 0, 'message' => 'Reprocess failed: ' . $e->getMessage()], 500);
        }
    }

    // ─── Processing engine ─────────────────────────────────────────────────────

    /**
     * Fan opening-balance figures out into live operational tables AND
     * account_transactions (double-entry pairs), so all dashboard screens
     * reflect opening balances correctly.
     *
     * Shares:      project_shares  +  account_transactions (share, both +)
     * Loans:       vsla_loans + loan_transactions + account_transactions (loan, both -)
     *              + account_transactions (loan_repayment, both +) if any already paid
     * Social fund: social_fund_transactions + account_transactions (social_fund, member - / group +)
     */
    private function processOpeningBalance(
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
            $memberId        = (int) $m['member_id'];
            $totalShares     = (float) ($m['total_shares']      ?? 0);
            $shareCount      = (float) ($m['share_count']       ?? 0);
            $totalLoanAmount = (float) ($m['total_loan_amount'] ?? 0);
            $loanBalance     = (float) ($m['loan_balance']      ?? 0);
            $totalSocialFund = (float) ($m['total_social_fund'] ?? 0);

            $memberName    = $m['name'] ?? "Member #{$memberId}";
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

                    // account_transactions: share purchase (group leg primary, member leg contra)
                    $groupShareTx = AccountTransaction::create([
                        'user_id'          => $submittedById,
                        'owner_type'       => 'group',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'share',
                        'source'           => 'opening_balance',
                        'amount'           => $totalShares,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – {$memberName} opening shares ({$numShares} @ {$shareValue})",
                        'is_contra_entry'  => false,
                        'created_by_id'    => $submittedById,
                    ]);
                    $memberShareTx = AccountTransaction::create([
                        'user_id'          => $memberId,
                        'owner_type'       => 'member',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'share',
                        'source'           => 'opening_balance',
                        'amount'           => $totalShares,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – {$memberName} purchased {$numShares} shares",
                        'is_contra_entry'  => true,
                        'contra_entry_id'  => $groupShareTx->id,
                        'created_by_id'    => $submittedById,
                    ]);
                    $groupShareTx->update(['contra_entry_id' => $memberShareTx->id]);

                    $sharesCreated++;
                    $shareCreated = true;
                    $log[] = "Share: member {$memberId} => {$numShares} shares @ {$shareValue} = {$totalShares}";
                }
            }

            // ── B. Loan ───────────────────────────────────────────────────────
            if ($totalLoanAmount > 0) {
                $amountPaid = max(0.0, $totalLoanAmount - $loanBalance);
                $loanStatus = ($loanBalance > 0) ? 'active' : 'paid';

                $durationMonths = 3;
                $dueDate = (clone \Carbon\Carbon::parse($submissionDate))->addMonths($durationMonths);

                $loan = VslaLoan::create([
                    'cycle_id'          => $cycle->id,
                    'meeting_id'        => null,
                    'borrower_id'       => $memberId,
                    'loan_amount'       => $totalLoanAmount,
                    'interest_rate'     => $interestRate,
                    'duration_months'   => $durationMonths,
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

                // account_transactions: loan disbursement (both negative – group pays out, member owes)
                $groupLoanTx = AccountTransaction::create([
                    'user_id'                 => $submittedById,
                    'owner_type'              => 'group',
                    'group_id'                => $groupId,
                    'meeting_id'              => null,
                    'cycle_id'                => $cycle->id,
                    'account_type'            => 'loan',
                    'source'                  => 'opening_balance',
                    'amount'                  => -$totalLoanAmount,
                    'transaction_date'        => $submissionDate,
                    'description'             => "Opening balance – loan carry-over disbursed to {$memberName}",
                    'related_disbursement_id' => $loan->id,
                    'is_contra_entry'         => false,
                    'created_by_id'           => $submittedById,
                ]);
                $memberLoanTx = AccountTransaction::create([
                    'user_id'                 => $memberId,
                    'owner_type'              => 'member',
                    'group_id'                => $groupId,
                    'meeting_id'              => null,
                    'cycle_id'                => $cycle->id,
                    'account_type'            => 'loan',
                    'source'                  => 'opening_balance',
                    'amount'                  => -$totalLoanAmount,
                    'transaction_date'        => $submissionDate,
                    'description'             => "Opening balance – {$memberName} carry-over loan of {$totalLoanAmount}",
                    'related_disbursement_id' => $loan->id,
                    'is_contra_entry'         => true,
                    'contra_entry_id'         => $groupLoanTx->id,
                    'created_by_id'           => $submittedById,
                ]);
                $groupLoanTx->update(['contra_entry_id' => $memberLoanTx->id]);

                // account_transactions: prior repayment (both positive – cash back in, debt reduced)
                if ($amountPaid > 0) {
                    $groupRepayTx = AccountTransaction::create([
                        'user_id'          => $submittedById,
                        'owner_type'       => 'group',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'loan_repayment',
                        'source'           => 'opening_balance',
                        'amount'           => $amountPaid,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – prior repayment from {$memberName}",
                        'is_contra_entry'  => true,
                        'created_by_id'    => $submittedById,
                    ]);
                    $memberRepayTx = AccountTransaction::create([
                        'user_id'          => $memberId,
                        'owner_type'       => 'member',
                        'group_id'         => $groupId,
                        'meeting_id'       => null,
                        'cycle_id'         => $cycle->id,
                        'account_type'     => 'loan_repayment',
                        'source'           => 'opening_balance',
                        'amount'           => $amountPaid,
                        'transaction_date' => $submissionDate,
                        'description'      => "Opening balance – {$memberName} prior loan repayment",
                        'is_contra_entry'  => true,
                        'contra_entry_id'  => $groupRepayTx->id,
                        'created_by_id'    => $submittedById,
                    ]);
                    $groupRepayTx->update(['contra_entry_id' => $memberRepayTx->id]);
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

                // account_transactions: social fund contribution (member - / group +)
                $memberSfTx = AccountTransaction::create([
                    'user_id'          => $memberId,
                    'owner_type'       => 'member',
                    'group_id'         => $groupId,
                    'meeting_id'       => null,
                    'cycle_id'         => $cycle->id,
                    'account_type'     => 'social_fund',
                    'source'           => 'opening_balance',
                    'amount'           => -$totalSocialFund,
                    'transaction_date' => $submissionDate,
                    'description'      => "Opening balance – {$memberName} social fund contribution",
                    'is_contra_entry'  => false,
                    'created_by_id'    => $submittedById,
                ]);
                $groupSfTx = AccountTransaction::create([
                    'user_id'          => $submittedById,
                    'owner_type'       => 'group',
                    'group_id'         => $groupId,
                    'meeting_id'       => null,
                    'cycle_id'         => $cycle->id,
                    'account_type'     => 'social_fund',
                    'source'           => 'opening_balance',
                    'amount'           => $totalSocialFund,
                    'transaction_date' => $submissionDate,
                    'description'      => "Opening balance – group received social fund from {$memberName}",
                    'is_contra_entry'  => true,
                    'contra_entry_id'  => $memberSfTx->id,
                    'created_by_id'    => $submittedById,
                ]);
                $memberSfTx->update(['contra_entry_id' => $groupSfTx->id]);

                $socialFundRecords++;
                $socialCreated = true;
                $log[] = "SocialFund: member {$memberId} => {$totalSocialFund}";
            }

            $totalSharesAmt    += $totalShares;
            $totalLoanAmt      += $totalLoanAmount;
            $totalLoanBalance  += $loanBalance;
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
            'member_summaries'    => $memberSummaries,
            'log'                 => $log,
        ];
    }
}
