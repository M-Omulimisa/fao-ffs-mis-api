<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegisteredByIdToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('users', 'registered_by_id')) {
                $table->bigInteger('registered_by_id')->nullable()->after('user_type');
            }
            
            // is_dtehm_member already exists from 2025_11_14 migration - skip it
            
            if (!Schema::hasColumn('users', 'dtehm_membership_paid_at')) {
                $table->timestamp('dtehm_membership_paid_at')->nullable()->after('is_dtehm_member');
            }
            
            if (!Schema::hasColumn('users', 'dtehm_membership_amount')) {
                $table->decimal('dtehm_membership_amount', 10, 2)->nullable()->after('dtehm_membership_paid_at');
            }
            
            if (!Schema::hasColumn('users', 'dtehm_membership_payment_id')) {
                $table->bigInteger('dtehm_membership_payment_id')->nullable()->after('dtehm_membership_amount');
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
            // Only drop columns that we added in this migration (not is_dtehm_member - that's from old migration)
            if (Schema::hasColumn('users', 'registered_by_id')) {
                $table->dropColumn('registered_by_id');
            }
            
            if (Schema::hasColumn('users', 'dtehm_membership_paid_at')) {
                $table->dropColumn('dtehm_membership_paid_at');
            }
            
            if (Schema::hasColumn('users', 'dtehm_membership_amount')) {
                $table->dropColumn('dtehm_membership_amount');
            }
            
            if (Schema::hasColumn('users', 'dtehm_membership_payment_id')) {
                $table->dropColumn('dtehm_membership_payment_id');
            }
        });
    }
}
