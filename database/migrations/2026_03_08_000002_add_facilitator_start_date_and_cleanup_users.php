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
            $table->date('facilitator_start_date')->nullable()->after('reg_date');

            // Drop 5 unused legacy vendor columns
            $table->dropColumn([
                'business_license_issue_authority',
                'business_license_issue_date',
                'business_license_validity',
                'business_cover_photo',
                'business_cover_details',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('facilitator_start_date');

            // Restore dropped columns
            $table->text('business_license_issue_authority')->nullable();
            $table->text('business_license_issue_date')->nullable();
            $table->text('business_license_validity')->nullable();
            $table->text('business_cover_photo')->nullable();
            $table->text('business_cover_details')->nullable();
        });
    }
};
