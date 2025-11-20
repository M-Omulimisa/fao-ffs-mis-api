<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        for ($i = 1; $i <= 10; $i++) {
            $column = 'parent_' . $i;
            if (!Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column) {
                    // 10 Generation Parent Hierarchy
                    // Each stores the user_id of the parent at that level
                    $table->bigInteger($column)->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'parent_1',
                'parent_2',
                'parent_3',
                'parent_4',
                'parent_5',
                'parent_6',
                'parent_7',
                'parent_8',
                'parent_9',
                'parent_10'
            ]);
        });
    }
}
