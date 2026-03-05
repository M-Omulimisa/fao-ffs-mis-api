<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAesaObservationsTable extends Migration
{
    public function up()
    {
        Schema::create('aesa_observations', function (Blueprint $table) {
            $table->id();

            // Parent session reference
            $table->unsignedBigInteger('aesa_session_id');

            // ================================================================
            // SECTION 2: Animal Identification & General Information
            // ================================================================
            $table->string('animal_id_tag')->nullable();
            $table->string('animal_type')->nullable(); // Cow, Goat, Sheep, Pig, Poultry, Donkey
            $table->string('animal_type_other')->nullable();
            $table->string('breed')->nullable(); // Local, Crossbreed, Exotic
            $table->string('breed_other')->nullable();
            $table->string('colour')->nullable(); // White, Brown, Black, Mixed
            $table->string('colour_other')->nullable();
            $table->string('sex')->nullable(); // Male, Female
            $table->string('age_category')->nullable(); // Young, Growing, Mature, Old
            $table->date('date_of_birth')->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->string('owner_name')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable(); // FK to users (farmer)
            $table->string('animal_health_status')->nullable(); // Healthy, Suspected Sick, Sick
            $table->string('animal_health_status_other')->nullable();

            // ================================================================
            // SECTION 3: Weather Conditions During Observation
            // ================================================================
            $table->string('weather_condition')->nullable(); // Sunny, Cloudy, Rainy, Windy
            $table->string('weather_condition_other')->nullable();
            $table->string('temperature_level')->nullable(); // Cool, Moderate, Hot
            $table->string('temperature_level_other')->nullable();
            $table->string('humidity_level')->nullable(); // Low, Medium, High
            $table->string('humidity_level_other')->nullable();
            $table->boolean('rainfall_occurrence')->nullable();
            $table->string('wind_intensity')->nullable(); // Calm, Light Wind, Strong Wind
            $table->string('wind_intensity_other')->nullable();
            $table->text('additional_weather_notes')->nullable();

            // ================================================================
            // SECTION 4: Animal Health & Physical Observations
            // ================================================================
            $table->string('body_condition')->nullable(); // Good, Moderate, Poor
            $table->string('body_condition_other')->nullable();
            $table->string('eyes_condition')->nullable(); // Bright, Dull, Discharge, Swollen
            $table->string('eyes_condition_other')->nullable();
            $table->string('coat_condition')->nullable(); // Smooth, Rough, Hair Loss, Dirty
            $table->string('coat_condition_other')->nullable();
            $table->string('appetite')->nullable(); // Normal, Reduced, No appetite
            $table->string('appetite_other')->nullable();
            $table->string('movement')->nullable(); // Active, Weak, Limping
            $table->string('movement_other')->nullable();
            $table->string('behaviour')->nullable(); // Normal, Aggressive, Lethargic
            $table->string('behaviour_other')->nullable();

            // External Parasite Observation
            $table->string('ticks_level')->nullable(); // None, Low, Medium, High
            $table->string('fleas_level')->nullable();
            $table->string('lice_level')->nullable();
            $table->string('mites_level')->nullable();
            $table->text('other_parasites_text')->nullable();

            // Other Health Observations
            $table->boolean('wounds_injuries')->nullable();
            $table->text('wounds_injuries_description')->nullable();
            $table->boolean('skin_infection')->nullable();
            $table->text('skin_infection_description')->nullable();
            $table->boolean('swelling')->nullable();
            $table->text('swelling_description')->nullable();
            $table->boolean('coughing')->nullable();
            $table->text('coughing_description')->nullable();
            $table->boolean('diarrhea')->nullable();
            $table->text('diarrhea_description')->nullable();
            $table->text('other_symptoms')->nullable();

            // ================================================================
            // SECTION 5: AESA Ecosystem Observations
            // ================================================================
            $table->string('feed_availability')->nullable(); // Adequate, Limited, Poor
            $table->string('feed_availability_other')->nullable();
            $table->string('water_availability')->nullable(); // Adequate, Limited, None
            $table->string('water_availability_other')->nullable();
            $table->string('grazing_condition')->nullable(); // Good Pasture, Moderate Pasture, Poor Pasture
            $table->string('grazing_condition_other')->nullable();
            $table->string('housing_condition')->nullable(); // Good, Moderate, Poor
            $table->string('housing_condition_other')->nullable();
            $table->string('hygiene_condition')->nullable(); // Clean, Moderate, Dirty
            $table->string('hygiene_condition_other')->nullable();
            $table->string('animal_interaction')->nullable(); // None, Few, Many
            $table->string('animal_interaction_other')->nullable();

            // ================================================================
            // SECTION 6: Problems Identified
            // ================================================================
            $table->string('main_problem')->nullable();
            $table->string('main_problem_other')->nullable();
            $table->string('cause_of_problem')->nullable();
            $table->string('cause_of_problem_other')->nullable();
            $table->string('risk_level')->nullable(); // Low, Medium, High
            $table->text('problem_description')->nullable();

            // ================================================================
            // SECTION 7: Recommended Management Actions
            // ================================================================
            $table->string('immediate_action')->nullable();
            $table->string('immediate_action_other')->nullable();
            $table->string('preventive_action')->nullable();
            $table->string('preventive_action_other')->nullable();
            $table->string('monitoring_plan')->nullable();
            $table->string('monitoring_plan_other')->nullable();
            $table->string('responsible_person')->nullable();
            $table->string('responsible_person_other')->nullable();
            $table->date('follow_up_date')->nullable();

            // ================================================================
            // SECTION 8: Group Discussion and Validation
            // ================================================================
            $table->text('mini_group_findings')->nullable();
            $table->text('feedback_from_members')->nullable();
            $table->text('final_agreed_decision')->nullable();
            $table->text('facilitator_remarks')->nullable();

            // ================================================================
            // Photo evidence (JSON array of photo paths)
            // ================================================================
            $table->json('photos')->nullable();

            // Multi-tenancy & audit
            $table->unsignedBigInteger('ip_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('aesa_session_id');
            $table->index('animal_type');
            $table->index('animal_health_status');
            $table->index('risk_level');
            $table->index('ip_id');
            $table->index('created_by_id');

            $table->foreign('aesa_session_id')
                ->references('id')
                ->on('aesa_sessions')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('aesa_observations');
    }
}
