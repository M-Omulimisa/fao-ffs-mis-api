<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegisteredByIdToMembershipPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('membership_payments', function (Blueprint $table) {
            $table->foreignId('registered_by_id')->nullable()->after('confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('membership_payments', function (Blueprint $table) {
            $table->dropColumn('registered_by_id');
        });
    }
}
