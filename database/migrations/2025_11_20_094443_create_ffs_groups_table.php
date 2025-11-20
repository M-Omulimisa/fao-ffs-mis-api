<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ffs_groups', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name');
            $table->enum('type', ['FFS', 'FBS', 'VSLA', 'Association'])->default('FFS');
            $table->string('code')->unique()->nullable();
            $table->date('registration_date')->nullable();
            
            // Location (no foreign key constraints)
            $table->bigInteger('district_id')->unsigned()->nullable();
            $table->bigInteger('subcounty_id')->unsigned()->nullable();
            $table->bigInteger('parish_id')->unsigned()->nullable();
            $table->string('village')->nullable();
            
            // Meeting Details
            $table->string('meeting_venue')->nullable();
            $table->string('meeting_day')->nullable(); // Monday, Tuesday, etc
            $table->enum('meeting_frequency', ['Weekly', 'Bi-weekly', 'Monthly'])->default('Weekly');
            
            // Value Chains
            $table->string('primary_value_chain')->nullable(); // Maize, Beans, Sorghum, etc
            $table->text('secondary_value_chains')->nullable(); // JSON array of additional value chains
            
            // Member Statistics
            $table->integer('total_members')->default(0);
            $table->integer('male_members')->default(0);
            $table->integer('female_members')->default(0);
            $table->integer('youth_members')->default(0); // Youth (18-35 years)
            $table->integer('pwd_members')->default(0); // Persons with Disabilities
            
            // Facilitation (no foreign key constraint)
            $table->bigInteger('facilitator_id')->unsigned()->nullable();
            
            // Contact Information
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_phone')->nullable();
            
            // GPS Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status
            $table->enum('status', ['Active', 'Inactive', 'Suspended', 'Graduated'])->default('Active');
            
            // Cycle Information (for VSLA and FFS)
            $table->integer('cycle_number')->default(1);
            $table->date('cycle_start_date')->nullable();
            $table->date('cycle_end_date')->nullable();
            
            // Additional Information
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('achievements')->nullable();
            $table->text('challenges')->nullable();
            $table->string('photo')->nullable();
            
            // Audit (no foreign key constraint)
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index('district_id');
            $table->index('facilitator_id');
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ffs_groups');
    }
}
