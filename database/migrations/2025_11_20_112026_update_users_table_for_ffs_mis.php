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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Add FFS-MIS specific fields
                if (!Schema::hasColumn('users', 'member_code')) {
                    $table->string('member_code', 50)->nullable()->after('id')->unique()->comment('Auto-generated member code');
                }
                if (!Schema::hasColumn('users', 'district_id')) {
                    $table->bigInteger('district_id')->unsigned()->nullable()->after('address');
                }
                if (!Schema::hasColumn('users', 'subcounty_id')) {
                    $table->bigInteger('subcounty_id')->unsigned()->nullable()->after('district_id');
                }
                if (!Schema::hasColumn('users', 'parish_id')) {
                    $table->bigInteger('parish_id')->unsigned()->nullable()->after('subcounty_id');
                }
                if (!Schema::hasColumn('users', 'village')) {
                    $table->string('village', 100)->nullable()->after('parish_id');
                }
                if (!Schema::hasColumn('users', 'education_level')) {
                    $table->string('education_level', 50)->nullable()->after('dob')->comment('Primary, Secondary, Tertiary, None');
                }
                if (!Schema::hasColumn('users', 'marital_status')) {
                    $table->string('marital_status', 20)->nullable()->after('sex')->comment('Single, Married, Divorced, Widowed');
                }
                if (!Schema::hasColumn('users', 'household_size')) {
                    $table->decimal('household_size', 8, 0)->nullable()->after('marital_status')->comment('Number of people in household');
                }
                if (!Schema::hasColumn('users', 'phone_number_2')) {
                    $table->string('phone_number_2', 35)->nullable()->after('phone_number');
                }
                if (!Schema::hasColumn('users', 'emergency_contact_name')) {
                    $table->string('emergency_contact_name', 100)->nullable()->after('phone_number_2');
                }
                if (!Schema::hasColumn('users', 'emergency_contact_phone')) {
                    $table->string('emergency_contact_phone', 35)->nullable()->after('emergency_contact_name');
                }
                if (!Schema::hasColumn('users', 'disabilities')) {
                    $table->text('disabilities')->nullable()->comment('Any disabilities or special needs');
                }
                if (!Schema::hasColumn('users', 'skills')) {
                    $table->text('skills')->nullable()->comment('Farming or business skills');
                }
                if (!Schema::hasColumn('users', 'remarks')) {
                    $table->text('remarks')->nullable()->comment('Additional notes');
                }
                if (!Schema::hasColumn('users', 'created_by_id')) {
                    $table->bigInteger('created_by_id')->unsigned()->nullable()->after('registered_by_id');
                }
                
                // Add indexes
                $table->index('district_id');
                $table->index('member_code');
            });
        }
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
