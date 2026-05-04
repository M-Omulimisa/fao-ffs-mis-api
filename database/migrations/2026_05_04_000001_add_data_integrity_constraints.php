<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data Integrity Constraints Migration
 *
 * Adds DB-level guards that were previously enforced only in application code.
 *
 * vsla_meetings unique guard uses a MySQL GENERATED VIRTUAL column so that
 * soft-deleted rows (deleted_at IS NOT NULL) produce NULL and are excluded
 * from the uniqueness check — MySQL permits multiple NULLs in a UNIQUE index.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. vsla_meetings: deduplicate then add soft-delete-aware unique guard ──
        //
        // Step 1a: Soft-delete all but the most recent active meeting per
        //          (group_id, meeting_date). "Most recent" = highest id.
        DB::statement("
            UPDATE vsla_meetings AS m
            JOIN (
                SELECT group_id, meeting_date, MAX(id) AS keep_id
                FROM vsla_meetings
                WHERE deleted_at IS NULL
                  AND group_id IS NOT NULL
                GROUP BY group_id, meeting_date
                HAVING COUNT(*) > 1
            ) AS dupes ON m.group_id = dupes.group_id
                       AND m.meeting_date = dupes.meeting_date
                       AND m.id <> dupes.keep_id
            SET m.deleted_at = NOW()
            WHERE m.deleted_at IS NULL
        ");

        // Step 1b: Add a GENERATED VIRTUAL column:
        //   active  → '<group_id>_<meeting_date>'  (included in unique check)
        //   deleted → NULL                          (excluded from unique check)
        //
        // Drop first if it already exists — a prior failed migration run may have
        // created the column with DATE_FORMAT() which MariaDB rejects on index
        // creation (error 1901). DATE() is deterministic on all MySQL/MariaDB versions.
        if ($this->indexExists('vsla_meetings', 'vsla_meetings_active_group_date_unique')) {
            DB::statement('ALTER TABLE vsla_meetings DROP INDEX vsla_meetings_active_group_date_unique');
        }
        if ($this->columnExists('vsla_meetings', 'active_group_date_key')) {
            DB::statement('ALTER TABLE vsla_meetings DROP COLUMN active_group_date_key');
        }

        DB::statement("
            ALTER TABLE vsla_meetings
            ADD COLUMN active_group_date_key VARCHAR(60)
                GENERATED ALWAYS AS (
                    IF(deleted_at IS NULL,
                        CONCAT(IFNULL(group_id, '0'), '_', DATE(meeting_date)),
                        NULL)
                ) VIRTUAL
        ");

        DB::statement("
            ALTER TABLE vsla_meetings
            ADD UNIQUE INDEX vsla_meetings_active_group_date_unique (active_group_date_key)
        ");

        // ── 2 + 3. vsla_meeting_attendance ───────────────────────────────────────
        // Remove orphaned attendance rows (no parent meeting) before adding FK.
        DB::statement("
            DELETE FROM vsla_meeting_attendance
            WHERE meeting_id NOT IN (SELECT id FROM vsla_meetings)
        ");
        // Remove orphaned loan rows (meeting_id set but meeting deleted/missing).
        DB::statement("
            UPDATE vsla_loans SET meeting_id = NULL
            WHERE meeting_id IS NOT NULL
              AND meeting_id NOT IN (SELECT id FROM vsla_meetings)
        ");
        // Remove orphaned action_plan rows.
        DB::statement("
            UPDATE vsla_action_plans SET meeting_id = NULL
            WHERE meeting_id IS NOT NULL
              AND meeting_id NOT IN (SELECT id FROM vsla_meetings)
        ");

        if (Schema::hasTable('vsla_meeting_attendance')) {
            Schema::table('vsla_meeting_attendance', function (Blueprint $table) {
                if (!$this->indexExists('vsla_meeting_attendance', 'vsla_attendance_meeting_member_unique')) {
                    $table->unique(['meeting_id', 'member_id'], 'vsla_attendance_meeting_member_unique');
                }
                if (!$this->foreignKeyExists('vsla_meeting_attendance', 'vsla_attendance_meeting_id_fk')) {
                    $table->foreign('meeting_id', 'vsla_attendance_meeting_id_fk')
                          ->references('id')->on('vsla_meetings')
                          ->onDelete('cascade');
                }
            });
        }

        // ── 4. vsla_loans ─────────────────────────────────────────────────────────
        if (Schema::hasTable('vsla_loans')) {
            Schema::table('vsla_loans', function (Blueprint $table) {
                if (!$this->indexExists('vsla_loans', 'vsla_loans_meeting_id_index')) {
                    $table->index('meeting_id', 'vsla_loans_meeting_id_index');
                }
                if (!$this->foreignKeyExists('vsla_loans', 'vsla_loans_meeting_id_fk')) {
                    $table->foreign('meeting_id', 'vsla_loans_meeting_id_fk')
                          ->references('id')->on('vsla_meetings')
                          ->onDelete('set null');
                }
            });
        }

        // ── 5. vsla_action_plans ──────────────────────────────────────────────────
        if (Schema::hasTable('vsla_action_plans')) {
            Schema::table('vsla_action_plans', function (Blueprint $table) {
                if (!$this->foreignKeyExists('vsla_action_plans', 'vsla_action_plans_meeting_id_fk')) {
                    $table->foreign('meeting_id', 'vsla_action_plans_meeting_id_fk')
                          ->references('id')->on('vsla_meetings')
                          ->onDelete('set null');
                }
            });
        }

        // ── 6. vsla_profiles: unique (group_id, cycle_id) ────────────────────────
        if (Schema::hasTable('vsla_profiles')) {
            Schema::table('vsla_profiles', function (Blueprint $table) {
                if (!$this->indexExists('vsla_profiles', 'vsla_profiles_group_cycle_unique')) {
                    $table->unique(['group_id', 'cycle_id'], 'vsla_profiles_group_cycle_unique');
                }
            });
        }

        // ── 7. vsla_shareouts: FK group_id → ffs_groups ──────────────────────────
        if (Schema::hasTable('vsla_shareouts')) {
            Schema::table('vsla_shareouts', function (Blueprint $table) {
                if (!$this->foreignKeyExists('vsla_shareouts', 'vsla_shareouts_group_id_fk')) {
                    $table->foreign('group_id', 'vsla_shareouts_group_id_fk')
                          ->references('id')->on('ffs_groups')
                          ->onDelete('cascade');
                }
            });
        }

        // ── 8. loan_transactions: FK loan_id → vsla_loans ────────────────────────
        if (Schema::hasTable('loan_transactions')) {
            Schema::table('loan_transactions', function (Blueprint $table) {
                if (!$this->foreignKeyExists('loan_transactions', 'loan_transactions_loan_id_fk')) {
                    $table->foreign('loan_id', 'loan_transactions_loan_id_fk')
                          ->references('id')->on('vsla_loans')
                          ->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('vsla_meetings', 'vsla_meetings_active_group_date_unique')) {
            DB::statement('ALTER TABLE vsla_meetings DROP INDEX vsla_meetings_active_group_date_unique');
        }
        if ($this->columnExists('vsla_meetings', 'active_group_date_key')) {
            DB::statement('ALTER TABLE vsla_meetings DROP COLUMN active_group_date_key');
        }
        if ($this->indexExists('vsla_meetings', 'vsla_meetings_group_date_unique')) {
            DB::statement('ALTER TABLE vsla_meetings DROP INDEX vsla_meetings_group_date_unique');
        }

        if (Schema::hasTable('vsla_meeting_attendance')) {
            Schema::table('vsla_meeting_attendance', function (Blueprint $table) {
                if ($this->foreignKeyExists('vsla_meeting_attendance', 'vsla_attendance_meeting_id_fk')) {
                    $table->dropForeign('vsla_attendance_meeting_id_fk');
                }
                if ($this->indexExists('vsla_meeting_attendance', 'vsla_attendance_meeting_member_unique')) {
                    $table->dropUnique('vsla_attendance_meeting_member_unique');
                }
            });
        }

        if (Schema::hasTable('vsla_loans')) {
            Schema::table('vsla_loans', function (Blueprint $table) {
                if ($this->foreignKeyExists('vsla_loans', 'vsla_loans_meeting_id_fk')) {
                    $table->dropForeign('vsla_loans_meeting_id_fk');
                }
                if ($this->indexExists('vsla_loans', 'vsla_loans_meeting_id_index')) {
                    $table->dropIndex('vsla_loans_meeting_id_index');
                }
            });
        }

        if (Schema::hasTable('vsla_action_plans')) {
            Schema::table('vsla_action_plans', function (Blueprint $table) {
                if ($this->foreignKeyExists('vsla_action_plans', 'vsla_action_plans_meeting_id_fk')) {
                    $table->dropForeign('vsla_action_plans_meeting_id_fk');
                }
            });
        }

        if (Schema::hasTable('vsla_profiles')) {
            Schema::table('vsla_profiles', function (Blueprint $table) {
                if ($this->indexExists('vsla_profiles', 'vsla_profiles_group_cycle_unique')) {
                    $table->dropUnique('vsla_profiles_group_cycle_unique');
                }
            });
        }

        if (Schema::hasTable('vsla_shareouts')) {
            Schema::table('vsla_shareouts', function (Blueprint $table) {
                if ($this->foreignKeyExists('vsla_shareouts', 'vsla_shareouts_group_id_fk')) {
                    $table->dropForeign('vsla_shareouts_group_id_fk');
                }
            });
        }

        if (Schema::hasTable('loan_transactions')) {
            Schema::table('loan_transactions', function (Blueprint $table) {
                if ($this->foreignKeyExists('loan_transactions', 'loan_transactions_loan_id_fk')) {
                    $table->dropForeign('loan_transactions_loan_id_fk');
                }
            });
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────────

    private function columnExists(string $table, string $column): bool
    {
        return collect(DB::select("SHOW COLUMNS FROM `{$table}`"))
            ->pluck('Field')
            ->contains($column);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->pluck('Key_name')
            ->contains($indexName);
    }

    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $count = DB::selectOne("
            SELECT COUNT(*) as cnt
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $fkName]);
        return ($count->cnt ?? 0) > 0;
    }
};
