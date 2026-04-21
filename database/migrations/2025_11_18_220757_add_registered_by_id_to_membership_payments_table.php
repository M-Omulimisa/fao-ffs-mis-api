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
        if (!Schema::hasTable('membership_payments')) {
            return;
        }

        Schema::table('membership_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_payments', 'registered_by_id')) {
                $table->foreignId('registered_by_id')->nullable()->after('confirmed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('membership_payments')) {
            return;
        }

        Schema::table('membership_payments', function (Blueprint $table) {
            if (Schema::hasColumn('membership_payments', 'registered_by_id')) {
                $table->dropColumn('registered_by_id');
            }
        });
    }
}
