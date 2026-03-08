<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Creates the kpi_benchmarks table — a single-record table holding
 * the global facilitator KPI benchmark targets.
 *
 * Also adds IP-specific KPI target columns to implementing_partners.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Facilitator benchmark (single-record) ─────────────
        Schema::create('kpi_benchmarks', function (Blueprint $table) {
            $table->id();

            // Facilitator-level KPI targets
            $table->unsignedInteger('min_groups_per_facilitator')->default(3)
                  ->comment('Minimum groups a facilitator should manage');
            $table->unsignedInteger('min_trainings_per_week')->default(2)
                  ->comment('Minimum training sessions per facilitator per week');
            $table->unsignedInteger('min_meetings_per_group_per_week')->default(1)
                  ->comment('Minimum VSLA meetings submitted per group per week');
            $table->unsignedInteger('min_members_per_group')->default(30)
                  ->comment('Minimum registered members per group');

            // Additional facilitator KPIs
            $table->unsignedInteger('min_aesa_sessions_per_week')->default(1)
                  ->comment('Minimum AESA sessions per facilitator per week');
            $table->decimal('min_meeting_attendance_pct', 5, 2)->default(75.00)
                  ->comment('Minimum meeting attendance percentage target');

            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
        });

        // Seed the single benchmark record with defaults
        DB::table('kpi_benchmarks')->insert([
            'min_groups_per_facilitator'      => 3,
            'min_trainings_per_week'          => 2,
            'min_meetings_per_group_per_week' => 1,
            'min_members_per_group'           => 30,
            'min_aesa_sessions_per_week'      => 1,
            'min_meeting_attendance_pct'      => 75.00,
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]);

        // ── 2. IP-specific KPI targets on implementing_partners ──
        Schema::table('implementing_partners', function (Blueprint $table) {
            $table->unsignedInteger('kpi_target_facilitators')->default(5)
                  ->after('end_date')
                  ->comment('Target number of facilitators');
            $table->unsignedInteger('kpi_target_groups')->default(15)
                  ->after('kpi_target_facilitators')
                  ->comment('Target number of groups');
            $table->unsignedInteger('kpi_target_trainings_per_week')->default(30)
                  ->after('kpi_target_groups')
                  ->comment('Target trainings per week across all facilitators');
            $table->unsignedInteger('kpi_target_meetings_per_week')->default(15)
                  ->after('kpi_target_trainings_per_week')
                  ->comment('Target group meetings submitted per week');
            $table->unsignedInteger('kpi_target_members')->default(450)
                  ->after('kpi_target_meetings_per_week')
                  ->comment('Target total registered members');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_benchmarks');

        Schema::table('implementing_partners', function (Blueprint $table) {
            $table->dropColumn([
                'kpi_target_facilitators',
                'kpi_target_groups',
                'kpi_target_trainings_per_week',
                'kpi_target_meetings_per_week',
                'kpi_target_members',
            ]);
        });
    }
};
