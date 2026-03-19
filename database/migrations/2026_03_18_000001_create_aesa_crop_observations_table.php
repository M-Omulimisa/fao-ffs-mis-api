<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAesaCropObservationsTable extends Migration
{
    public function up()
    {
        Schema::create('aesa_crop_observations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aesa_session_id');
            $table->foreign('aesa_session_id')
                  ->references('id')->on('aesa_sessions')
                  ->onDelete('cascade');

            // ── Section 2: Crop Identification ─────────────────────────────
            $table->string('plot_id', 100)->nullable();
            $table->string('farmer_name', 150)->nullable();
            $table->unsignedBigInteger('farmer_id')->nullable(); // FK to users (no constraint — users.id is int)
            $table->string('crop_type', 80)->nullable();
            $table->string('crop_type_other', 150)->nullable();
            $table->string('variety', 150)->nullable();
            $table->string('cropping_system', 80)->nullable();
            $table->string('planting_method', 80)->nullable();
            $table->date('planting_date')->nullable();
            $table->string('growth_stage', 80)->nullable();
            $table->decimal('plot_size_acres', 8, 2)->nullable();
            $table->string('irrigation_method', 80)->nullable();

            // ── Section 3: Weather Conditions ──────────────────────────────
            $table->string('weather_condition', 60)->nullable();
            $table->string('weather_condition_other', 150)->nullable();
            $table->string('temperature_level', 40)->nullable();
            $table->string('humidity_level', 40)->nullable();
            $table->boolean('rainfall_occurrence')->nullable();
            $table->string('wind_intensity', 60)->nullable();
            $table->text('additional_weather_notes')->nullable();

            // ── Section 4: Crop Health & Plant Observations ─────────────────
            $table->string('population_density', 40)->nullable();
            $table->decimal('plant_height_cm', 8, 2)->nullable();
            $table->string('leaf_colour', 60)->nullable();
            $table->string('leaf_condition', 60)->nullable();
            $table->string('stem_condition', 60)->nullable();
            $table->string('root_condition', 60)->nullable();
            $table->string('flowering_status', 60)->nullable();
            $table->string('fruit_grain_formation', 60)->nullable();
            $table->string('crop_vigor', 40)->nullable();

            // ── Section 5: Pests (None/Low/Medium/High) ─────────────────────
            $table->string('aphids_level', 20)->nullable();
            $table->string('caterpillars_armyworms_level', 20)->nullable();
            $table->string('beetles_level', 20)->nullable();
            $table->string('grasshoppers_level', 20)->nullable();
            $table->string('whiteflies_level', 20)->nullable();
            $table->string('other_insect_pests_level', 20)->nullable();
            $table->string('other_insect_pests_text', 150)->nullable();
            // Diseases (None/Low/Medium/High)
            $table->string('leaf_spot_level', 20)->nullable();
            $table->string('blight_level', 20)->nullable();
            $table->string('rust_level', 20)->nullable();
            $table->string('wilt_level', 20)->nullable();
            $table->string('mosaic_virus_level', 20)->nullable();
            $table->string('other_diseases_level', 20)->nullable();
            $table->string('other_diseases_text', 150)->nullable();

            // ── Section 6: Natural Enemies (None/Few/Moderate/Many) ─────────
            $table->string('ladybird_beetles_level', 20)->nullable();
            $table->string('spiders_level', 20)->nullable();
            $table->string('parasitoid_wasps_level', 20)->nullable();
            $table->string('bees_pollinators_level', 20)->nullable();
            $table->string('other_beneficial_level', 20)->nullable();
            $table->string('other_beneficial_text', 150)->nullable();

            // ── Section 7: Soil & Field Ecosystem ───────────────────────────
            $table->string('soil_condition', 40)->nullable();
            $table->string('soil_fertility_status', 40)->nullable();
            $table->string('soil_erosion_signs', 40)->nullable();
            $table->string('weed_presence', 20)->nullable();
            $table->string('dominant_weed_type', 150)->nullable();
            $table->boolean('mulching_present')->nullable();
            $table->string('crop_residue_cover', 40)->nullable();
            $table->string('water_drainage', 60)->nullable();

            // ── Section 8: Problems Identified ──────────────────────────────
            $table->string('main_problem', 150)->nullable();
            $table->string('main_problem_other', 150)->nullable();
            $table->string('cause_of_problem', 100)->nullable();
            $table->string('cause_of_problem_other', 150)->nullable();
            $table->string('risk_level', 20)->nullable();
            $table->text('problem_description')->nullable();

            // ── Section 9: Recommended Management Actions ────────────────────
            $table->string('immediate_action', 150)->nullable();
            $table->string('immediate_action_other', 150)->nullable();
            $table->string('soil_management_action', 150)->nullable();
            $table->string('soil_management_action_other', 150)->nullable();
            $table->string('preventive_action', 150)->nullable();
            $table->string('preventive_action_other', 150)->nullable();
            $table->string('monitoring_plan', 100)->nullable();
            $table->string('monitoring_plan_other', 150)->nullable();
            $table->string('responsible_person', 100)->nullable();
            $table->string('responsible_person_other', 150)->nullable();
            $table->date('follow_up_date')->nullable();

            // ── Section 10: Group Discussion ─────────────────────────────────
            $table->text('mini_group_findings')->nullable();
            $table->text('feedback_from_members')->nullable();
            $table->text('final_agreed_decision')->nullable();
            $table->text('key_learning_points')->nullable();
            $table->text('facilitator_remarks')->nullable();

            // Photos
            $table->json('photos')->nullable();

            // Multi-tenancy
            $table->unsignedBigInteger('ip_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable(); // FK to users (no constraint — users.id is int)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('aesa_crop_observations');
    }
}
