<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsSessionTargetGroupsTable extends Migration
{
    /**
     * Run the migrations.
     * Creates pivot table for many-to-many relationship between training sessions and groups.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ffs_session_target_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('group_id');
            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index('group_id');
            
            // Unique constraint: a group can only be added once per session
            $table->unique(['session_id', 'group_id'], 'session_group_unique');

            // Foreign keys (commented out if FK constraints not enforced in your setup)
            // $table->foreign('session_id')->references('id')->on('ffs_training_sessions')->onDelete('cascade');
            // $table->foreign('group_id')->references('id')->on('ffs_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ffs_session_target_groups');
    }
}
