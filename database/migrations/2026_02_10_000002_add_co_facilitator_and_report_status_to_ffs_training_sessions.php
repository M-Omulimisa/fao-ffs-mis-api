<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoFacilitatorAndReportStatusToFfsTrainingSessions extends Migration
{
    /**
     * Run the migrations.
     * Adds co-facilitator support and report submission tracking.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ffs_training_sessions', function (Blueprint $table) {
            // Co-facilitator
            $table->unsignedBigInteger('co_facilitator_id')->nullable()->after('facilitator_id');
            $table->index('co_facilitator_id');
            
            // Report submission workflow
            $table->string('report_status')->default('draft')->after('status'); // draft, submitted
            $table->datetime('submitted_at')->nullable()->after('report_status');
            $table->unsignedBigInteger('submitted_by_id')->nullable()->after('submitted_at');
            
            $table->index('report_status');
            
            // Foreign keys (commented out if FK constraints not enforced)
            // $table->foreign('co_facilitator_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('submitted_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ffs_training_sessions', function (Blueprint $table) {
            $table->dropIndex(['co_facilitator_id']);
            $table->dropIndex(['report_status']);
            $table->dropColumn(['co_facilitator_id', 'report_status', 'submitted_at', 'submitted_by_id']);
        });
    }
}
