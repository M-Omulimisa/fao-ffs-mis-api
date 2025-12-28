<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->string('user_type')->default('customer');
            $table->integer('campus_id')->nullable();
            
            // Business fields
            $table->string('business_name')->nullable();
            $table->string('business_license_number')->nullable();
            $table->string('business_phone_number')->nullable();
            $table->string('business_email')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
