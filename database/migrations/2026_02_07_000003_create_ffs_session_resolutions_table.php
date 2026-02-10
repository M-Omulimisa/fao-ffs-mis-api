<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsSessionResolutionsTable extends Migration
{
    public function up()
    {
        Schema::create('ffs_session_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('resolution');
            $table->text('description')->nullable();
            $table->string('gap_category')->nullable(); // soil, water, seeds, pest, harvest, storage, marketing, other
            $table->unsignedBigInteger('responsible_person_id')->nullable();
            $table->date('target_date')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->text('follow_up_notes')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('session_id');
            $table->index('status');
            $table->index('gap_category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ffs_session_resolutions');
    }
}
