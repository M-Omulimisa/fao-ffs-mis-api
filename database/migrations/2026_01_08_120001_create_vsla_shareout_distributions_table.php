<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create VSLA Shareout Distributions Table
 * 
 * PURPOSE: Store per-member distribution calculations for a shareout
 * 
 * This table holds the detailed breakdown of what each member receives
 * in a shareout. Each record represents one member's distribution.
 * 
 * CALCULATION PER MEMBER:
 * 1. Member's Share Count
 * 2. Member's Share Percentage = (Member Shares / Total Shares) × 100
 * 3. Member's Proportional Distribution = Distributable Fund × Share Percentage
 * 4. Member's Outstanding Loans (to be deducted)
 * 5. Final Payout = Proportional Distribution - Outstanding Loans
 */
class CreateVslaShareoutDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_shareout_distributions', function (Blueprint $table) {
            $table->id();
            
            // References
            $table->unsignedBigInteger('shareout_id')
                ->comment('Parent shareout record');
            $table->unsignedBigInteger('member_id')
                ->comment('Member receiving distribution');
            
            // Member's Contributions
            $table->decimal('member_savings', 15, 2)->default(0)
                ->comment('Total savings contributed by member');
            $table->integer('member_shares')->default(0)
                ->comment('Number of shares owned by member');
            $table->decimal('member_share_value', 15, 2)->default(0)
                ->comment('Total value of member shares purchased');
            $table->decimal('member_fines_paid', 15, 2)->default(0)
                ->comment('Total fines paid by member');
            $table->decimal('member_welfare_contribution', 15, 2)->default(0)
                ->comment('Welfare fund contributions (not distributed)');
            
            // Member's Distribution Calculation
            $table->decimal('share_percentage', 5, 2)->default(0)
                ->comment('Member share percentage (e.g., 12.50 for 12.5%)');
            $table->decimal('proportional_distribution', 15, 2)->default(0)
                ->comment('Amount based on share percentage');
            $table->decimal('loan_interest_share', 15, 2)->default(0)
                ->comment('Share of total loan interest earned');
            $table->decimal('fine_share', 15, 2)->default(0)
                ->comment('Share of total fines collected');
            
            // Member's Deductions
            $table->decimal('outstanding_loan_principal', 15, 2)->default(0)
                ->comment('Principal amount of unpaid loans');
            $table->decimal('outstanding_loan_interest', 15, 2)->default(0)
                ->comment('Interest on unpaid loans');
            $table->decimal('outstanding_loan_total', 15, 2)->default(0)
                ->comment('Total outstanding loan amount');
            
            // Final Calculation
            $table->decimal('total_entitled', 15, 2)->default(0)
                ->comment('Total amount member is entitled to');
            $table->decimal('total_deductions', 15, 2)->default(0)
                ->comment('Total deductions (loans, penalties)');
            $table->decimal('final_payout', 15, 2)->default(0)
                ->comment('Actual amount to be paid to member');
            
            // Payment Status
            $table->enum('payment_status', [
                'pending',      // Not yet paid
                'paid',         // Payment completed
                'deferred',     // Payment deferred (e.g., large outstanding loan)
                'waived'        // Amount waived/forgiven
            ])->default('pending');
            
            $table->timestamp('paid_at')->nullable()
                ->comment('When payment was made');
            $table->unsignedBigInteger('paid_by_id')->nullable()
                ->comment('User who processed the payment');
            $table->string('payment_method', 50)->nullable()
                ->comment('Cash, mobile money, bank transfer, etc.');
            $table->string('payment_reference', 100)->nullable()
                ->comment('Transaction reference/receipt number');
            
            // Notes
            $table->text('notes')->nullable()
                ->comment('Additional notes about this distribution');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('shareout_id');
            $table->index('member_id');
            $table->index('payment_status');
            
            // Unique constraint: one distribution per member per shareout
            $table->unique(['shareout_id', 'member_id'], 'unique_member_shareout');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vsla_shareout_distributions');
    }
}
