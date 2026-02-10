<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPendingStatusToParticipants extends Migration
{
    /**
     * Run the migrations.
     * Change default attendance_status from 'present' to 'pending'.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ffs_session_participants', function (Blueprint $table) {
            // Change default value
            $table->string('attendance_status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ffs_session_participants', function (Blueprint $table) {
            // Revert to old default
            $table->string('attendance_status')->default('present')->change();
        });
    }
}
