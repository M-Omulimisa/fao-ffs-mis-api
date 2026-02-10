<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsSessionParticipantsTable extends Migration
{
    public function up()
    {
        Schema::create('ffs_session_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->string('attendance_status')->default('present'); // present, absent, excused, late
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
            $table->unique(['session_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ffs_session_participants');
    }
}
