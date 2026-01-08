<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create VSLA Shareouts Table
 * 
 * PURPOSE: Handle cycle closing and member fund distribution
 * 
 * A shareout represents the process of closing a savings cycle and distributing
 * accumulated funds back to members based on their shares, plus any profits earned
 * from loan interest, minus any outstanding loans.
 * 
 * WORKFLOW:
 * 1. Admin initiates shareout for active cycle (status = 'draft')
 * 2. System calculates member distributions (status = 'calculated')
 * 3. Admin reviews and can recalculate if needed
 * 4. Admin approves calculations (status = 'approved')
 * 5. System processes payments and closes cycle (status = 'completed')
 * 
 * CALCULATION FORMULA:
 * - Distributable Fund = Total Savings + Share Purchases + Loan Interest Earned + Fines
 * - Member Share = (Member Shares / Total Shares) × Distributable Fund
 * - Final Payout = Member Share - Outstanding Loans
 */
class CreateVslaShareoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_shareouts', function (Blueprint $table) {
            $table->id();
            
            // Core References
            $table->unsignedBigInteger('cycle_id')
                ->comment('Project ID (cycle) being closed');
            $table->unsignedBigInteger('group_id')
                ->comment('VSLA Group ID');
            
            // Financial Totals (Calculated)
            $table->decimal('total_savings', 15, 2)->default(0)
                ->comment('Total member savings in cycle');
            $table->decimal('total_share_value', 15, 2)->default(0)
                ->comment('Total value of shares purchased');
            $table->decimal('total_loan_interest_earned', 15, 2)->default(0)
                ->comment('Total interest earned from loans');
            $table->decimal('total_fines_collected', 15, 2)->default(0)
                ->comment('Total fines/penalties collected');
            $table->decimal('total_distributable_fund', 15, 2)->default(0)
                ->comment('Total amount available for distribution');
            $table->decimal('total_outstanding_loans', 15, 2)->default(0)
                ->comment('Total loans not yet repaid');
            $table->decimal('total_actual_payout', 15, 2)->default(0)
                ->comment('Actual amount to be paid out (after deducting loans)');
            
            // Metadata
            $table->integer('total_members')->default(0)
                ->comment('Number of members participating');
            $table->integer('total_shares')->default(0)
                ->comment('Total number of shares in cycle');
            $table->decimal('share_unit_value', 15, 2)->default(0)
                ->comment('Original share value/price');
            $table->decimal('final_share_value', 15, 2)->default(0)
                ->comment('Final value per share after profit distribution');
            
            // Workflow Status
            $table->enum('status', [
                'draft',        // Initiated, not yet calculated
                'calculated',   // Calculations completed, pending review
                'approved',     // Admin approved, ready to process
                'processing',   // Currently processing payments
                'completed',    // All distributions done, cycle closed
                'cancelled'     // Shareout cancelled
            ])->default('draft');
            
            // Dates
            $table->date('shareout_date')
                ->comment('Date when shareout was initiated');
            $table->timestamp('calculated_at')->nullable()
                ->comment('When calculations were last done');
            $table->timestamp('approved_at')->nullable()
                ->comment('When admin approved the shareout');
            $table->timestamp('completed_at')->nullable()
                ->comment('When distributions were completed');
            
            // Audit Fields
            $table->text('calculation_notes')->nullable()
                ->comment('Admin notes during calculation review');
            $table->text('admin_notes')->nullable()
                ->comment('General admin notes/comments');
            $table->unsignedBigInteger('initiated_by_id')
                ->comment('User who initiated the shareout');
            $table->unsignedBigInteger('approved_by_id')->nullable()
                ->comment('User who approved the shareout');
            $table->unsignedBigInteger('completed_by_id')->nullable()
                ->comment('User who marked it as completed');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('cycle_id');
            $table->index('group_id');
            $table->index('status');
            $table->index('shareout_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vsla_shareouts');
    }
}
