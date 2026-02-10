<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateExistingSessionsToPivot extends Migration
{
    /**
     * Run the migrations.
     * Migrates existing group_id values from ffs_training_sessions to the new pivot table.
     *
     * @return void
     */
    public function up()
    {
        // Copy all existing session->group relationships to the pivot table
        DB::statement("
            INSERT INTO ffs_session_target_groups (session_id, group_id, created_at, updated_at)
            SELECT id, group_id, created_at, updated_at
            FROM ffs_training_sessions
            WHERE group_id IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM ffs_session_target_groups 
                WHERE session_id = ffs_training_sessions.id 
                AND group_id = ffs_training_sessions.group_id
            )
        ");
    }

    /**
     * Reverse the migrations.
     * Cannot cleanly reverse without data loss, so we'll just log a warning.
     *
     * @return void
     */
    public function down()
    {
        // Reversing this would mean deleting pivot data, which we don't want to do automatically
        // You would need to manually handle this if you truly need to rollback
        \Log::warning('Migration rollback: migrate_existing_sessions_to_pivot cannot be automatically reversed. Pivot table data remains intact.');
    }
}
