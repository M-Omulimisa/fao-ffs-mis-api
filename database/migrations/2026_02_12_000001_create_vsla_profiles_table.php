<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVslaProfilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('vsla_profiles', function (Blueprint $table) {
            $table->id();

            // ── Linkage: generated foreign keys ──
            $table->unsignedBigInteger('group_id')->nullable()->comment('FK → ffs_groups.id (auto-generated)');
            $table->unsignedBigInteger('cycle_id')->nullable()->comment('FK → projects.id (auto-generated cycle)');
            $table->unsignedBigInteger('chairperson_id')->nullable()->comment('FK → users.id (auto-generated chairperson)');
            $table->unsignedBigInteger('ip_id')->nullable()->comment('FK → implementing_partners.id');
            $table->unsignedBigInteger('created_by_id')->nullable()->comment('Admin user who created this profile');

            // ── Section 1: Group Information ──
            $table->string('group_name')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->string('village')->nullable();
            $table->string('meeting_frequency')->nullable()->comment('Weekly, Bi-weekly, Monthly');
            $table->string('meeting_day')->nullable()->comment('Monday–Sunday');

            // ── Section 2: Cycle / Financial Configuration ──
            $table->decimal('share_value', 12, 2)->nullable()->comment('Cost per share in UGX');
            $table->decimal('loan_interest_rate', 5, 2)->nullable()->comment('Interest rate percentage');
            $table->date('cycle_start_date')->nullable();
            $table->date('cycle_end_date')->nullable();

            // ── Section 3: Chairperson Details ──
            $table->string('chair_first_name')->nullable();
            $table->string('chair_last_name')->nullable();
            $table->string('chair_phone')->nullable();
            $table->string('chair_sex')->nullable()->comment('Male or Female');

            // ── Meta ──
            $table->string('status')->default('Active')->comment('Active, Inactive');
            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──
            $table->index('group_id');
            $table->index('cycle_id');
            $table->index('chairperson_id');
            $table->index('ip_id');
            $table->index('district_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('vsla_profiles');
    }
}
