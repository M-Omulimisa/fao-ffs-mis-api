<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContractFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('contract_type', ['Full Time', 'Part Time'])->nullable()->after('facilitator_start_date');
            $table->string('position', 100)->nullable()->after('contract_type');
            $table->string('department', 100)->nullable()->after('position');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['contract_type', 'position', 'department']);
        });
    }
}
