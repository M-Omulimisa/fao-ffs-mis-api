<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data Integrity Constraints Migration
 *
 * Adds DB-level guards that were previously enforced only in application code:
 *
 *  1. vsla_meetings           — UNIQUE (group_id, meeting_date): prevents duplicate meetings at DB level.
 *  2. vsla_meeting_attendance — UNIQUE (meeting_id, member_id): one attendance row per member per meeting.
 *  3. vsla_meeting_attendance — FK meeting_id → vsla_meetings (CASCADE): attendance auto-cleaned when meeting deleted.
 *  4. vsla_loans              — INDEX meeting_id: speeds up joins; FK meeting_id → vsla_meetings (SET NULL).
 *  5. vsla_action_plans       — FK meeting_id → vsla_meetings (SET NULL).
 *  6. vsla_profiles           — UNIQUE (group_id, cycle_id): one active profile per group per cycle.
 *  7. vsla_shareouts          — FK group_id → ffs_groups (CASCADE).
 *  8. loan_transactions       — FK loan_id → vsla_loans (CASCADE): repayments auto-cleaned when loan deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. vsla_meetings: unique guard on (group_id, meeting_date) ────────────
        // Ensures that no two meetings can exist for the same group on the same date.
        // Note: soft-deleted rows still occupy the slot; if a past meeting was
        // deleted and a new one on the same date is needed, the old row must be
        // permanently removed first (which is correct behaviour — no silent overwrites).
        Schema::table('vsla_meetings', function (Blueprint $table) {
            $table->unique(['group_id', 'meeting_date'], 'vsla_meetings_group_date_unique');
        });

        // ── 2 + 3. vsla_meeting_attendance ───────────────────────────────────────
        Schema::table('vsla_meeting_attendance', function (Blueprint $table) {
            // One attendance record per member per meeting
            $table->unique(['meeting_id', 'member_id'], 'vsla_attendance_meeting_member_unique');

            // FK: cascade delete attendance when the parent meeting is deleted
            $table->foreign('meeting_id', 'vsla_attendance_meeting_id_fk')
                  ->references('id')->on('vsla_meetings')
                  ->onDelete('cascade');
        });

        // ── 4. vsla_loans ─────────────────────────────────────────────────────────
        Schema::table('vsla_loans', function (Blueprint $table) {
            // Index meeting_id so joins on it are fast
            $table->index('meeting_id', 'vsla_loans_meeting_id_index');

            // FK: set meeting_id to NULL when the meeting is deleted (loan survives)
            $table->foreign('meeting_id', 'vsla_loans_meeting_id_fk')
                  ->references('id')->on('vsla_meetings')
                  ->onDelete('set null');
        });

        // ── 5. vsla_action_plans ──────────────────────────────────────────────────
        Schema::table('vsla_action_plans', function (Blueprint $table) {
            // FK: set meeting_id to NULL when the meeting is deleted (action plan survives)
            $table->foreign('meeting_id', 'vsla_action_plans_meeting_id_fk')
                  ->references('id')->on('vsla_meetings')
                  ->onDelete('set null');
        });

        // ── 6. vsla_profiles: unique (group_id, cycle_id) ────────────────────────
        // In MySQL, multiple rows with NULL cycle_id are permitted in a UNIQUE index,
        // so profiles without a cycle assignment are not affected.
        Schema::table('vsla_profiles', function (Blueprint $table) {
            $table->unique(['group_id', 'cycle_id'], 'vsla_profiles_group_cycle_unique');
        });

        // ── 7. vsla_shareouts: FK group_id → ffs_groups ──────────────────────────
        Schema::table('vsla_shareouts', function (Blueprint $table) {
            $table->foreign('group_id', 'vsla_shareouts_group_id_fk')
                  ->references('id')->on('ffs_groups')
                  ->onDelete('cascade');
        });

        // ── 8. loan_transactions: FK loan_id → vsla_loans ────────────────────────
        Schema::table('loan_transactions', function (Blueprint $table) {
            $table->foreign('loan_id', 'loan_transactions_loan_id_fk')
                  ->references('id')->on('vsla_loans')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('vsla_meetings', function (Blueprint $table) {
            $table->dropUnique('vsla_meetings_group_date_unique');
        });

        Schema::table('vsla_meeting_attendance', function (Blueprint $table) {
            $table->dropForeign('vsla_attendance_meeting_id_fk');
            $table->dropUnique('vsla_attendance_meeting_member_unique');
        });

        Schema::table('vsla_loans', function (Blueprint $table) {
            $table->dropForeign('vsla_loans_meeting_id_fk');
            $table->dropIndex('vsla_loans_meeting_id_index');
        });

        Schema::table('vsla_action_plans', function (Blueprint $table) {
            $table->dropForeign('vsla_action_plans_meeting_id_fk');
        });

        Schema::table('vsla_profiles', function (Blueprint $table) {
            $table->dropUnique('vsla_profiles_group_cycle_unique');
        });

        Schema::table('vsla_shareouts', function (Blueprint $table) {
            $table->dropForeign('vsla_shareouts_group_id_fk');
        });

        Schema::table('loan_transactions', function (Blueprint $table) {
            $table->dropForeign('loan_transactions_loan_id_fk');
        });
    }
};
