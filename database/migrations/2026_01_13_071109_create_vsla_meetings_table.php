<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVslaMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('local_id')->nullable()->unique()->comment('UUID from mobile app');
            
            // Foreign Keys
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('group_id');
            $table->unsignedInteger('created_by_id');
            $table->unsignedInteger('processed_by_id')->nullable();
            
            // Meeting Details
            $table->date('meeting_date');
            $table->integer('meeting_number')->default(1);
            $table->text('notes')->nullable();
            
            // Statistics
            $table->integer('members_present')->default(0);
            $table->integer('members_absent')->default(0);
            $table->decimal('total_savings_collected', 15, 2)->default(0);
            $table->decimal('total_welfare_collected', 15, 2)->default(0);
            $table->decimal('total_social_fund_collected', 15, 2)->default(0);
            $table->decimal('total_fines_collected', 15, 2)->default(0);
            $table->decimal('total_loans_disbursed', 15, 2)->default(0);
            $table->integer('total_shares_sold')->default(0);
            $table->decimal('total_share_value', 15, 2)->default(0);
            
            // JSON Data from App
            $table->json('attendance_data')->nullable();
            $table->json('transactions_data')->nullable();
            $table->json('loans_data')->nullable();
            $table->json('share_purchases_data')->nullable();
            $table->json('previous_action_plans_data')->nullable();
            $table->json('upcoming_action_plans_data')->nullable();
            
            // Processing Status
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->boolean('has_errors')->default(false);
            $table->boolean('has_warnings')->default(false);
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            
            // Timestamps
            $table->timestamp('submitted_from_app_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('group_id');
            $table->index('cycle_id');
            $table->index('meeting_date');
            $table->index('processing_status');
            $table->index('created_by_id');
            $table->index('processed_by_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vsla_meetings');
    }
}
