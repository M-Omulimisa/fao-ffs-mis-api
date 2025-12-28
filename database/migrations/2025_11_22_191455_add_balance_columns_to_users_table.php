<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBalanceColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'balance')) {
                    $table->decimal('balance', 15, 2)->default(0)->after('status')->comment('User account balance');
                }
                if (!Schema::hasColumn('users', 'loan_balance')) {
                    $table->decimal('loan_balance', 15, 2)->default(0)->after('balance')->comment('User loan balance');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['balance', 'loan_balance']);
        });
    }
}
