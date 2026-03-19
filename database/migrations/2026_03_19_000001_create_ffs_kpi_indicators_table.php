<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFfsKpiIndicatorsTable extends Migration
{
    public function up()
    {
        Schema::create('ffs_kpi_indicators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('output_number');                         // 1, 2, 3
            $table->string('output_name');                               // e.g. "FFS/FBS Established…"
            $table->enum('type', ['ip', 'facilitator']);                 // who enters this KPI
            $table->string('indicator_name');
            $table->decimal('default_target', 10, 2)->default(0);
            $table->enum('location_config', [
                'group',           // Output 1 — District + Sub-county + FFS Group
                'institution',     // Output 2 — District + Sub-county + Institution
                'location_type',   // Output 3 — District + Location Type
                'district_only',   // Just District (no sub-location)
            ]);
            $table->json('possible_disaggregations');                    // ["Total","Female","Male",...]
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ffs_kpi_indicators');
    }
}
