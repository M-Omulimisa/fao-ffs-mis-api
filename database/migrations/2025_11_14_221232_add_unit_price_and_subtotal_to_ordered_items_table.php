<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitPriceAndSubtotalToOrderedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            // Add unit_price column if it doesn't exist
            if (!Schema::hasColumn('ordered_items', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0)->after('amount')->comment('Unit price at time of order');
            }
            
            // Add subtotal column if it doesn't exist
            if (!Schema::hasColumn('ordered_items', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('unit_price')->comment('Quantity * Unit Price');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            if (Schema::hasColumn('ordered_items', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
            
            if (Schema::hasColumn('ordered_items', 'subtotal')) {
                $table->dropColumn('subtotal');
            }
        });
    }
}
