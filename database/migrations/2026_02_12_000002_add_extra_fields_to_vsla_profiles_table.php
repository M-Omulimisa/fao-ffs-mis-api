<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToVslaProfilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('vsla_profiles', function (Blueprint $table) {
            // ── Group extras (from ffs_groups columns) ──
            $table->date('registration_date')->nullable()->after('meeting_day')->comment('Group registration date');
            $table->string('meeting_venue')->nullable()->after('registration_date')->comment('Where the group meets');
            $table->unsignedInteger('estimated_members')->nullable()->after('meeting_venue')->comment('Estimated member count');

            // ── Cycle extras (from projects columns) ──
            $table->string('cycle_name')->nullable()->after('cycle_end_date')->comment('Name of the savings cycle');
            $table->string('saving_type')->nullable()->default('shares')->after('cycle_name')->comment('shares or any_amount');
            $table->string('interest_frequency')->nullable()->default('Monthly')->after('loan_interest_rate')->comment('Weekly or Monthly');
            $table->decimal('minimum_loan_amount', 12, 2)->nullable()->after('interest_frequency')->comment('Min loan in UGX');
            $table->decimal('maximum_loan_multiple', 5, 2)->nullable()->after('minimum_loan_amount')->comment('Max multiple of shares');
            $table->decimal('late_payment_penalty', 5, 2)->nullable()->after('maximum_loan_multiple')->comment('Penalty percentage');

            // ── Chairperson extras (from users columns) ──
            $table->string('chair_email')->nullable()->after('chair_sex')->comment('Chairperson email');
            $table->string('chair_national_id')->nullable()->after('chair_email')->comment('Chairperson NIN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('vsla_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'registration_date',
                'meeting_venue',
                'estimated_members',
                'cycle_name',
                'saving_type',
                'interest_frequency',
                'minimum_loan_amount',
                'maximum_loan_multiple',
                'late_payment_penalty',
                'chair_email',
                'chair_national_id',
            ]);
        });
    }
}
