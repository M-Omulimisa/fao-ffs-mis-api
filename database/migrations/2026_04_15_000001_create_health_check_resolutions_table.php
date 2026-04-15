<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthCheckResolutionsTable extends Migration
{
    public function up()
    {
        Schema::create('health_check_resolutions', function (Blueprint $table) {
            $table->id();
            $table->string('check_key', 60)->index();        // e.g. groups_empty, duplicate_phone
            $table->string('entity_type', 10)->index();       // 'group' or 'user'
            $table->unsignedBigInteger('entity_id')->index(); // ffs_groups.id or users.id
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->unique(['check_key', 'entity_type', 'entity_id'], 'hcr_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('health_check_resolutions');
    }
}
