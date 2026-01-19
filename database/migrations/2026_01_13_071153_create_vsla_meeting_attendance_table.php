<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVslaMeetingAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vsla_meeting_attendance', function (Blueprint $table) {
            $table->id();
            $table->string('local_id')->nullable()->unique()->comment('UUID from mobile app');
            
            // Foreign Keys
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedInteger('member_id');
            
            // Attendance Details
            $table->boolean('is_present')->default(true);
            $table->string('absent_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('meeting_id');
            $table->index('member_id');
            $table->index('is_present');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vsla_meeting_attendance');
    }
}
