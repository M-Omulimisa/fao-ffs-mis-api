<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSavingTypeToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds saving_type column to projects table:
     * - 'shares': Members buy shares at a fixed price (share_value required)
     * - 'any_amount': Members save any amount (share_value not required)
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'saving_type')) {
                $table->enum('saving_type', ['shares', 'any_amount'])
                    ->default('shares')
                    ->comment('Type of savings: shares (fixed amount) or any_amount (flexible)');
            }
        });
        
        // Update existing cycles to have 'shares' as default
        if (Schema::hasColumn('projects', 'saving_type') && Schema::hasColumn('projects', 'is_vsla_cycle')) {
            \DB::statement("UPDATE projects SET saving_type = 'shares' WHERE is_vsla_cycle = 'Yes'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'saving_type')) {
                $table->dropColumn('saving_type');
            }
        });
    }
}
