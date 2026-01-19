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
        Schema::table('projects', function (Blueprint $table) {
            // Add saving_type column after is_vsla_cycle
            $table->enum('saving_type', ['shares', 'any_amount'])
                ->default('shares')
                ->after('is_vsla_cycle')
                ->comment('Type of savings: shares (fixed amount) or any_amount (flexible)');
        });
        
        // Update existing cycles to have 'shares' as default
        \DB::statement("UPDATE projects SET saving_type = 'shares' WHERE is_vsla_cycle = 'Yes'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('saving_type');
        });
    }
}
