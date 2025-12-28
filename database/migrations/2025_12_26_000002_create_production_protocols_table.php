<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionProtocolsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * ProductionProtocol represents specific activities/tasks that must be
     * performed within an enterprise. Each protocol defines what needs to be
     * done, when it should start and end (in weeks), and whether it's mandatory.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('production_protocols')) {
            Schema::create('production_protocols', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enterprise_id');
            $table->string('activity_name');
            $table->text('activity_description')->nullable();
            $table->integer('start_time')->comment('Start time in weeks from enterprise start');
            $table->integer('end_time')->comment('End time in weeks from enterprise start');
            $table->boolean('is_compulsory')->default(true)->comment('Whether this activity is mandatory or optional');
            $table->string('photo')->nullable();
            $table->integer('order')->default(0)->comment('Display order for activities');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key (no cascade - database doesn't support it)
            $table->foreign('enterprise_id')->references('id')->on('enterprises');

            // Indexes
            $table->index('enterprise_id');
            $table->index('is_active');
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
        Schema::dropIfExists('production_protocols');
    }
}
