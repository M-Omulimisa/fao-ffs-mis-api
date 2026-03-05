<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAesaSessionsTable extends Migration
{
    public function up()
    {
        Schema::create('aesa_sessions', function (Blueprint $table) {
            $table->id();

            // Auto-generated unique session identifier
            $table->string('data_sheet_number')->unique();

            // FFS Group reference
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('group_name_other')->nullable(); // If "Other" selected

            // Location fields (linked to locations table or free text)
            $table->unsignedBigInteger('district_id')->nullable();
            $table->string('district_text')->nullable();
            $table->unsignedBigInteger('sub_county_id')->nullable();
            $table->string('sub_county_text')->nullable();
            $table->unsignedBigInteger('village_id')->nullable();
            $table->string('village_text')->nullable();

            // Date and time
            $table->date('observation_date');
            $table->time('observation_time')->nullable();

            // Facilitator
            $table->unsignedBigInteger('facilitator_id')->nullable();
            $table->string('facilitator_name')->nullable(); // If "Other" or free text

            // Mini-Group
            $table->string('mini_group_name')->nullable();

            // Observation location
            $table->string('observation_location')->nullable(); // Farm, Grazing Field, etc.
            $table->string('observation_location_other')->nullable();

            // GPS coordinates (optional, auto-captured)
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();

            // Status
            $table->string('status')->default('draft'); // draft, submitted, reviewed

            // Multi-tenancy
            $table->unsignedBigInteger('ip_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('group_id');
            $table->index('facilitator_id');
            $table->index('ip_id');
            $table->index('created_by_id');
            $table->index('status');
            $table->index('observation_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aesa_sessions');
    }
}
