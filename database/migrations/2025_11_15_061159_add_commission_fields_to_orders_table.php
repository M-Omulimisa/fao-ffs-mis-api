<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionFieldsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Payment tracking
            if (!Schema::hasColumn('orders', 'order_is_paid')) {
                $table->string('order_is_paid')->default('No');
            }
            if (!Schema::hasColumn('orders', 'order_paid_date')) {
                $table->timestamp('order_paid_date')->nullable()->after('order_is_paid');
            }
            if (!Schema::hasColumn('orders', 'order_paid_amount')) {
                $table->decimal('order_paid_amount', 15, 2)->nullable()->after('order_paid_date');
            }
            
            // DTEHM Seller information
            if (!Schema::hasColumn('orders', 'has_detehm_seller')) {
                $table->string('has_detehm_seller')->default('No')->after('order_paid_amount');
            }
            if (!Schema::hasColumn('orders', 'dtehm_seller_id')) {
                $table->string('dtehm_seller_id')->nullable()->after('has_detehm_seller');
            }
            if (!Schema::hasColumn('orders', 'dtehm_user_id')) {
                $table->unsignedBigInteger('dtehm_user_id')->nullable()->after('dtehm_seller_id');
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
        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'order_is_paid',
                'order_paid_date',
                'order_paid_amount',
                'has_detehm_seller',
                'dtehm_seller_id',
                'dtehm_user_id',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
