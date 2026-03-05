<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFacilitatorIdToVslaProfilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('vsla_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('facilitator_id')->nullable()
                ->after('chairperson_id')
                ->comment('FK → users.id — field facilitator (defaults to logged-in admin)');
            $table->index('facilitator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('vsla_profiles', function (Blueprint $table) {
            $table->dropIndex(['facilitator_id']);
            $table->dropColumn('facilitator_id');
        });
    }
}
