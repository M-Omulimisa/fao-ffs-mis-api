<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsKpiIpEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('ffs_kpi_ip_entries', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('ip_id');
            $table->foreign('ip_id')->references('id')->on('implementing_partners')->onDelete('cascade');

            $table->unsignedBigInteger('indicator_id');
            $table->foreign('indicator_id')->references('id')->on('ffs_kpi_indicators');

            $table->string('disaggregation');               // "Total", "Female", "New", "N/A", etc.

            // Location fields — which ones are used depends on indicator.location_config
            $table->string('district')->nullable();
            $table->string('sub_county')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();  // Output 1
            $table->foreign('group_id')->references('id')->on('ffs_groups')->onDelete('set null');
            $table->string('institution')->nullable();            // Output 2
            $table->string('location_type')->nullable();          // Output 3

            $table->smallInteger('year')->default(0);             // set in controller

            $table->decimal('target', 10, 2)->default(0);

            // Monthly actuals (Jan–Dec, all nullable)
            $table->decimal('jan', 10, 2)->nullable();
            $table->decimal('feb', 10, 2)->nullable();
            $table->decimal('mar', 10, 2)->nullable();
            $table->decimal('apr', 10, 2)->nullable();
            $table->decimal('may', 10, 2)->nullable();
            $table->decimal('jun', 10, 2)->nullable();
            $table->decimal('jul', 10, 2)->nullable();
            $table->decimal('aug', 10, 2)->nullable();
            $table->decimal('sep', 10, 2)->nullable();
            $table->decimal('oct', 10, 2)->nullable();
            $table->decimal('nov', 10, 2)->nullable();
            $table->decimal('dec', 10, 2)->nullable();

            $table->text('comments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index('ip_id');
            $table->index('indicator_id');
            $table->index('year');
            $table->index(['ip_id', 'indicator_id', 'year'], 'kpi_ip_entries_lookup');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ffs_kpi_ip_entries');
    }
}
