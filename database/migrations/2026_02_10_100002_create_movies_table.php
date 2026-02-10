<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('poster_image')->nullable();
            $table->string('category')->nullable();
            $table->string('genre')->nullable();
            $table->integer('year')->nullable();
            $table->string('rating')->nullable();
            $table->integer('duration')->nullable(); // minutes
            $table->string('source_url')->nullable();
            $table->string('quality')->nullable();
            $table->string('language')->nullable();
            $table->string('status')->default('active');

            // Debug / Fix tracking fields
            $table->string('fix_status')->nullable()->default('pending');
            $table->text('fix_message')->nullable();
            $table->timestamp('last_fix_date')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movies');
    }
};
