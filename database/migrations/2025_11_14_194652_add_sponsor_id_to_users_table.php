<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSponsorIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('users', 'sponsor_id')) {
                $table->string('sponsor_id')->nullable()->after('business_name')->index();
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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sponsor_id')) {
                $table->dropColumn('sponsor_id');
            }
        });
    }
}
