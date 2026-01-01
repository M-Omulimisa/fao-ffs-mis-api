<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVslaLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->unsignedBigInteger('borrower_id');
            $table->decimal('loan_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->integer('duration_months');
            $table->decimal('total_amount_due', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2);
            $table->date('disbursement_date');
            $table->date('due_date')->nullable();
            $table->text('purpose')->nullable();
            $table->enum('status', ['active', 'paid', 'defaulted'])->default('active');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes only (no foreign key constraints due to database limitations)
            $table->index('borrower_id');
            $table->index('status');
            $table->index('cycle_id');
            $table->index('created_by_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vsla_loans');
    }
}
