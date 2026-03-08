<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Add facilitator_start_date to users table.
 * 2. Drop 5 unused columns from users table (legacy vendor/business fields
 *    with zero Flutter usage and only ApiResurceController::become_vendor references).
 * 3. implementing_partners.start_date already exists — no change needed there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add facilitator start date after reg_date
            if (!Schema::hasColumn('users', 'facilitator_start_date')) {
                $table->date('facilitator_start_date')->nullable()->after('reg_date');
            }
        });

        // Drop columns defensively to avoid migration failures on schema drift
        $dropColumns = [
            'business_license_issue_authority',
            'business_license_issue_date',
            'business_license_validity',
            'business_cover_photo',
            'business_cover_details',
        ];

        foreach ($dropColumns as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'facilitator_start_date')) {
                $table->dropColumn('facilitator_start_date');
            }

            if (!Schema::hasColumn('users', 'business_license_issue_authority')) {
                $table->text('business_license_issue_authority')->nullable();
            }
            if (!Schema::hasColumn('users', 'business_license_issue_date')) {
                $table->text('business_license_issue_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'business_license_validity')) {
                $table->text('business_license_validity')->nullable();
            }
            if (!Schema::hasColumn('users', 'business_cover_photo')) {
                $table->text('business_cover_photo')->nullable();
            }
            if (!Schema::hasColumn('users', 'business_cover_details')) {
                $table->text('business_cover_details')->nullable();
            }
        });
    }
};
