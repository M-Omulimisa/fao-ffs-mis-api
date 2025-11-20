<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDtehmMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dtehm_memberships', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2)->default(76000);
            $table->enum('status', ['PENDING', 'CONFIRMED', 'FAILED', 'REFUNDED'])->default('PENDING');
            $table->enum('payment_method', ['CASH', 'MOBILE_MONEY', 'BANK_TRANSFER', 'PESAPAL'])->nullable();
            $table->string('payment_phone_number')->nullable();
            $table->string('payment_account_number')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->enum('membership_type', ['DTEHM'])->default('DTEHM');
            $table->date('expiry_date')->nullable();
            $table->text('receipt_photo')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            
            // Pesapal integration fields
            $table->string('pesapal_merchant_reference')->nullable();
            $table->string('pesapal_tracking_id')->nullable();
            $table->string('pesapal_payment_status_code')->nullable();
            $table->text('pesapal_payment_status_description')->nullable();
            $table->string('confirmation_code')->nullable();
            
            // Link to universal payment system
            $table->bigInteger('universal_payment_id')->nullable();
            
            // Audit fields
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->bigInteger('confirmed_by')->nullable();
            $table->bigInteger('registered_by_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dtehm_memberships');
    }
}
