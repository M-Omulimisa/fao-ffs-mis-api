<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsTrainingSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('ffs_training_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('facilitator_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('topic')->nullable();
            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('session_type')->default('classroom'); // classroom, field, demonstration, workshop
            $table->string('status')->default('scheduled'); // scheduled, ongoing, completed, cancelled
            $table->integer('expected_participants')->default(0);
            $table->integer('actual_participants')->default(0);
            $table->text('materials_used')->nullable();
            $table->text('notes')->nullable();
            $table->text('challenges')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('group_id');
            $table->index('facilitator_id');
            $table->index('session_date');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ffs_training_sessions');
    }
}
