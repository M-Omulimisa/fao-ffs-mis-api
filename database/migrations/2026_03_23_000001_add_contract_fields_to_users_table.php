<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContractFieldsToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'contract_type')) {
                $table->enum('contract_type', ['Full Time', 'Part Time'])->nullable();
            }
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position', 100)->nullable();
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 100)->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['contract_type', 'position', 'department'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
}
