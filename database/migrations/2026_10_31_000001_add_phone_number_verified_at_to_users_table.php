<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * profiling/users filters and counts on phone_number_verified_at and the
 * "verify" action writes it, but the column was never created (every call 500'd).
 */
class AddPhoneNumberVerifiedAtToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone_number_verified_at')) {
                $table->timestamp('phone_number_verified_at')->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone_number_verified_at')) {
                $table->dropColumn('phone_number_verified_at');
            }
        });
    }
}
