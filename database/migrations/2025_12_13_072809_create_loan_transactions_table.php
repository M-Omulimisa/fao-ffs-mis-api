<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * LoanTransaction tracks every event in a loan's lifecycle:
     * - Loan disbursement: -principal amount (debt created)
     * - Interest charge: -interest amount (additional debt)
     * - Loan payment: +payment amount (debt reduced)
     * - Penalty: -penalty amount (debt increased)
     * - Waiver: +waiver amount (debt reduced)
     * 
     * Loan balance = SUM(all loan_transactions.amount)
     * Negative balance = member owes money
     * Zero balance = loan fully paid
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('loan_transactions')) {
            Schema::create('loan_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id')->index();
            $table->decimal('amount', 15, 2)->comment('Positive for payments/waivers, Negative for principal/interest/penalties');
            $table->date('transaction_date')->index();
            $table->text('description')->nullable();
            $table->enum('type', [
                'principal',        // Initial loan amount (negative)
                'interest',         // Interest charge (negative)
                'payment',          // Loan repayment (positive)
                'penalty',          // Late payment penalty (negative)
                'waiver',           // Debt forgiveness (positive)
                'adjustment'        // Manual adjustment (positive or negative)
            ])->index();
            $table->unsignedBigInteger('created_by_id');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys - only add if referenced table exists
            if (Schema::hasTable('vsla_loans')) {
                $table->foreign('loan_id')->references('id')->on('vsla_loans')->onDelete('cascade');
            }
            // Note: created_by_id may not exist in users table, so we'll skip the foreign key constraint
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_transactions');
    }
}
