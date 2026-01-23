<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVslaActionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_action_plans', function (Blueprint $table) {
            $table->id();
            $table->string('local_id')->nullable()->unique();
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->string('action')->nullable();
            $table->text('description');
            $table->unsignedBigInteger('assigned_to_member_id')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'in-progress', 'completed', 'cancelled'])->default('pending');
            $table->text('completion_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['cycle_id', 'status']);
            $table->index(['meeting_id']);
            $table->index(['assigned_to_member_id']);
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vsla_action_plans');
    }
}
