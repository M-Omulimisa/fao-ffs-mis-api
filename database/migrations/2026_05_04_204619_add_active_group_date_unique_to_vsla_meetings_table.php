<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddActiveGroupDateUniqueToVslaMeetingsTable extends Migration
{
    /**
     * Add a MySQL-compatible unique constraint on (group_id, meeting_date) for
     * non-deleted rows only.
     *
     * MySQL does not support partial (filtered) indexes, so we use a GENERATED
     * virtual column that is NULL when the row is soft-deleted and carries the
     * composite value when it is active.  MySQL allows multiple NULLs in a UNIQUE
     * index, so soft-deleted rows never conflict with each other or with live rows.
     *
     * active_group_date_key:
     *   - deleted_at IS NULL  →  '<group_id>_<meeting_date>'  (e.g. "643_2026-04-06")
     *   - deleted_at IS NOT NULL → NULL  (excluded from uniqueness check)
     */
    public function up()
    {
        // Drop any stale column/index from a previous failed run before recreating.
        // This also handles the case where 2026_05_04_000001_add_data_integrity_constraints
        // already added the column on production before this migration ran.
        $indexExists = collect(DB::select("SHOW INDEX FROM `vsla_meetings`"))
            ->pluck('Key_name')->contains('vsla_meetings_active_group_date_unique');

        $columnExists = collect(DB::select("SHOW COLUMNS FROM `vsla_meetings`"))
            ->pluck('Field')->contains('active_group_date_key');

        if ($indexExists) {
            DB::statement('ALTER TABLE vsla_meetings DROP INDEX vsla_meetings_active_group_date_unique');
        }
        if ($columnExists) {
            DB::statement('ALTER TABLE vsla_meetings DROP COLUMN active_group_date_key');
        }

        // Add the virtual generated column.
        // DATE() is used instead of DATE_FORMAT() for MariaDB compatibility (error 1901).
        DB::statement("
            ALTER TABLE vsla_meetings
            ADD COLUMN active_group_date_key VARCHAR(60)
                GENERATED ALWAYS AS (
                    IF(deleted_at IS NULL,
                        CONCAT(IFNULL(group_id, '0'), '_', DATE(meeting_date)),
                        NULL)
                ) VIRTUAL
        ");

        // Add the unique index on the generated column.
        DB::statement("
            ALTER TABLE vsla_meetings
            ADD UNIQUE INDEX vsla_meetings_active_group_date_unique (active_group_date_key)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $indexExists = collect(DB::select("SHOW INDEX FROM `vsla_meetings`"))
            ->pluck('Key_name')->contains('vsla_meetings_active_group_date_unique');

        $columnExists = collect(DB::select("SHOW COLUMNS FROM `vsla_meetings`"))
            ->pluck('Field')->contains('active_group_date_key');

        if ($indexExists) {
            DB::statement('ALTER TABLE vsla_meetings DROP INDEX vsla_meetings_active_group_date_unique');
        }
        if ($columnExists) {
            DB::statement('ALTER TABLE vsla_meetings DROP COLUMN active_group_date_key');
        }
    }
}
