<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVslaOpeningBalancesTable extends Migration
{
    /**
     * Run the migrations.
     * Creates tables to store opening/initial financial balances for VSLA group members
     * at the start of a cycle. Submitted by the group chairperson.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->nullable()->comment('The VSLA group (project) ID');
            $table->unsignedBigInteger('cycle_id')->nullable()->comment('The savings cycle this opening balance belongs to');
            $table->unsignedBigInteger('submitted_by_id')->nullable()->comment('User who submitted the opening balance');
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('submission_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('group_id');
            $table->index('cycle_id');
            $table->index('submitted_by_id');
            $table->index('status');
        });

        Schema::create('vsla_opening_balance_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opening_balance_id');
            $table->unsignedBigInteger('member_id')->comment('User ID of the group member');
            $table->decimal('total_shares', 15, 2)->default(0)->comment('Total share value saved by member');
            $table->decimal('share_count', 15, 2)->default(0)->comment('Number of shares held');
            $table->decimal('total_loan_amount', 15, 2)->default(0)->comment('Total loan amount disbursed to member');
            $table->decimal('loan_balance', 15, 2)->default(0)->comment('Outstanding loan balance member still owes');
            $table->decimal('total_social_fund', 15, 2)->default(0)->comment('Total social fund contributions by member');
            $table->timestamps();

            $table->index('opening_balance_id');
            $table->index('member_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vsla_opening_balance_members');
        Schema::dropIfExists('vsla_opening_balances');
    }
}
