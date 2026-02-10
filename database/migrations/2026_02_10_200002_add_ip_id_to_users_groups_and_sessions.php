<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add ip_id (nullable) to users, ffs_groups, and ffs_training_sessions.
 * Nullable to ensure a smooth transition — existing records keep working.
 */
class AddIpIdToUsersGroupsAndSessions extends Migration
{
    public function up()
    {
        // ── Users ──────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('ip_id')->nullable()->after('user_type');
            $table->index('ip_id');
        });

        // ── FFS Groups ─────────────────────────────────────
        Schema::table('ffs_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('ip_id')->nullable()->after('id');
            $table->index('ip_id');
        });

        // ── FFS Training Sessions ──────────────────────────
        Schema::table('ffs_training_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('ip_id')->nullable()->after('id');
            $table->index('ip_id');
        });

        // ── VSLA Meetings ──────────────────────────────────
        if (Schema::hasTable('vsla_meetings')) {
            Schema::table('vsla_meetings', function (Blueprint $table) {
                if (!Schema::hasColumn('vsla_meetings', 'ip_id')) {
                    $table->unsignedBigInteger('ip_id')->nullable()->after('id');
                    $table->index('ip_id');
                }
            });
        }

        // ── Advisory Posts ─────────────────────────────────
        if (Schema::hasTable('advisory_posts')) {
            Schema::table('advisory_posts', function (Blueprint $table) {
                if (!Schema::hasColumn('advisory_posts', 'ip_id')) {
                    $table->unsignedBigInteger('ip_id')->nullable()->after('id');
                    $table->index('ip_id');
                }
            });
        }
    }

    public function down()
    {
        $tables = ['users', 'ffs_groups', 'ffs_training_sessions', 'vsla_meetings', 'advisory_posts'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'ip_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['ip_id']);
                    $table->dropColumn('ip_id');
                });
            }
        }
    }
}
