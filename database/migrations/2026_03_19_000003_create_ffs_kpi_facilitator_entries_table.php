<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsKpiFacilitatorEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('ffs_kpi_facilitator_entries', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('ip_id');
            $table->foreign('ip_id')->references('id')->on('implementing_partners')->onDelete('cascade');

            // facilitator_id references users.id (int(11) signed — no FK constraint due to type)
            $table->integer('facilitator_id')->nullable();

            $table->unsignedBigInteger('indicator_id');
            $table->foreign('indicator_id')->references('id')->on('ffs_kpi_indicators');

            $table->string('disaggregation');   // "Female", "Male", "Youth", "PWD"

            $table->string('district')->nullable();
            $table->string('sub_county')->nullable();

            $table->unsignedBigInteger('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('ffs_groups')->onDelete('set null');

            $table->date('session_date')->nullable();
            $table->decimal('value', 10, 2)->default(0);
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('ip_id');
            $table->index('facilitator_id');
            $table->index('indicator_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ffs_kpi_facilitator_entries');
    }
}
