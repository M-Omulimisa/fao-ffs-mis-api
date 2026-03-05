<?php

/**
 * AESA Test Data Seeder
 * Run: php seed_aesa_test_data.php
 * 
 * Creates sample AESA sessions and observations for testing purposes.
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AesaSession;
use App\Models\AesaObservation;
use App\Models\FfsGroup;
use App\Models\User;

echo "======================================\n";
echo "  AESA Test Data Seeder\n";
echo "======================================\n\n";

// Get a group and user for reference
$group = FfsGroup::first();
$user = User::first();

if (!$user) {
    echo "❌ No users found in database. Cannot seed.\n";
    exit(1);
}

echo "Using User: {$user->first_name} {$user->last_name} (ID: {$user->id})\n";
echo "Using Group: " . ($group ? $group->name . " (ID: {$group->id})" : "No group found, using NULL") . "\n\n";

// Create 3 test sessions
$sessions = [
    [
        'group_id' => $group ? $group->id : null,
        'district_text' => 'Busia',
        'sub_county_text' => 'Buteba',
        'village_text' => 'Luchuulo',
        'observation_date' => '2026-03-01',
        'observation_time' => '09:30',
        'facilitator_name' => 'John Okello',
        'mini_group_name' => 'Mini-Group A',
        'observation_location' => 'Grazing Field',
        'status' => 'submitted',
        'created_by_id' => $user->id,
        'ip_id' => $user->ip_id ?? null,
    ],
    [
        'group_id' => $group ? $group->id : null,
        'district_text' => 'Soroti',
        'sub_county_text' => 'Arapai',
        'village_text' => 'Obutet',
        'observation_date' => '2026-03-03',
        'observation_time' => '10:00',
        'facilitator_name' => 'Mary Apio',
        'mini_group_name' => 'Mini-Group B',
        'observation_location' => 'Farm',
        'status' => 'draft',
        'created_by_id' => $user->id,
        'ip_id' => $user->ip_id ?? null,
    ],
    [
        'group_id' => $group ? $group->id : null,
        'district_text' => 'Lira',
        'sub_county_text' => 'Barr',
        'village_text' => 'Agweng',
        'observation_date' => '2026-03-05',
        'observation_time' => '14:00',
        'facilitator_name' => 'Peter Otieno',
        'mini_group_name' => 'Mini-Group C',
        'observation_location' => 'Livestock Shelter',
        'status' => 'submitted',
        'created_by_id' => $user->id,
        'ip_id' => $user->ip_id ?? null,
    ],
];

$createdSessions = [];
foreach ($sessions as $i => $sessionData) {
    $session = AesaSession::create($sessionData);
    $createdSessions[] = $session;
    echo "✅ Created Session #{$session->id}: {$session->data_sheet_number}\n";
}

// Create observations for each session
$observations = [
    // Session 1 - Cow observation
    [
        'session_index' => 0,
        'animal_id_tag' => 'C123',
        'animal_type' => 'Cow',
        'breed' => 'Local',
        'colour' => 'Brown',
        'sex' => 'Female',
        'age_category' => 'Mature',
        'date_of_birth' => '2018-06-10',
        'weight_kg' => 450,
        'height_cm' => 120,
        'owner_name' => 'Peter Otieno',
        'animal_health_status' => 'Suspected Sick',
        'weather_condition' => 'Cloudy',
        'temperature_level' => 'Moderate',
        'humidity_level' => 'Medium',
        'rainfall_occurrence' => true,
        'wind_intensity' => 'Light Wind',
        'body_condition' => 'Moderate',
        'eyes_condition' => 'Dull',
        'coat_condition' => 'Rough',
        'appetite' => 'Reduced',
        'movement' => 'Active',
        'behaviour' => 'Normal',
        'ticks_level' => 'Medium',
        'fleas_level' => 'None',
        'lice_level' => 'Low',
        'mites_level' => 'None',
        'wounds_injuries' => false,
        'skin_infection' => false,
        'swelling' => false,
        'coughing' => true,
        'coughing_description' => 'Mild coughing observed in the morning',
        'diarrhea' => false,
        'feed_availability' => 'Limited',
        'water_availability' => 'Adequate',
        'grazing_condition' => 'Moderate Pasture',
        'housing_condition' => 'Good',
        'hygiene_condition' => 'Moderate',
        'animal_interaction' => 'Few',
        'main_problem' => 'Parasites',
        'cause_of_problem' => 'Environmental',
        'risk_level' => 'Medium',
        'problem_description' => 'Tick infestation with respiratory symptoms',
        'immediate_action' => 'Treat Parasites',
        'preventive_action' => 'Regular Spraying/Dipping',
        'monitoring_plan' => 'Weekly Monitoring',
        'responsible_person' => 'Farmer',
        'follow_up_date' => '2026-03-15',
        'mini_group_findings' => 'Cow shows signs of tick-borne disease. Needs immediate dipping.',
        'feedback_from_members' => 'Members agreed that the farmer should consult a vet.',
        'final_agreed_decision' => 'Schedule dipping and veterinary check-up within a week.',
        'facilitator_remarks' => 'Follow up needed. Recommended ECF vaccination.',
    ],
    // Session 1 - Goat observation
    [
        'session_index' => 0,
        'animal_id_tag' => 'G045',
        'animal_type' => 'Goat',
        'breed' => 'Crossbreed',
        'colour' => 'White',
        'sex' => 'Female',
        'age_category' => 'Growing',
        'weight_kg' => 25,
        'height_cm' => 55,
        'owner_name' => 'Sarah Adongo',
        'animal_health_status' => 'Healthy',
        'weather_condition' => 'Cloudy',
        'temperature_level' => 'Moderate',
        'humidity_level' => 'Medium',
        'rainfall_occurrence' => true,
        'wind_intensity' => 'Light Wind',
        'body_condition' => 'Good',
        'eyes_condition' => 'Bright',
        'coat_condition' => 'Smooth',
        'appetite' => 'Normal',
        'movement' => 'Active',
        'behaviour' => 'Normal',
        'ticks_level' => 'Low',
        'fleas_level' => 'None',
        'lice_level' => 'None',
        'mites_level' => 'None',
        'wounds_injuries' => false,
        'skin_infection' => false,
        'swelling' => false,
        'coughing' => false,
        'diarrhea' => false,
        'feed_availability' => 'Adequate',
        'water_availability' => 'Adequate',
        'grazing_condition' => 'Good Pasture',
        'housing_condition' => 'Good',
        'hygiene_condition' => 'Clean',
        'animal_interaction' => 'Few',
        'risk_level' => 'Low',
        'monitoring_plan' => 'Weekly Monitoring',
        'responsible_person' => 'Farmer',
        'mini_group_findings' => 'Goat appears healthy with good body condition.',
        'final_agreed_decision' => 'Continue regular monitoring and deworming schedule.',
        'facilitator_remarks' => 'Good animal management practices observed.',
    ],
    // Session 2 - Pig observation
    [
        'session_index' => 1,
        'animal_id_tag' => 'P078',
        'animal_type' => 'Pig',
        'breed' => 'Local',
        'colour' => 'Black',
        'sex' => 'Male',
        'age_category' => 'Mature',
        'weight_kg' => 80,
        'height_cm' => 65,
        'owner_name' => 'James Otim',
        'animal_health_status' => 'Sick',
        'weather_condition' => 'Sunny',
        'temperature_level' => 'Hot',
        'humidity_level' => 'High',
        'rainfall_occurrence' => false,
        'wind_intensity' => 'Calm',
        'body_condition' => 'Poor',
        'eyes_condition' => 'Dull',
        'coat_condition' => 'Dirty',
        'appetite' => 'No appetite',
        'movement' => 'Weak',
        'behaviour' => 'Lethargic',
        'ticks_level' => 'Low',
        'fleas_level' => 'Medium',
        'lice_level' => 'High',
        'mites_level' => 'Medium',
        'wounds_injuries' => true,
        'wounds_injuries_description' => 'Open wound on left hind leg',
        'skin_infection' => true,
        'skin_infection_description' => 'Skin lesions on back area',
        'swelling' => true,
        'swelling_description' => 'Swollen joints',
        'coughing' => true,
        'coughing_description' => 'Persistent coughing',
        'diarrhea' => true,
        'diarrhea_description' => 'Watery diarrhea for 3 days',
        'other_symptoms' => 'Fever detected, loss of weight over past week',
        'feed_availability' => 'Poor',
        'water_availability' => 'Limited',
        'grazing_condition' => 'Poor Pasture',
        'housing_condition' => 'Poor',
        'hygiene_condition' => 'Dirty',
        'animal_interaction' => 'Many',
        'main_problem' => 'Disease',
        'cause_of_problem' => 'Disease',
        'risk_level' => 'High',
        'problem_description' => 'Multiple symptoms suggesting African Swine Fever or severe parasitic infection',
        'immediate_action' => 'Provide Veterinary Treatment',
        'preventive_action' => 'Improve Hygiene',
        'monitoring_plan' => 'Daily Observation',
        'responsible_person' => 'Veterinary Officer',
        'follow_up_date' => '2026-03-06',
        'mini_group_findings' => 'Pig is critically ill. Needs urgent professional vet attention.',
        'feedback_from_members' => 'Isolate the pig immediately from others. Clean the housing.',
        'final_agreed_decision' => 'Emergency vet visit arranged. Pig to be isolated.',
        'facilitator_remarks' => 'High risk of disease spread. All pigs in the area should be monitored.',
    ],
    // Session 3 - Poultry observation
    [
        'session_index' => 2,
        'animal_id_tag' => 'PL200',
        'animal_type' => 'Poultry',
        'breed' => 'Local',
        'colour' => 'Mixed',
        'sex' => 'Female',
        'age_category' => 'Young',
        'weight_kg' => 2.5,
        'height_cm' => 30,
        'owner_name' => 'Grace Atim',
        'animal_health_status' => 'Healthy',
        'weather_condition' => 'Sunny',
        'temperature_level' => 'Cool',
        'humidity_level' => 'Low',
        'rainfall_occurrence' => false,
        'wind_intensity' => 'Light Wind',
        'body_condition' => 'Good',
        'eyes_condition' => 'Bright',
        'coat_condition' => 'Smooth',
        'appetite' => 'Normal',
        'movement' => 'Active',
        'behaviour' => 'Normal',
        'ticks_level' => 'None',
        'fleas_level' => 'Low',
        'lice_level' => 'None',
        'mites_level' => 'Low',
        'wounds_injuries' => false,
        'skin_infection' => false,
        'swelling' => false,
        'coughing' => false,
        'diarrhea' => false,
        'feed_availability' => 'Adequate',
        'water_availability' => 'Adequate',
        'grazing_condition' => 'Good Pasture',
        'housing_condition' => 'Good',
        'hygiene_condition' => 'Clean',
        'animal_interaction' => 'Many',
        'risk_level' => 'Low',
        'preventive_action' => 'Vaccination',
        'monitoring_plan' => 'Weekly Monitoring',
        'responsible_person' => 'Farmer',
        'mini_group_findings' => 'Poultry flock is healthy and well managed.',
        'final_agreed_decision' => 'Continue current management with routine vaccinations.',
        'facilitator_remarks' => 'Excellent husbandry practices. Model farmer for poultry.',
    ],
];

foreach ($observations as $obsData) {
    $sessionIndex = $obsData['session_index'];
    unset($obsData['session_index']);
    $obsData['aesa_session_id'] = $createdSessions[$sessionIndex]->id;
    $obsData['created_by_id'] = $user->id;
    $obsData['ip_id'] = $user->ip_id ?? null;

    $obs = AesaObservation::create($obsData);
    echo "  ✅ Created Observation #{$obs->id}: {$obs->animal_type} ({$obs->animal_id_tag}) → Session {$obs->aesa_session_id}\n";
}

echo "\n======================================\n";
echo "  ✅ Test data seeding complete!\n";
echo "  Sessions created: " . count($createdSessions) . "\n";
echo "  Observations created: " . count($observations) . "\n";
echo "======================================\n";
