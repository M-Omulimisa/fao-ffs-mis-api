<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingDeliveryColumnsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add delivery_method if it doesn't exist
            if (!Schema::hasColumn('orders', 'delivery_method')) {
                $table->string('delivery_method')->nullable()->after('customer_address')->comment('Delivery or Pickup');
            }
            
            // Add delivery_address_id if it doesn't exist
            if (!Schema::hasColumn('orders', 'delivery_address_id')) {
                $table->unsignedBigInteger('delivery_address_id')->nullable()->after('delivery_method')->comment('Foreign key to delivery_addresses');
            }
            
            // Add delivery_address_text if it doesn't exist
            if (!Schema::hasColumn('orders', 'delivery_address_text')) {
                $table->text('delivery_address_text')->nullable()->after('delivery_address_id')->comment('Delivery location text');
            }
            
            // Add delivery_address_details if it doesn't exist
            if (!Schema::hasColumn('orders', 'delivery_address_details')) {
                $table->text('delivery_address_details')->nullable()->after('delivery_address_text')->comment('Specific delivery address details');
            }
            
            // Add delivery_amount if it doesn't exist (rename from delivery_fee if needed)
            if (!Schema::hasColumn('orders', 'delivery_amount')) {
                $table->decimal('delivery_amount', 15, 2)->default(0)->after('delivery_address_details')->comment('Delivery fee amount');
            }
            
            // Add payable_amount if it doesn't exist
            if (!Schema::hasColumn('orders', 'payable_amount')) {
                $table->decimal('payable_amount', 15, 2)->default(0)->after('order_total')->comment('Total amount payable including delivery');
            }
            
            // Add items if it doesn't exist
            if (!Schema::hasColumn('orders', 'items')) {
                $table->text('items')->nullable()->after('order_details')->comment('JSON items data for backward compatibility');
            }
            
            // Add phone_number fields if they don't exist (backward compatibility)
            if (!Schema::hasColumn('orders', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('customer_phone_number_2')->comment('Primary phone number');
            }
            
            if (!Schema::hasColumn('orders', 'phone_number_1')) {
                $table->string('phone_number_1')->nullable()->after('phone_number')->comment('Phone number 1');
            }
            
            if (!Schema::hasColumn('orders', 'phone_number_2')) {
                $table->string('phone_number_2')->nullable()->after('phone_number_1')->comment('Phone number 2');
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
                'delivery_method',
                'delivery_address_id', 
                'delivery_address_text',
                'delivery_address_details',
                'delivery_amount',
                'payable_amount',
                'items',
                'phone_number',
                'phone_number_1',
                'phone_number_2'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
