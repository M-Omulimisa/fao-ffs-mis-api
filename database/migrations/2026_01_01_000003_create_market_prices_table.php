<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('district_id')->nullable();
            $table->integer('sub_county_id')->nullable();
            $table->string('market_name');
            $table->decimal('price', 15, 2);
            $table->decimal('price_min', 15, 2)->nullable();
            $table->decimal('price_max', 15, 2)->nullable();
            $table->string('currency', 10)->default('UGX');
            $table->string('unit')->nullable(); // Override product unit if needed
            $table->string('quantity')->nullable(); // e.g., "per kg", "per 100kg bag"
            $table->date('date');
            $table->string('source')->nullable(); // Who reported this price
            $table->text('notes')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->integer('created_by')->nullable();
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('district_id');
            $table->index('sub_county_id');
            $table->index('date');
            $table->index('status');
            $table->index(['product_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('market_prices');
    }
}
