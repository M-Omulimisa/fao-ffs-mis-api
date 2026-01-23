<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialFundTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->integer('member_id')->nullable()->comment('Member who contributed or withdrew');
            $table->unsignedBigInteger('meeting_id')->nullable()->comment('If from offline meeting');
            $table->enum('transaction_type', ['contribution', 'withdrawal'])->default('contribution');
            $table->decimal('amount', 15, 2)->comment('Positive for contribution, negative for withdrawal');
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->text('reason')->nullable()->comment('Reason for withdrawal or contribution note');
            $table->integer('created_by_id');
            $table->timestamps();

            // Foreign keys (where possible)
            $table->foreign('group_id')->references('id')->on('ffs_groups')->onDelete('cascade');
            $table->foreign('cycle_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('meeting_id')->references('id')->on('vsla_meetings')->onDelete('set null');

            // Indexes (for user references - can't use foreign key due to type mismatch)
            $table->index('group_id');
            $table->index('cycle_id');
            $table->index('member_id');
            $table->index('created_by_id');
            $table->index('transaction_date');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_fund_transactions');
    }
}
