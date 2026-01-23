<?php

namespace App\Services;

use App\Models\VslaMeeting;
use App\Models\VslaMeetingAttendance;
use App\Models\VslaActionPlan;
use App\Models\ProjectTransaction;
use App\Models\ProjectShare;
use App\Models\AccountTransaction;
use App\Models\LoanTransaction;
use App\Models\VslaLoan;
use App\Models\SocialFundTransaction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;

/**
 * Meeting Processing Service
 * 
 * Core business logic for processing VSLA meetings:
 * 1. Validates meeting data
 * 2. Extracts and creates attendance records
 * 3. Creates double-entry transactions for savings/welfare/social fund/fines
 * 4. Creates share purchase records and transactions
 * 5. Creates loan disbursement transactions
 * 6. Processes action plans (updates previous, creates new)
 * 7. Tracks errors and warnings
 */
class MeetingProcessingService
{
    /**
     * Process a VSLA meeting
     * 
     * @param VslaMeeting $meeting
     * @return array ['success' => bool, 'errors' => array, 'warnings' => array]
     */
    public function processMeeting(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            // Mark as processing
            $meeting->markAsProcessing();

            // Note: Transaction is managed by the controller, not here

            // Step 1: Validate meeting
            $validationResult = $this->validateMeeting($meeting);
            if (!$validationResult['valid']) {
                $errors = array_merge($errors, $validationResult['errors']);
                $warnings = array_merge($warnings, $validationResult['warnings']);
                
                // If critical errors, stop processing
                if (!empty($validationResult['errors'])) {
                    return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
                }
            }

            // Step 2: Process attendance
            $attendanceResult = $this->processAttendance($meeting);
            if (!$attendanceResult['success']) {
                $errors = array_merge($errors, $attendanceResult['errors']);
            }
            $warnings = array_merge($warnings, $attendanceResult['warnings']);

            // Step 3: Process transactions (savings, welfare, social fund, fines)
            $transactionsResult = $this->processTransactions($meeting);
            if (!$transactionsResult['success']) {
                $errors = array_merge($errors, $transactionsResult['errors']);
            }
            $warnings = array_merge($warnings, $transactionsResult['warnings']);

            // Step 4: Process share purchases
            $sharesResult = $this->processSharePurchases($meeting);
            if (!$sharesResult['success']) {
                $errors = array_merge($errors, $sharesResult['errors']);
            }
            $warnings = array_merge($warnings, $sharesResult['warnings']);

            // Step 5: Process loan repayments
            $loanRepaymentsResult = $this->processLoanRepayments($meeting);
            if (!$loanRepaymentsResult['success']) {
                $errors = array_merge($errors, $loanRepaymentsResult['errors']);
            }
            $warnings = array_merge($warnings, $loanRepaymentsResult['warnings']);

            // Step 5.5: Process social fund contributions
            $socialFundResult = $this->processSocialFundContributions($meeting);
            if (!$socialFundResult['success']) {
                $errors = array_merge($errors, $socialFundResult['errors']);
            }
            $warnings = array_merge($warnings, $socialFundResult['warnings']);

            // Step 6: Process loan disbursements
            $loansResult = $this->processLoans($meeting);
            if (!$loansResult['success']) {
                $errors = array_merge($errors, $loansResult['errors']);
            }
            $warnings = array_merge($warnings, $loansResult['warnings']);

            // Step 7: Process action plans
            $actionPlansResult = $this->processActionPlans($meeting);
            if (!$actionPlansResult['success']) {
                $errors = array_merge($errors, $actionPlansResult['errors']);
            }
            $warnings = array_merge($warnings, $actionPlansResult['warnings']);

            // If any critical errors occurred, return error status
            // The controller will handle the rollback
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
            }

            // Mark as completed or needs review (controller will commit)
            if (!empty($warnings)) {
                // Mark as completed even with warnings (processing_status enum doesn't have 'needs_review')
                $meeting->markAsCompletedWithWarnings($warnings);
            } else {
                $meeting->markAsCompleted();
            }

            return ['success' => true, 'errors' => [], 'warnings' => $warnings];

        } catch (Exception $e) {
            DB::rollBack();
            // Let the exception bubble up to the controller to handle rollback
            $error = [
                'type' => 'exception',
                'message' => 'Processing failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            
            Log::error('Meeting processing failed', [
                'meeting_id' => $meeting->id,
                'error' => $error
            ]);

            // Don't try to update meeting - let controller handle it
            return ['success' => false, 'errors' => [$error], 'warnings' => $warnings];
        }
    }

    /**
     * Validate meeting data
     */
    protected function validateMeeting(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        // Check for duplicate local_id
        $duplicate = VslaMeeting::where('local_id', $meeting->local_id)
            ->where('id', '!=', $meeting->id)
            ->where('processing_status', 'completed')
            ->first();
            
        if ($duplicate) {
            $errors[] = [
                'type' => 'duplicate',
                'message' => "Meeting already processed (ID: {$duplicate->id})",
                'field' => 'local_id'
            ];
        }

        // Validate cycle exists and is VSLA
        $cycle = Project::find($meeting->cycle_id);
        if (!$cycle) {
            $errors[] = [
                'type' => 'missing_cycle',
                'message' => 'VSLA cycle not found',
                'field' => 'cycle_id'
            ];
        } elseif (!$cycle->is_vsla_cycle) {
            $warnings[] = [
                'type' => 'not_vsla_cycle',
                'message' => 'Project is not marked as VSLA cycle',
                'suggestion' => 'Verify this is a VSLA savings cycle'
            ];
        }

        // Validate attendance data
        if (empty($meeting->attendance_data)) {
            $warnings[] = [
                'type' => 'no_attendance',
                'message' => 'No attendance data provided',
                'suggestion' => 'Verify attendance was recorded'
            ];
        }

        // Validate financial totals match
        $expectedSavings = $this->calculateExpectedTotal($meeting->transactions_data, 'savings');
        if (abs($expectedSavings - $meeting->total_savings_collected) > 0.01) {
            $warnings[] = [
                'type' => 'savings_mismatch',
                'message' => "Savings total mismatch: Expected {$expectedSavings}, got {$meeting->total_savings_collected}",
                'suggestion' => 'Verify savings calculations'
            ];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Process attendance data
     */
    protected function processAttendance(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            $attendanceData = $meeting->attendance_data ?? [];
            
            // Get all group members
            $groupId = $meeting->group_id;
            $allGroupMembers = User::where('group_id', $groupId)->get();
            
            if ($allGroupMembers->isEmpty()) {
                $warnings[] = [
                    'type' => 'no_group_members',
                    'message' => 'No members found for this group',
                    'suggestion' => 'Ensure group has registered members'
                ];
            }
            
            // Track members marked as present in attendance_data
            $markedMemberIds = collect($attendanceData)->pluck('memberId')->filter()->toArray();
            
            // Process attendance records from mobile app
            foreach ($attendanceData as $record) {
                // Validate member exists
                $member = User::find($record['memberId'] ?? null);
                if (!$member) {
                    $warnings[] = [
                        'type' => 'member_not_found',
                        'message' => "Member not found: {$record['memberName']}",
                        'suggestion' => 'Member may need to be added to system'
                    ];
                    continue;
                }

                // Convert string boolean to actual boolean
                $isPresent = $record['isPresent'] ?? false;
                if (is_string($isPresent)) {
                    $isPresent = strtolower($isPresent) === 'true' || $isPresent === '1';
                } else {
                    $isPresent = filter_var($isPresent, FILTER_VALIDATE_BOOLEAN);
                }

                // Create or update attendance record
                VslaMeetingAttendance::updateOrCreate(
                    [
                        'meeting_id' => $meeting->id,
                        'member_id' => $record['memberId']
                    ],
                    [
                        'is_present' => $isPresent ? 1 : 0,
                        'absent_reason' => $record['absentReason'] ?? null
                    ]
                );
            }
            
            // Mark all members NOT in attendance_data as absent
            foreach ($allGroupMembers as $member) {
                if (!in_array($member->id, $markedMemberIds)) {
                    VslaMeetingAttendance::updateOrCreate(
                        [
                            'meeting_id' => $meeting->id,
                            'member_id' => $member->id
                        ],
                        [
                            'is_present' => false,
                            'absent_reason' => 'Not recorded in meeting attendance'
                        ]
                    );
                }
            }

            return ['success' => true, 'errors' => $errors, 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'attendance_processing_error',
                'message' => 'Failed to process attendance: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Process transactions (savings, welfare, social fund, fines)
     */
    protected function processTransactions(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            $transactionsData = $meeting->transactions_data ?? [];
            
            foreach ($transactionsData as $transaction) {
                $accountType = $transaction['accountType'] ?? null;
                $amount = $transaction['amount'] ?? 0;
                $memberId = $transaction['memberId'] ?? null;
                $description = $transaction['description'] ?? '';

                // Skip if no amount
                if ($amount <= 0) {
                    continue;
                }

                // Validate member
                $member = User::find($memberId);
                if (!$member) {
                    $warnings[] = [
                        'type' => 'member_not_found',
                        'message' => "Member not found for transaction: {$transaction['memberName']}",
                        'suggestion' => 'Transaction recorded but member needs verification'
                    ];
                    continue;
                }

                // Create double-entry transaction
                $result = $this->createDoubleEntryTransaction(
                    $meeting,
                    $member,
                    $accountType,
                    $amount,
                    $description
                );

                if (!$result['success']) {
                    $errors = array_merge($errors, $result['errors']);
                }
            }

            return ['success' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'transaction_processing_error',
                'message' => 'Failed to process transactions: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Create double-entry transaction for meeting contributions
     * 
     * Creates AccountTransaction records (not ProjectTransaction):
     * - Member debit: Member pays money (negative amount)
     * - Group credit: Group receives money (positive amount)
     * 
     * Properly tracks:
     * - owner_type: 'member' for member transactions, 'group' for group entries
     * - group_id, meeting_id, cycle_id for comprehensive filtering
     * - account_type: from offline accountType field
     * - contra_entry_id: links double-entry pairs
     */
    protected function createDoubleEntryTransaction(
        VslaMeeting $meeting,
        User $member,
        string $accountType,
        float $amount,
        string $description
    ): array {
        $errors = [];

        try {
            // Map account type to transaction source (for backward compatibility)
            // Valid source values: 'deposit', 'withdrawal', 'disbursement'
            // Use 'deposit' for all member contributions to the group
            $source = 'deposit';

            // 1. MEMBER DEBIT: Member pays money (negative amount)
            $memberTransaction = AccountTransaction::create([
                'user_id' => $member->id,
                'owner_type' => 'member',
                'group_id' => $meeting->group_id,
                'meeting_id' => $meeting->id,
                'cycle_id' => $meeting->cycle_id,
                'account_type' => $accountType,
                'source' => $source,
                'amount' => -$amount, // Negative for member payment
                'description' => $description ?: "Meeting #{$meeting->meeting_number} - {$accountType}",
                'transaction_date' => $meeting->meeting_date,
                'is_contra_entry' => false,
                'created_by_id' => $meeting->created_by_id
            ]);

            // 2. GROUP CREDIT: Group receives money (positive amount)
            $groupTransaction = AccountTransaction::create([
                'user_id' => $meeting->created_by_id, // Use creator's ID for group transactions
                'owner_type' => 'group',
                'group_id' => $meeting->group_id,
                'meeting_id' => $meeting->id,
                'cycle_id' => $meeting->cycle_id,
                'account_type' => $accountType,
                'source' => $source,
                'amount' => $amount, // Positive for group receipt
                'description' => "Group receipt from {$member->name} - {$accountType}",
                'transaction_date' => $meeting->meeting_date,
                'is_contra_entry' => true,
                'contra_entry_id' => $memberTransaction->id,
                'created_by_id' => $meeting->created_by_id
            ]);

            // Link member transaction to group transaction
            $memberTransaction->update([
                'contra_entry_id' => $groupTransaction->id
            ]);

            return ['success' => true, 'errors' => []];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'transaction_creation_error',
                'message' => "Failed to create transaction for {$member->name}: " . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors];
        }
    }

    /**
     * Process share purchases
     */
    /**
     * Process share purchases from meeting
     * 
     * Uses DOUBLE-ENTRY accounting:
     * - Each share purchase creates 2 AccountTransaction records
     * - Group record (user_id=NULL): +amount (credit - money received)
     * - Member record (user_id=member_id): +amount (member's contribution)
     * 
     * Also creates ProjectShare record for ownership tracking
     */
    protected function processSharePurchases(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            $sharesData = $meeting->share_purchases_data ?? [];
            
            foreach ($sharesData as $purchase) {
                // Mobile app sends: investor_id, investor_name, number_of_shares, total_amount_paid, share_price_at_purchase
                // OLD format: memberId, memberName, numberOfShares, totalAmountPaid, sharePriceAtPurchase
                // Support both formats for compatibility
                $memberId = $purchase['investor_id'] ?? $purchase['memberId'] ?? null;
                $numberOfShares = $purchase['number_of_shares'] ?? $purchase['numberOfShares'] ?? 0;
                $totalAmount = $purchase['total_amount_paid'] ?? $purchase['totalAmountPaid'] ?? 0;
                $sharePriceAtPurchase = $purchase['share_price_at_purchase'] ?? $purchase['sharePriceAtPurchase'] ?? ($numberOfShares > 0 ? $totalAmount / $numberOfShares : 0);

                if ($numberOfShares <= 0 || $totalAmount <= 0) {
                    continue;
                }

                $member = User::find($memberId);
                if (!$member) {
                    $memberName = $purchase['investor_name'] ?? $purchase['memberName'] ?? "ID: {$memberId}";
                    $warnings[] = [
                        'type' => 'member_not_found',
                        'message' => "Member not found for share purchase: {$memberName}",
                        'suggestion' => 'Share purchase needs verification'
                    ];
                    continue;
                }

                $memberName = $member->name;

                // Create ProjectShare record for ownership tracking
                $projectShare = ProjectShare::create([
                    'project_id' => $meeting->cycle_id,
                    'investor_id' => $member->id,
                    'number_of_shares' => $numberOfShares,
                    'share_price_at_purchase' => $sharePriceAtPurchase,
                    'total_amount_paid' => $totalAmount,
                    'purchase_date' => $meeting->meeting_date,
                ]);

                // DOUBLE-ENTRY ACCOUNTING:
                // Transaction 1: Group receives money (credit to group)
                $groupTransaction = AccountTransaction::create([
                    'user_id' => $meeting->created_by_id, // Use creator's ID for group transactions
                    'owner_type' => 'group',
                    'group_id' => $meeting->group_id,
                    'meeting_id' => $meeting->id,
                    'cycle_id' => $meeting->cycle_id,
                    'account_type' => 'share',
                    'source' => 'deposit',
                    'amount' => $totalAmount, // Positive = credit (money in)
                    'transaction_date' => $meeting->meeting_date,
                    'description' => "Group received share payment from {$memberName}",
                    'is_contra_entry' => false,
                    'created_by_id' => $meeting->created_by_id,
                ]);

                // Transaction 2: Member contributes money
                $memberTransaction = AccountTransaction::create([
                    'user_id' => $member->id, // Member transaction
                    'owner_type' => 'member',
                    'group_id' => $meeting->group_id,
                    'meeting_id' => $meeting->id,
                    'cycle_id' => $meeting->cycle_id,
                    'account_type' => 'share',
                    'source' => 'deposit',
                    'amount' => $totalAmount, // Positive = member's contribution
                    'transaction_date' => $meeting->meeting_date,
                    'description' => "{$memberName} purchased {$numberOfShares} shares @ UGX " . number_format($sharePriceAtPurchase, 2),
                    'is_contra_entry' => true,
                    'contra_entry_id' => $groupTransaction->id,
                    'created_by_id' => $meeting->created_by_id,
                ]);

                // Link group transaction to member transaction
                $groupTransaction->update([
                    'contra_entry_id' => $memberTransaction->id
                ]);
            }

            return ['success' => true, 'errors' => $errors, 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'share_processing_error',
                'message' => 'Failed to process shares: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Process loan repayments from meeting
     * 
     * Mobile app sends: loan_id, amount, payment_method, payment_date, notes
     * Updates: VslaLoan (amount_paid, balance, status), creates LoanTransaction, creates AccountTransaction
     */
    protected function processLoanRepayments(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            $repaymentsData = $meeting->loan_repayments_data ?? [];
            
            foreach ($repaymentsData as $repayment) {
                $loanId = $repayment['loan_id'] ?? null;
                $amount = $repayment['amount'] ?? 0;
                $paymentMethod = $repayment['payment_method'] ?? 'cash';
                $paymentDate = $repayment['payment_date'] ?? $meeting->meeting_date;
                $notes = $repayment['notes'] ?? null;

                if (!$loanId) {
                    $warnings[] = [
                        'type' => 'missing_loan_id',
                        'message' => 'Loan repayment missing loan_id',
                        'suggestion' => 'Skipping this repayment entry'
                    ];
                    continue;
                }

                if ($amount <= 0) {
                    $warnings[] = [
                        'type' => 'invalid_repayment_amount',
                        'message' => "Repayment amount must be greater than 0 for loan ID: {$loanId}",
                        'suggestion' => 'Skipping this repayment entry'
                    ];
                    continue;
                }

                // Find the loan
                $loan = VslaLoan::find($loanId);
                if (!$loan) {
                    $warnings[] = [
                        'type' => 'loan_not_found',
                        'message' => "Loan not found: ID {$loanId}",
                        'suggestion' => 'Loan repayment needs verification'
                    ];
                    continue;
                }

                // Validate payment amount doesn't exceed balance
                if ($amount > $loan->balance) {
                    $warnings[] = [
                        'type' => 'overpayment',
                        'message' => "Repayment amount (UGX " . number_format($amount, 2) . ") exceeds loan balance (UGX " . number_format($loan->balance, 2) . ") for {$loan->borrower_name}",
                        'suggestion' => 'Adjusting payment to loan balance'
                    ];
                    $amount = $loan->balance;
                }

                // Process the repayment
                $result = $this->createLoanRepayment($meeting, $loan, $amount, $paymentMethod, $paymentDate, $notes);
                
                if (!$result['success']) {
                    $errors = array_merge($errors, $result['errors']);
                } else if (!empty($result['warnings'])) {
                    $warnings = array_merge($warnings, $result['warnings']);
                }
            }

            return ['success' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'loan_repayment_processing_error',
                'message' => 'Failed to process loan repayments: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Create loan repayment with transaction tracking
     * 
     * Updates VslaLoan:
     * - Increases amount_paid
     * - Decreases balance
     * - Updates status to 'paid' if balance reaches 0
     * 
     * Creates LoanTransaction:
     * - Records repayment with payment method
     * - Links to loan
     * 
     * Creates AccountTransactions (2 - double entry):
     * - Group: +amount (cash in)
     * - Member: +amount (debt reduced)
     */
    protected function createLoanRepayment(
        VslaMeeting $meeting,
        VslaLoan $loan,
        float $amount,
        string $paymentMethod,
        string $paymentDate,
        ?string $notes
    ): array {
        $errors = [];
        $warnings = [];

        try {
            // Calculate principal and interest portions
            // For simplicity, we'll apply payment to reduce balance directly
            // More sophisticated systems would track principal vs interest separately
            $balanceBefore = $loan->balance;
            $balanceAfter = max(0, $balanceBefore - $amount);

            // Update loan
            $loan->amount_paid += $amount;
            $loan->balance = $balanceAfter;
            
            // Update status if fully paid
            if ($loan->balance <= 0) {
                $loan->status = 'paid';
            }
            
            $loan->save();

            // Create LoanTransaction record
            LoanTransaction::create([
                'loan_id' => $loan->id,
                'cycle_id' => $meeting->cycle_id,
                'meeting_id' => $meeting->id,
                'borrower_id' => $loan->borrower_id,
                'transaction_type' => 'repayment',
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_date' => $paymentDate,
                'description' => $notes ?? "Repayment of UGX " . number_format($amount, 2) . " via {$paymentMethod}",
                'created_by_id' => $meeting->created_by_id,
            ]);

            // Create AccountTransactions (double-entry)
            // Transaction 1: Group receives cash (+amount)
            $groupTransaction = AccountTransaction::create([
                'user_id' => $meeting->created_by_id, // Use creator's ID for group transactions
                'owner_type' => 'group',
                'group_id' => $meeting->group_id,
                'meeting_id' => $meeting->id,
                'cycle_id' => $meeting->cycle_id,
                'account_type' => 'loan_repayment',
                'source' => 'deposit',
                'amount' => $amount, // Positive = cash in
                'transaction_date' => $paymentDate,
                'description' => "{$loan->borrower_name} repaid loan {$loan->loan_number} via {$paymentMethod}" . ($notes ? " - {$notes}" : ""),
                'is_contra_entry' => true,
                'created_by_id' => $meeting->created_by_id,
            ]);

            // Transaction 2: Member's debt reduced (+amount = less debt)
            $memberTransaction = AccountTransaction::create([
                'user_id' => $loan->borrower_id,
                'owner_type' => 'member',
                'group_id' => $meeting->group_id,
                'meeting_id' => $meeting->id,
                'cycle_id' => $meeting->cycle_id,
                'account_type' => 'loan_repayment',
                'source' => 'withdrawal',
                'amount' => $amount, // Positive = debt reduced
                'transaction_date' => $paymentDate,
                'description' => "Repaid loan {$loan->loan_number} via {$paymentMethod}" . ($notes ? " - {$notes}" : ""),
                'is_contra_entry' => true,
                'contra_entry_id' => $groupTransaction->id,
                'created_by_id' => $meeting->created_by_id,
            ]);

            // Link group transaction to member transaction
            $groupTransaction->update([
                'contra_entry_id' => $memberTransaction->id
            ]);

            return ['success' => true, 'errors' => $errors, 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'loan_repayment_creation_error',
                'message' => 'Failed to create loan repayment: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Process loan disbursements
     */
    /**
     * Process loan disbursements from meeting
     * 
     * Mobile app sends: borrower_id, loan_amount, interest_rate, repayment_period_months, loan_purpose
     * OLD format: borrowerId, loanAmount, interestRate, durationMonths, purpose
     */
    protected function processLoans(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            $loansData = $meeting->loans_data ?? [];
            
            foreach ($loansData as $loan) {
                // Support both mobile app (snake_case) and old (camelCase) formats
                $memberId = $loan['borrower_id'] ?? $loan['borrowerId'] ?? null;
                $loanAmount = $loan['loan_amount'] ?? $loan['loanAmount'] ?? 0;
                $interestRate = $loan['interest_rate'] ?? $loan['interestRate'] ?? 0;
                $durationMonths = $loan['repayment_period_months'] ?? $loan['duration_months'] ?? $loan['durationMonths'] ?? 1;
                $purpose = $loan['loan_purpose'] ?? $loan['loanPurpose'] ?? $loan['purpose'] ?? '';

                if ($loanAmount <= 0) {
                    $warnings[] = [
                        'type' => 'invalid_loan_amount',
                        'message' => "Loan amount must be greater than 0 for member ID: {$memberId}",
                        'suggestion' => 'Skipping this loan entry'
                    ];
                    continue;
                }

                $member = User::find($memberId);
                if (!$member) {
                    $memberName = $loan['borrower_name'] ?? $loan['borrowerName'] ?? "ID: {$memberId}";
                    $warnings[] = [
                        'type' => 'member_not_found',
                        'message' => "Member not found for loan: {$memberName}",
                        'suggestion' => 'Loan needs verification'
                    ];
                    continue;
                }

                // Validate loan parameters
                if ($interestRate < 0 || $interestRate > 100) {
                    $warnings[] = [
                        'type' => 'invalid_interest_rate',
                        'message' => "Invalid interest rate ({$interestRate}%) for member {$member->name}. Using 0%",
                        'suggestion' => 'Review loan terms'
                    ];
                    $interestRate = 0;
                }

                if ($durationMonths < 1) {
                    $warnings[] = [
                        'type' => 'invalid_duration',
                        'message' => "Invalid duration ({$durationMonths} months) for member {$member->name}. Using 1 month",
                        'suggestion' => 'Review loan terms'
                    ];
                    $durationMonths = 1;
                }

                // Create VslaLoan record and double-entry transactions
                $result = $this->createLoanDisbursement($meeting, $member, $loanAmount, $interestRate, $durationMonths, $purpose);
                
                if (!$result['success']) {
                    $errors = array_merge($errors, $result['errors']);
                } else if (!empty($result['warnings'])) {
                    $warnings = array_merge($warnings, $result['warnings']);
                }
            }

            return ['success' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'loan_processing_error',
                'message' => 'Failed to process loans: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Create loan disbursement with full transaction tracking
     * 
     * Creates 3 types of records:
     * 1. VslaLoan - Main loan record
     * 2. LoanTransactions (2) - Principal and Interest tracking for the loan
     * 3. AccountTransactions (2) - Group and Member cash flow (double-entry)
     * 
     * LOAN TRANSACTIONS (specific to this loan):
     * - Principal: -amount (debt created)
     * - Interest: -interest_amount (additional debt)
     * 
     * ACCOUNT TRANSACTIONS (group/member cash flow):
     * - Group: -amount (cash out)
     * - Member: -amount (debt created)
     */
    protected function createLoanDisbursement(
        VslaMeeting $meeting,
        User $member,
        float $amount,
        float $interestRate,
        int $durationMonths,
        string $purpose
    ): array {
        $errors = [];
        $warnings = [];

        try {
            // Check if loan already exists for this member in this meeting
            $existingLoan = VslaLoan::where('meeting_id', $meeting->id)
                ->where('borrower_id', $member->id)
                ->first();

            if ($existingLoan) {
                $warnings[] = [
                    'type' => 'duplicate_loan',
                    'message' => "Loan already exists for {$member->name} in this meeting. Skipping.",
                    'suggestion' => 'Review meeting data for duplicates'
                ];
                return ['success' => true, 'errors' => [], 'warnings' => $warnings];
            }

            // Create VslaLoan record
            $loan = VslaLoan::create([
                'cycle_id' => $meeting->cycle_id,
                'meeting_id' => $meeting->id,
                'borrower_id' => $member->id,
                'loan_amount' => $amount,
                'interest_rate' => $interestRate,
                'duration_months' => $durationMonths,
                'purpose' => $purpose,
                'disbursement_date' => $meeting->meeting_date,
                'status' => 'active',
                'created_by_id' => $meeting->created_by_id,
                // Auto-calculated fields handled by model boot method:
                // - total_amount_due (principal + interest)
                // - balance
                // - due_date
            ]);

            // Calculate interest amount
            $interestAmount = ($amount * $interestRate / 100);
            $totalDue = $amount + $interestAmount;

            // LOAN TRANSACTIONS (for this specific loan):
            // LoanTransaction 1: Principal (negative - debt created)
            LoanTransaction::create([
                'loan_id' => $loan->id,
                'amount' => -$amount,
                'transaction_date' => $meeting->meeting_date,
                'description' => "Loan principal disbursed to {$member->name}",
                'type' => LoanTransaction::TYPE_PRINCIPAL,
                'created_by_id' => $meeting->created_by_id,
            ]);

            // LoanTransaction 2: Interest (negative - additional debt)
            if ($interestAmount > 0) {
                LoanTransaction::create([
                    'loan_id' => $loan->id,
                    'amount' => -$interestAmount,
                    'transaction_date' => $meeting->meeting_date,
                    'description' => "Interest charge @ {$interestRate}% for {$durationMonths} months",
                    'type' => LoanTransaction::TYPE_INTEREST,
                    'created_by_id' => $meeting->created_by_id,
                ]);
            }

            // ACCOUNT TRANSACTIONS (group/member cash flow - double-entry):
            // AccountTransaction 1: Group loses money (debit to group)
            $groupTransaction = AccountTransaction::create([
                'user_id' => $meeting->created_by_id, // Use creator's ID for group transactions
                'owner_type' => 'group',
                'group_id' => $meeting->group_id,
                'meeting_id' => $meeting->id,
                'cycle_id' => $meeting->cycle_id,
                'account_type' => 'loan',
                'source' => 'disbursement',
                'amount' => -$amount, // Negative = debit (money out)
                'transaction_date' => $meeting->meeting_date,
                'description' => "Group disbursed loan to {$member->name}" . 
                    ($purpose ? " ({$purpose})" : ""),
                'related_disbursement_id' => $loan->id,
                'is_contra_entry' => false,
                'created_by_id' => $meeting->created_by_id,
            ]);

            // AccountTransaction 2: Member receives money (creates debt)
            $memberTransaction = AccountTransaction::create([
                'user_id' => $member->id, // Member transaction
                'owner_type' => 'member',
                'group_id' => $meeting->group_id,
                'meeting_id' => $meeting->id,
                'cycle_id' => $meeting->cycle_id,
                'account_type' => 'loan',
                'source' => 'disbursement',
                'amount' => -$amount, // Negative = member owes this money
                'transaction_date' => $meeting->meeting_date,
                'description' => "{$member->name} received loan of UGX " . 
                    number_format($amount, 2) . " @ {$interestRate}% for {$durationMonths} months",
                'related_disbursement_id' => $loan->id,
                'is_contra_entry' => true,
                'contra_entry_id' => $groupTransaction->id,
                'created_by_id' => $meeting->created_by_id,
            ]);

            // Link group transaction to member transaction
            $groupTransaction->update([
                'contra_entry_id' => $memberTransaction->id
            ]);

            Log::info("Loan created for meeting #{$meeting->meeting_number}", [
                'loan_id' => $loan->id,
                'borrower' => $member->name,
                'principal' => $amount,
                'interest' => $interestAmount,
                'total_due' => $totalDue,
                'interest_rate' => $interestRate,
                'duration' => $durationMonths
            ]);

            return ['success' => true, 'errors' => [], 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'loan_creation_error',
                'message' => "Failed to create loan for {$member->name}: " . $e->getMessage()
            ];
            Log::error("Loan creation failed", [
                'meeting_id' => $meeting->id,
                'borrower_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Process action plans (update previous, create new)
     */
    protected function processActionPlans(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            // Process previous action plans (status updates)
            $previousPlans = $meeting->previous_action_plans_data ?? [];
            
            foreach ($previousPlans as $planData) {
                // Support multiple field name formats
                $localId = $planData['planId'] ?? $planData['action_plan_id'] ?? $planData['local_id'] ?? null;
                
                if (!$localId) {
                    continue;
                }

                $plan = VslaActionPlan::where('local_id', $localId)->first();
                
                if (!$plan) {
                    $warnings[] = [
                        'type' => 'plan_not_found',
                        'message' => "Previous action plan not found: " . ($planData['action'] ?? $planData['description'] ?? 'Unknown'),
                        'suggestion' => 'Plan may have been created offline'
                    ];
                    continue;
                }

                // Update status based on completion - support multiple field formats
                $status = $planData['completionStatus'] ?? $planData['completion_status'] ?? $planData['status'] ?? $plan->status;
                $notes = $planData['completionNotes'] ?? $planData['completion_notes'] ?? null;
                
                // Update the plan
                if ($status === 'completed') {
                    $plan->complete($notes);
                } else {
                    $plan->update([
                        'status' => $status,
                        'completion_notes' => $notes
                    ]);
                }
            }

            // Create new action plans
            $upcomingPlans = $meeting->upcoming_action_plans_data ?? [];
            
            foreach ($upcomingPlans as $planData) {
                try {
                    // Support both mobile app format and old format
                    // Mobile: responsible_member_id, description (as action), notes (as description), due_date
                    // Old: assignedToMemberId, action, description, dueDate
                    $action = $planData['action'] ?? $planData['description'] ?? '';
                    $description = $planData['description'] ?? $planData['notes'] ?? null;
                    $assignedTo = $planData['assignedToMemberId'] ?? $planData['assigned_to_member_id'] ?? $planData['responsible_member_id'] ?? null;
                    $dueDate = $planData['dueDate'] ?? $planData['due_date'] ?? null;
                    $localId = $planData['planId'] ?? $planData['local_id'] ?? null;
                    
                    VslaActionPlan::create([
                        'local_id' => $localId,
                        'meeting_id' => $meeting->id,
                        'cycle_id' => $meeting->cycle_id,
                        'action' => $action,
                        'description' => $description,
                        'assigned_to_member_id' => $assignedTo,
                        'priority' => $planData['priority'] ?? 'medium',
                        'due_date' => $dueDate,
                        'status' => 'pending',
                        'created_by_id' => $meeting->created_by_id
                    ]);
                } catch (\Exception $planError) {
                    // If individual plan creation fails, log warning but continue
                    $warnings[] = [
                        'type' => 'action_plan_creation_failed',
                        'message' => 'Failed to create action plan: ' . $planError->getMessage(),
                        'suggestion' => 'This action plan was skipped'
                    ];
                    continue;
                }
            }

            return ['success' => true, 'errors' => $errors, 'warnings' => $warnings];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Model not found - shouldn't happen but handle gracefully
            $warnings[] = [
                'type' => 'action_plans_unavailable',
                'message' => 'Action plans feature not yet available',
                'suggestion' => 'Model or table not found: ' . $e->getMessage()
            ];
            return ['success' => true, 'errors' => [], 'warnings' => $warnings];
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if this is a table-not-found error (SQL error code 42S02)
            if ($e->getCode() === '42S02' || 
                strpos($e->getMessage(), "Base table or view not found") !== false || 
                strpos($e->getMessage(), "doesn't exist") !== false) {
                // Non-critical: Action plans feature not available yet
                $warnings[] = [
                    'type' => 'action_plans_unavailable',
                    'message' => 'Action plans feature not yet available',
                    'suggestion' => 'Action plans table will be created when this feature is set up'
                ];
                return ['success' => true, 'errors' => [], 'warnings' => $warnings];
            }
            
            // Other database errors
            $errors[] = [
                'type' => 'action_plan_database_error',
                'message' => 'Database error processing action plans: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
            
        } catch (Exception $e) {
            // Other general errors
            $errors[] = [
                'type' => 'action_plan_processing_error',
                'message' => 'Failed to process action plans: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Process social fund contributions from offline meeting
     * 
     * Creates SocialFundTransaction records for each contributor
     * Similar to attendance - members either contributed or didn't
     */
    protected function processSocialFundContributions(VslaMeeting $meeting): array
    {
        $errors = [];
        $warnings = [];

        try {
            $contributionsData = $meeting->social_fund_contributions_data ?? [];
            
            // If no contributions data, skip
            if (empty($contributionsData)) {
                return ['success' => true, 'errors' => [], 'warnings' => []];
            }

            foreach ($contributionsData as $contribution) {
                $memberId = $contribution['member_id'] ?? null;
                $amount = $contribution['amount'] ?? 0;
                $contributed = $contribution['contributed'] ?? false;

                // Skip if member didn't contribute or amount is 0
                if (!$contributed || $amount <= 0) {
                    continue;
                }

                // Validate member exists
                $member = User::find($memberId);
                if (!$member) {
                    $memberName = $contribution['member_name'] ?? "ID: {$memberId}";
                    $warnings[] = [
                        'type' => 'member_not_found',
                        'message' => "Member not found for social fund contribution: {$memberName}",
                        'suggestion' => 'Contribution needs verification'
                    ];
                    continue;
                }

                // Create social fund transaction
                SocialFundTransaction::create([
                    'group_id' => $meeting->group_id,
                    'cycle_id' => $meeting->cycle_id,
                    'member_id' => $member->id,
                    'meeting_id' => $meeting->id,
                    'transaction_type' => 'contribution',
                    'amount' => $amount,
                    'transaction_date' => $meeting->meeting_date,
                    'description' => "Social fund contribution from {$member->name} at Meeting #{$meeting->meeting_number}",
                    'reason' => $contribution['notes'] ?? null,
                    'created_by_id' => $meeting->created_by_id,
                ]);
            }

            return ['success' => true, 'errors' => [], 'warnings' => $warnings];

        } catch (Exception $e) {
            $errors[] = [
                'type' => 'social_fund_processing_error',
                'message' => 'Failed to process social fund contributions: ' . $e->getMessage()
            ];
            return ['success' => false, 'errors' => $errors, 'warnings' => $warnings];
        }
    }

    /**
     * Calculate expected total for a transaction type
     */
    protected function calculateExpectedTotal(?array $transactions, string $type): float
    {
        if (empty($transactions)) {
            return 0;
        }

        $total = 0;
        foreach ($transactions as $transaction) {
            if (($transaction['accountType'] ?? '') === $type) {
                $total += $transaction['amount'] ?? 0;
            }
        }

        return $total;
    }
}
