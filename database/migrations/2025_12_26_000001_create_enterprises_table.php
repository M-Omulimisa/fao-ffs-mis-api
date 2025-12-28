<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnterprisesTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enterprise represents a farming venture blueprint that farmers can start.
     * It can be either livestock-based or crop-based and provides a structured
     * plan with production protocols (activities) for successful operation.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('enterprises')) {
            Schema::create('enterprises', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['livestock', 'crop'])->comment('Type of enterprise: livestock or crop based');
            $table->integer('duration')->comment('Duration in months');
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better query performance
            $table->index('type');
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
        Schema::dropIfExists('enterprises');
    }
}
