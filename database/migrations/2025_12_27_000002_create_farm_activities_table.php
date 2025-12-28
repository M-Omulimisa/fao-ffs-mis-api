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
        if (!Schema::hasTable('farm_activities')) {
            Schema::create('farm_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('farm_id');
            $table->unsignedBigInteger('production_protocol_id')->nullable();
            
            $table->string('activity_name');
            $table->text('activity_description')->nullable();
            
            $table->date('scheduled_date');
            $table->integer('scheduled_week')->default(1);
            $table->date('actual_completion_date')->nullable();
            
            $table->enum('status', ['pending', 'done', 'skipped', 'overdue'])->default('pending');
            $table->boolean('is_mandatory')->default(false);
            $table->integer('weight')->default(1); // 1-5
            
            $table->decimal('target_value', 10, 2)->nullable();
            $table->decimal('actual_value', 10, 2)->nullable();
            $table->decimal('score', 6, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->string('photo')->nullable();
            
            $table->timestamps();
            
            // Foreign keys - only add if referenced tables exist
            if (Schema::hasTable('farms')) {
                $table->foreign('farm_id')->references('id')->on('farms');
            }
            if (Schema::hasTable('production_protocols')) {
                $table->foreign('production_protocol_id')->references('id')->on('production_protocols');
            }
            
            // Indexes for better performance
            $table->index('farm_id');
            $table->index('production_protocol_id');
            $table->index('scheduled_date');
            $table->index('status');
            $table->index(['farm_id', 'scheduled_date']);
            $table->index(['farm_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_activities');
    }
};
