<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportDetailsToFfsTrainingSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ffs_training_sessions', function (Blueprint $table) {
            // GPS coordinates for session location
            $table->decimal('gps_latitude', 10, 8)->nullable()->after('photo');
            $table->decimal('gps_longitude', 11, 8)->nullable()->after('gps_latitude');
            
            // Multiple photos array (JSON)
            $table->json('photos')->nullable()->after('gps_longitude');
            
            // Attending facilitators (JSON array of user IDs)
            $table->json('attending_facilitator_ids')->nullable()->after('photos');
            
            // Report submitted timestamp
            $table->timestamp('report_submitted_at')->nullable()->after('attending_facilitator_ids');
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
            $table->dropColumn([
                'gps_latitude',
                'gps_longitude', 
                'photos',
                'attending_facilitator_ids',
                'report_submitted_at',
            ]);
        });
    }
}
