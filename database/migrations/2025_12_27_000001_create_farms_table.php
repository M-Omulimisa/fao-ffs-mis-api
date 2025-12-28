<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('farms')) {
            Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enterprise_id');
            $table->unsignedBigInteger('user_id');
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['planning', 'active', 'completed', 'abandoned'])->default('planning');
            
            $table->date('start_date');
            $table->date('expected_end_date');
            $table->date('actual_end_date')->nullable();
            
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->string('location_text')->nullable();
            
            $table->string('photo')->nullable();
            
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->integer('completed_activities_count')->default(0);
            $table->integer('total_activities_count')->default(0);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign keys - only add if referenced tables exist
            if (Schema::hasTable('enterprises')) {
                $table->foreign('enterprise_id')->references('id')->on('enterprises');
            }
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users');
            }
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('enterprise_id');
            $table->index('status');
            $table->index('start_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
