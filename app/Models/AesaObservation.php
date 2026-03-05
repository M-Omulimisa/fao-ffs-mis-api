<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AesaObservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aesa_observations';

    protected $fillable = [
        'aesa_session_id',
        // Section 2: Animal Identification
        'animal_id_tag',
        'animal_type',
        'animal_type_other',
        'breed',
        'breed_other',
        'colour',
        'colour_other',
        'sex',
        'age_category',
        'date_of_birth',
        'weight_kg',
        'height_cm',
        'owner_name',
        'owner_id',
        'animal_health_status',
        'animal_health_status_other',
        // Section 3: Weather Conditions
        'weather_condition',
        'weather_condition_other',
        'temperature_level',
        'temperature_level_other',
        'humidity_level',
        'humidity_level_other',
        'rainfall_occurrence',
        'wind_intensity',
        'wind_intensity_other',
        'additional_weather_notes',
        // Section 4: Health & Physical Observations
        'body_condition',
        'body_condition_other',
        'eyes_condition',
        'eyes_condition_other',
        'coat_condition',
        'coat_condition_other',
        'appetite',
        'appetite_other',
        'movement',
        'movement_other',
        'behaviour',
        'behaviour_other',
        'ticks_level',
        'fleas_level',
        'lice_level',
        'mites_level',
        'other_parasites_text',
        'wounds_injuries',
        'wounds_injuries_description',
        'skin_infection',
        'skin_infection_description',
        'swelling',
        'swelling_description',
        'coughing',
        'coughing_description',
        'diarrhea',
        'diarrhea_description',
        'other_symptoms',
        // Section 5: Ecosystem Observations
        'feed_availability',
        'feed_availability_other',
        'water_availability',
        'water_availability_other',
        'grazing_condition',
        'grazing_condition_other',
        'housing_condition',
        'housing_condition_other',
        'hygiene_condition',
        'hygiene_condition_other',
        'animal_interaction',
        'animal_interaction_other',
        // Section 6: Problems Identified
        'main_problem',
        'main_problem_other',
        'cause_of_problem',
        'cause_of_problem_other',
        'risk_level',
        'problem_description',
        // Section 7: Recommended Actions
        'immediate_action',
        'immediate_action_other',
        'preventive_action',
        'preventive_action_other',
        'monitoring_plan',
        'monitoring_plan_other',
        'responsible_person',
        'responsible_person_other',
        'follow_up_date',
        // Section 8: Group Discussion
        'mini_group_findings',
        'feedback_from_members',
        'final_agreed_decision',
        'facilitator_remarks',
        // Photos
        'photos',
        // Multi-tenancy
        'ip_id',
        'created_by_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'follow_up_date' => 'date',
        'weight_kg' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'rainfall_occurrence' => 'boolean',
        'wounds_injuries' => 'boolean',
        'skin_infection' => 'boolean',
        'swelling' => 'boolean',
        'coughing' => 'boolean',
        'diarrhea' => 'boolean',
        'photos' => 'array',
    ];

    protected $appends = [
        'animal_type_display',
        'breed_display',
        'colour_display',
        'health_status_display',
        'risk_level_display',
        'owner_display',
        'health_score',
        'parasite_severity',
    ];

    // ── Constants ────────────────────────────────────

    const ANIMAL_TYPES = ['Cow', 'Goat', 'Sheep', 'Pig', 'Poultry', 'Donkey'];
    const BREEDS = ['Local', 'Crossbreed', 'Exotic'];
    const COLOURS = ['White', 'Brown', 'Black', 'Mixed'];
    const SEXES = ['Male', 'Female'];
    const AGE_CATEGORIES = ['Young', 'Growing', 'Mature', 'Old'];
    const HEALTH_STATUSES = ['Healthy', 'Suspected Sick', 'Sick'];
    
    const WEATHER_CONDITIONS = ['Sunny', 'Cloudy', 'Rainy', 'Windy'];
    const TEMPERATURE_LEVELS = ['Cool', 'Moderate', 'Hot'];
    const HUMIDITY_LEVELS = ['Low', 'Medium', 'High'];
    const WIND_INTENSITIES = ['Calm', 'Light Wind', 'Strong Wind'];
    
    const BODY_CONDITIONS = ['Good', 'Moderate', 'Poor'];
    const EYES_CONDITIONS = ['Bright', 'Dull', 'Discharge', 'Swollen'];
    const COAT_CONDITIONS = ['Smooth', 'Rough', 'Hair Loss', 'Dirty'];
    const APPETITES = ['Normal', 'Reduced', 'No appetite'];
    const MOVEMENTS = ['Active', 'Weak', 'Limping'];
    const BEHAVIOURS = ['Normal', 'Aggressive', 'Lethargic'];
    const PARASITE_LEVELS = ['None', 'Low', 'Medium', 'High'];
    
    const FEED_AVAILABILITIES = ['Adequate', 'Limited', 'Poor'];
    const WATER_AVAILABILITIES = ['Adequate', 'Limited', 'None'];
    const GRAZING_CONDITIONS = ['Good Pasture', 'Moderate Pasture', 'Poor Pasture'];
    const HOUSING_CONDITIONS = ['Good', 'Moderate', 'Poor'];
    const HYGIENE_CONDITIONS = ['Clean', 'Moderate', 'Dirty'];
    const ANIMAL_INTERACTIONS = ['None', 'Few', 'Many'];
    
    const MAIN_PROBLEMS = [
        'Parasites', 'Disease', 'Poor Nutrition', 'Poor Housing', 'Water Shortage', 'Injury'
    ];
    const CAUSES_OF_PROBLEM = [
        'Environmental', 'Disease', 'Management Practice', 'Feed Shortage'
    ];
    const RISK_LEVELS = ['Low', 'Medium', 'High'];
    
    const IMMEDIATE_ACTIONS = [
        'Treat Parasites', 'Clean Housing', 'Provide Veterinary Treatment', 'Improve Feeding'
    ];
    const PREVENTIVE_ACTIONS = [
        'Vaccination', 'Regular Spraying/Dipping', 'Improve Hygiene', 'Improve Nutrition'
    ];
    const MONITORING_PLANS = [
        'Daily Observation', 'Weekly Monitoring', 'Veterinary Follow-Up'
    ];
    const RESPONSIBLE_PERSONS = [
        'Farmer', 'Facilitator', 'Community Animal Health Worker', 'Veterinary Officer'
    ];

    // ── Boot ─────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Inherit ip_id from session
            if (empty($model->ip_id) && $model->aesa_session_id) {
                $session = AesaSession::find($model->aesa_session_id);
                if ($session && $session->ip_id) {
                    $model->ip_id = $session->ip_id;
                }
            }

            // Set created_by_id
            if (empty($model->created_by_id)) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user) {
                    $model->created_by_id = $user->id;
                }
            }
        });
    }

    // ── Relationships ────────────────────────────────

    public function session()
    {
        return $this->belongsTo(AesaSession::class, 'aesa_session_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function implementingPartner()
    {
        return $this->belongsTo(ImplementingPartner::class, 'ip_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // ── Accessors ────────────────────────────────────

    public function getAnimalTypeDisplayAttribute(): ?string
    {
        if ($this->animal_type === 'Other' && $this->animal_type_other) {
            return $this->animal_type_other;
        }
        return $this->animal_type;
    }

    public function getBreedDisplayAttribute(): ?string
    {
        if ($this->breed === 'Other' && $this->breed_other) {
            return $this->breed_other;
        }
        return $this->breed;
    }

    public function getColourDisplayAttribute(): ?string
    {
        if ($this->colour === 'Other' && $this->colour_other) {
            return $this->colour_other;
        }
        return $this->colour;
    }

    public function getHealthStatusDisplayAttribute(): ?string
    {
        if ($this->animal_health_status === 'Other' && $this->animal_health_status_other) {
            return $this->animal_health_status_other;
        }
        return $this->animal_health_status;
    }

    public function getRiskLevelDisplayAttribute(): ?string
    {
        return $this->risk_level ? ucfirst($this->risk_level) : null;
    }

    public function getOwnerDisplayAttribute(): ?string
    {
        if ($this->owner) {
            return trim($this->owner->first_name . ' ' . $this->owner->last_name);
        }
        return $this->owner_name;
    }

    /**
     * Calculate a health score (0-100) based on observation data
     * Higher score = healthier animal
     */
    public function getHealthScoreAttribute(): int
    {
        $score = 100;
        $deductions = 0;

        // Body condition
        if ($this->body_condition === 'Moderate') $deductions += 10;
        if ($this->body_condition === 'Poor') $deductions += 25;

        // Health status
        if ($this->animal_health_status === 'Suspected Sick') $deductions += 15;
        if ($this->animal_health_status === 'Sick') $deductions += 30;

        // Appetite
        if ($this->appetite === 'Reduced') $deductions += 10;
        if ($this->appetite === 'No appetite') $deductions += 20;

        // Movement
        if ($this->movement === 'Weak') $deductions += 10;
        if ($this->movement === 'Limping') $deductions += 15;

        // Parasites
        $parasiteFields = ['ticks_level', 'fleas_level', 'lice_level', 'mites_level'];
        foreach ($parasiteFields as $field) {
            if ($this->$field === 'Low') $deductions += 2;
            if ($this->$field === 'Medium') $deductions += 5;
            if ($this->$field === 'High') $deductions += 8;
        }

        // Health issues
        if ($this->wounds_injuries) $deductions += 10;
        if ($this->skin_infection) $deductions += 10;
        if ($this->swelling) $deductions += 10;
        if ($this->coughing) $deductions += 10;
        if ($this->diarrhea) $deductions += 15;

        return max(0, $score - $deductions);
    }

    /**
     * Calculate parasite severity level based on parasite observations
     */
    public function getParasiteSeverityAttribute(): string
    {
        $levels = ['ticks_level', 'fleas_level', 'lice_level', 'mites_level'];
        $maxLevel = 'None';
        $levelOrder = ['None' => 0, 'Low' => 1, 'Medium' => 2, 'High' => 3];

        foreach ($levels as $field) {
            $value = $this->$field ?? 'None';
            if (($levelOrder[$value] ?? 0) > ($levelOrder[$maxLevel] ?? 0)) {
                $maxLevel = $value;
            }
        }

        return $maxLevel;
    }

    // ── Scopes ───────────────────────────────────────

    public function scopeByAnimalType($query, $type)
    {
        return $query->where('animal_type', $type);
    }

    public function scopeByRiskLevel($query, $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeSick($query)
    {
        return $query->whereIn('animal_health_status', ['Suspected Sick', 'Sick']);
    }

    public function scopeHealthy($query)
    {
        return $query->where('animal_health_status', 'Healthy');
    }

    /**
     * Get all dropdown options as a structured array for the frontend
     */
    public static function getDropdownOptions(): array
    {
        return [
            'animal_types' => self::ANIMAL_TYPES,
            'breeds' => self::BREEDS,
            'colours' => self::COLOURS,
            'sexes' => self::SEXES,
            'age_categories' => self::AGE_CATEGORIES,
            'health_statuses' => self::HEALTH_STATUSES,
            'weather_conditions' => self::WEATHER_CONDITIONS,
            'temperature_levels' => self::TEMPERATURE_LEVELS,
            'humidity_levels' => self::HUMIDITY_LEVELS,
            'wind_intensities' => self::WIND_INTENSITIES,
            'body_conditions' => self::BODY_CONDITIONS,
            'eyes_conditions' => self::EYES_CONDITIONS,
            'coat_conditions' => self::COAT_CONDITIONS,
            'appetites' => self::APPETITES,
            'movements' => self::MOVEMENTS,
            'behaviours' => self::BEHAVIOURS,
            'parasite_levels' => self::PARASITE_LEVELS,
            'feed_availabilities' => self::FEED_AVAILABILITIES,
            'water_availabilities' => self::WATER_AVAILABILITIES,
            'grazing_conditions' => self::GRAZING_CONDITIONS,
            'housing_conditions' => self::HOUSING_CONDITIONS,
            'hygiene_conditions' => self::HYGIENE_CONDITIONS,
            'animal_interactions' => self::ANIMAL_INTERACTIONS,
            'main_problems' => self::MAIN_PROBLEMS,
            'causes_of_problem' => self::CAUSES_OF_PROBLEM,
            'risk_levels' => self::RISK_LEVELS,
            'immediate_actions' => self::IMMEDIATE_ACTIONS,
            'preventive_actions' => self::PREVENTIVE_ACTIONS,
            'monitoring_plans' => self::MONITORING_PLANS,
            'responsible_persons' => self::RESPONSIBLE_PERSONS,
            'observation_locations' => AesaSession::OBSERVATION_LOCATIONS,
            'mini_groups' => AesaSession::MINI_GROUPS,
        ];
    }
}
