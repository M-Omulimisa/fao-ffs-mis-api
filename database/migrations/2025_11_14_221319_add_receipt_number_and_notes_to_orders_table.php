<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceiptNumberAndNotesToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add receipt_number for tracking
            if (!Schema::hasColumn('orders', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->unique()->after('id')->comment('Unique order receipt number');
            }
            
            // Add invoice_number for invoicing
            if (!Schema::hasColumn('orders', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->unique()->after('receipt_number')->comment('Invoice number');
            }
            
            // Add order_date
            if (!Schema::hasColumn('orders', 'order_date')) {
                $table->date('order_date')->nullable()->after('created_at')->comment('Date of the order');
            }
            
            // Add notes field for admin notes
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('description')->comment('Admin notes about the order');
            }
            
            // Add sub_total, tax, discount fields for better order breakdown
            if (!Schema::hasColumn('orders', 'sub_total')) {
                $table->decimal('sub_total', 15, 2)->default(0)->after('order_total')->comment('Subtotal before tax and fees');
            }
            
            if (!Schema::hasColumn('orders', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('sub_total')->comment('Tax amount');
            }
            
            if (!Schema::hasColumn('orders', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0)->after('tax')->comment('Discount amount');
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
            $columns = ['receipt_number', 'invoice_number', 'order_date', 'notes', 'sub_total', 'tax', 'discount'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
