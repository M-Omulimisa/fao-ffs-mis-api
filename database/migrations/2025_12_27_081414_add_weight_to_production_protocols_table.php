<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeightToProductionProtocolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('production_protocols')) {
            Schema::table('production_protocols', function (Blueprint $table) {
                if (!Schema::hasColumn('production_protocols', 'weight')) {
                    $table->integer('weight')->default(1)->after('order')->comment('Activity importance weight based on + signs (1-5)');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('production_protocols', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
}
