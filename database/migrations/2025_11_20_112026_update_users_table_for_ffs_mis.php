<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTableForFfsMis extends Migration
{
    /**
     * Run the migrations for FAO FFS-MIS member management
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add FFS-MIS specific fields
            $table->string('member_code', 50)->nullable()->after('id')->unique()->comment('Auto-generated member code');
            $table->bigInteger('district_id')->unsigned()->nullable()->after('address');
            $table->bigInteger('subcounty_id')->unsigned()->nullable()->after('district_id');
            $table->bigInteger('parish_id')->unsigned()->nullable()->after('subcounty_id');
            $table->string('village', 100)->nullable()->after('parish_id');
            $table->string('education_level', 50)->nullable()->after('dob')->comment('Primary, Secondary, Tertiary, None');
            $table->string('marital_status', 20)->nullable()->after('sex')->comment('Single, Married, Divorced, Widowed');
            $table->decimal('household_size', 8, 0)->nullable()->after('marital_status')->comment('Number of people in household');
            $table->string('phone_number_2', 35)->nullable()->after('phone_number');
            $table->string('emergency_contact_name', 100)->nullable()->after('phone_number_2');
            $table->string('emergency_contact_phone', 35)->nullable()->after('emergency_contact_name');
            $table->text('disabilities')->nullable()->comment('Any disabilities or special needs');
            $table->text('skills')->nullable()->comment('Farming or business skills');
            $table->text('remarks')->nullable()->comment('Additional notes');
            $table->bigInteger('created_by_id')->unsigned()->nullable()->after('registered_by_id');
            
            // Add indexes
            $table->index('district_id');
            $table->index('member_code');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['district_id']);
            $table->dropIndex(['member_code']);
            
            $table->dropColumn([
                'member_code',
                'district_id',
                'subcounty_id', 
                'parish_id',
                'village',
                'education_level',
                'marital_status',
                'household_size',
                'phone_number_2',
                'emergency_contact_name',
                'emergency_contact_phone',
                'disabilities',
                'skills',
                'remarks',
                'created_by_id'
            ]);
        });
    }
}
