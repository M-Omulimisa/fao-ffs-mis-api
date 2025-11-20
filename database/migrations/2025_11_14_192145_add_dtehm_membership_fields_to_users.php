<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDtehmMembershipFieldsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // DTEHM Membership Status
            if (!Schema::hasColumn('users', 'is_dtehm_member')) {
                $table->string('is_dtehm_member')->default('No');
            }
            if (!Schema::hasColumn('users', 'is_dip_member')) {
                $table->string('is_dip_member')->default('No');
            }

            // DTEHM Member ID (no index/unique)
            if (!Schema::hasColumn('users', 'dtehm_member_id')) {
                $table->string('dtehm_member_id')->nullable();
            }

            // Membership Dates
            if (!Schema::hasColumn('users', 'dtehm_member_membership_date')) {
                $table->timestamp('dtehm_member_membership_date')->nullable();
            }

            // Payment Information
            if (!Schema::hasColumn('users', 'dtehm_membership_is_paid')) {
                $table->string('dtehm_membership_is_paid')->default('No');
            }
            if (!Schema::hasColumn('users', 'dtehm_membership_paid_date')) {
                $table->timestamp('dtehm_membership_paid_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'dtehm_membership_paid_amount')) {
                $table->decimal('dtehm_membership_paid_amount', 10, 2)->nullable();
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
        Schema::table('users', function (Blueprint $table) {});
    }
}
