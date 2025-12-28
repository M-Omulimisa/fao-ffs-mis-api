<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMeetingIdToProjectSharesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('project_shares') && Schema::hasTable('vsla_meetings')) {
            Schema::table('project_shares', function (Blueprint $table) {
                if (!Schema::hasColumn('project_shares', 'meeting_id')) {
                    $table->foreignId('meeting_id')->nullable()->after('project_id')->constrained('vsla_meetings')->onDelete('set null');
                    $table->index('meeting_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_shares', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
            $table->dropColumn('meeting_id');
        });
    }
}
