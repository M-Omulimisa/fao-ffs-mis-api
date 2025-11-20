<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionFieldsToOrderedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordered_items', function (Blueprint $table) {
            // Payment tracking for individual items
            if (!Schema::hasColumn('ordered_items', 'item_is_paid')) {
                $table->string('item_is_paid')->default('No')->after('subtotal');
            }
            if (!Schema::hasColumn('ordered_items', 'item_paid_date')) {
                $table->timestamp('item_paid_date')->nullable()->after('item_is_paid');
            }
            if (!Schema::hasColumn('ordered_items', 'item_paid_amount')) {
                $table->decimal('item_paid_amount', 15, 2)->nullable()->after('item_paid_date');
            }
            
            // DTEHM Seller information
            if (!Schema::hasColumn('ordered_items', 'has_detehm_seller')) {
                $table->string('has_detehm_seller')->default('No')->after('item_paid_amount');
            }
            if (!Schema::hasColumn('ordered_items', 'dtehm_seller_id')) {
                $table->string('dtehm_seller_id')->nullable()->after('has_detehm_seller');
            }
            if (!Schema::hasColumn('ordered_items', 'dtehm_user_id')) {
                $table->unsignedBigInteger('dtehm_user_id')->nullable()->after('dtehm_seller_id');
            }
            
            // Commission processing status
            if (!Schema::hasColumn('ordered_items', 'commission_is_processed')) {
                $table->string('commission_is_processed')->default('No')->after('dtehm_user_id');
            }
            if (!Schema::hasColumn('ordered_items', 'commission_processed_date')) {
                $table->timestamp('commission_processed_date')->nullable()->after('commission_is_processed');
            }
            if (!Schema::hasColumn('ordered_items', 'total_commission_amount')) {
                $table->decimal('total_commission_amount', 15, 2)->nullable()->after('commission_processed_date');
            }
            if (!Schema::hasColumn('ordered_items', 'balance_after_commission')) {
                $table->decimal('balance_after_commission', 15, 2)->nullable()->after('total_commission_amount');
            }
            
            // Seller commission (10%)
            if (!Schema::hasColumn('ordered_items', 'commission_seller')) {
                $table->decimal('commission_seller', 15, 2)->nullable()->after('balance_after_commission');
            }
            
            // Commission amounts for each parent level
            for ($i = 1; $i <= 10; $i++) {
                $columnName = 'commission_parent_' . $i;
                if (!Schema::hasColumn('ordered_items', $columnName)) {
                    $table->decimal($columnName, 15, 2)->nullable()->after($i == 1 ? 'commission_seller' : 'commission_parent_' . ($i - 1));
                }
            }
            
            // Parent user IDs for tracking
            for ($i = 1; $i <= 10; $i++) {
                $columnName = 'parent_' . $i . '_user_id';
                if (!Schema::hasColumn('ordered_items', $columnName)) {
                    $table->unsignedBigInteger($columnName)->nullable()->after($i == 1 ? 'commission_parent_10' : 'parent_' . ($i - 1) . '_user_id');
                }
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
            $columns = [
                'item_is_paid',
                'item_paid_date',
                'item_paid_amount',
                'has_detehm_seller',
                'dtehm_seller_id',
                'dtehm_user_id',
                'commission_is_processed',
                'commission_processed_date',
                'total_commission_amount',
                'balance_after_commission',
                'commission_seller',
            ];
            
            // Add parent commission columns
            for ($i = 1; $i <= 10; $i++) {
                $columns[] = 'commission_parent_' . $i;
                $columns[] = 'parent_' . $i . '_user_id';
            }
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('ordered_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
