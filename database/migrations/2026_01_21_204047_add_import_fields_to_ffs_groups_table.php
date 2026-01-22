<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportFieldsToFfsGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ffs_groups', function (Blueprint $table) {
            // New fields from CSV import
            $table->string('loa', 100)->nullable()->after('code')->comment('Letter of Agreement');
            $table->string('ip_name', 100)->nullable()->after('loa')->comment('Implementing Partner Name');
            $table->string('project_code', 100)->nullable()->after('ip_name')->comment('Project Code e.g. UNJP/UGA/068/EC');
            $table->integer('pwd_male_members')->default(0)->after('pwd_members')->comment('PWD Male Members count');
            $table->integer('pwd_female_members')->default(0)->after('pwd_male_members')->comment('PWD Female Members count');
            $table->string('facilitator_sex', 20)->nullable()->after('contact_person_phone')->comment('Facilitator gender');
            $table->string('district_text', 100)->nullable()->after('district_id')->comment('District name text');
            $table->string('source_file', 255)->nullable()->after('photo')->comment('Original import source file');
            $table->integer('original_id')->nullable()->after('source_file')->comment('Original ID from import file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ffs_groups', function (Blueprint $table) {
            $table->dropColumn([
                'loa',
                'ip_name', 
                'project_code',
                'pwd_male_members',
                'pwd_female_members',
                'facilitator_sex',
                'district_text',
                'source_file',
                'original_id'
            ]);
        });
    }
}
