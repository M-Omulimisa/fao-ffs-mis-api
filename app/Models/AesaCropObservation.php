<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AesaCropObservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aesa_crop_observations';

    protected $fillable = [
        'aesa_session_id',
        // Section 2: Crop Identification
        'plot_id',
        'farmer_name',
        'farmer_id',
        'crop_type',
        'crop_type_other',
        'variety',
        'cropping_system',
        'planting_method',
        'planting_date',
        'growth_stage',
        'plot_size_acres',
        'irrigation_method',
        // Section 3: Weather Conditions
        'weather_condition',
        'weather_condition_other',
        'temperature_level',
        'humidity_level',
        'rainfall_occurrence',
        'wind_intensity',
        'additional_weather_notes',
        // Section 4: Crop Health & Plant Observations
        'population_density',
        'plant_height_cm',
        'leaf_colour',
        'leaf_condition',
        'stem_condition',
        'root_condition',
        'flowering_status',
        'fruit_grain_formation',
        'crop_vigor',
        // Section 5: Pests
        'aphids_level',
        'caterpillars_armyworms_level',
        'beetles_level',
        'grasshoppers_level',
        'whiteflies_level',
        'other_insect_pests_level',
        'other_insect_pests_text',
        // Section 5: Diseases
        'leaf_spot_level',
        'blight_level',
        'rust_level',
        'wilt_level',
        'mosaic_virus_level',
        'other_diseases_level',
        'other_diseases_text',
        // Section 6: Natural Enemies
        'ladybird_beetles_level',
        'spiders_level',
        'parasitoid_wasps_level',
        'bees_pollinators_level',
        'other_beneficial_level',
        'other_beneficial_text',
        // Section 7: Soil & Field
        'soil_condition',
        'soil_fertility_status',
        'soil_erosion_signs',
        'weed_presence',
        'dominant_weed_type',
        'mulching_present',
        'crop_residue_cover',
        'water_drainage',
        // Section 8: Problems
        'main_problem',
        'main_problem_other',
        'cause_of_problem',
        'cause_of_problem_other',
        'risk_level',
        'problem_description',
        // Section 9: Management Actions
        'immediate_action',
        'immediate_action_other',
        'soil_management_action',
        'soil_management_action_other',
        'preventive_action',
        'preventive_action_other',
        'monitoring_plan',
        'monitoring_plan_other',
        'responsible_person',
        'responsible_person_other',
        'follow_up_date',
        // Section 10: Group Discussion
        'mini_group_findings',
        'feedback_from_members',
        'final_agreed_decision',
        'key_learning_points',
        'facilitator_remarks',
        // Photos
        'photos',
        // Multi-tenancy
        'ip_id',
        'created_by_id',
    ];

    protected $casts = [
        'planting_date'       => 'date',
        'follow_up_date'      => 'date',
        'plot_size_acres'     => 'decimal:2',
        'plant_height_cm'     => 'decimal:2',
        'rainfall_occurrence' => 'boolean',
        'mulching_present'    => 'boolean',
        'photos'              => 'array',
    ];

    protected $appends = [
        'crop_type_display',
        'farmer_display',
        'risk_level_display',
        'crop_health_score',
        'pest_pressure_level',
        'disease_pressure_level',
    ];

    // ── Constants ────────────────────────────────────

    const CROP_TYPES = [
        'Sorghum', 'Millet', 'Maize', 'Cassava', 'Sweet Potato',
        'Beans', 'Groundnuts', 'Sunflower', 'Sesame', 'Cowpeas', 'Other',
    ];

    const CROPPING_SYSTEMS = [
        'Mono-crop', 'Intercrop', 'Mixed Cropping', 'Relay Cropping',
    ];

    const PLANTING_METHODS = [
        'Direct Seeding', 'Transplanting', 'Cuttings/Stems', 'Tubers/Roots',
    ];

    const GROWTH_STAGES = [
        'Germination', 'Seedling', 'Vegetative', 'Flowering',
        'Grain/Fruit Filling', 'Maturity', 'Post-Harvest',
    ];

    const IRRIGATION_METHODS = [
        'Rain-fed', 'Irrigated', 'Supplemental Irrigation',
    ];

    const WEATHER_CONDITIONS = [
        'Sunny', 'Cloudy', 'Rainy', 'Windy',
    ];

    const TEMPERATURE_LEVELS = [
        'Cool', 'Moderate', 'Hot',
    ];

    const HUMIDITY_LEVELS = [
        'Low', 'Medium', 'High',
    ];

    const WIND_INTENSITIES = [
        'Calm', 'Light Wind', 'Strong Wind',
    ];

    const POPULATION_DENSITIES = [
        'Good', 'Moderate', 'Poor',
    ];

    const LEAF_COLOURS = [
        'Dark Green', 'Light Green', 'Yellow', 'Brown', 'Mixed',
    ];

    const LEAF_CONDITIONS = [
        'Healthy', 'Wilting', 'Spotted', 'Burnt', 'Torn',
    ];

    const STEM_CONDITIONS = [
        'Healthy', 'Weak', 'Rotten', 'Broken', 'Lodged',
    ];

    const ROOT_CONDITIONS = [
        'Healthy', 'Shallow', 'Rotting', 'Stunted',
    ];

    const FLOWERING_STATUSES = [
        'No Flowering', 'Flowering', 'Post-Flowering',
    ];

    const FRUIT_GRAIN_FORMATIONS = [
        'None', 'Early Stage', 'Good Formation', 'Poor Formation',
    ];

    const CROP_VIGORS = [
        'Excellent', 'Good', 'Moderate', 'Poor',
    ];

    const PEST_LEVELS = ['None', 'Low', 'Medium', 'High'];
    const DISEASE_LEVELS = ['None', 'Low', 'Medium', 'High'];
    const BENEFICIAL_LEVELS = ['None', 'Few', 'Moderate', 'Many'];

    const SOIL_CONDITIONS = [
        'Good', 'Moderate', 'Poor',
    ];

    const SOIL_FERTILITY_STATUSES = [
        'High', 'Medium', 'Low',
    ];

    const SOIL_EROSION_SIGNS = [
        'None', 'Slight', 'Moderate', 'Severe',
    ];

    const WEED_LEVELS = ['None', 'Low', 'Medium', 'High'];

    const CROP_RESIDUE_COVERS = [
        'None', 'Partial', 'Full',
    ];

    const WATER_DRAINAGES = [
        'Well Drained', 'Moderately Drained', 'Waterlogged',
    ];

    const MAIN_PROBLEMS = [
        'Pest Infestation', 'Disease Outbreak', 'Poor Soil Fertility',
        'Weed Competition', 'Water Stress', 'Nutrient Deficiency',
        'Poor Stand Establishment', 'Extreme Weather',
    ];

    const CAUSES_OF_PROBLEM = [
        'Environmental', 'Disease', 'Pest', 'Poor Management Practice',
        'Soil Degradation', 'Water Shortage',
    ];

    const RISK_LEVELS = ['Low', 'Medium', 'High'];

    const IMMEDIATE_ACTIONS = [
        'Apply Pesticide', 'Apply Fungicide', 'Remove Infected Plants',
        'Irrigate Crop', 'Apply Fertilizer', 'Weed Field',
    ];

    const SOIL_MANAGEMENT_ACTIONS = [
        'Apply Mulch', 'Add Compost/Manure', 'Apply Inorganic Fertilizer',
        'Terrace Field', 'Improve Drainage', 'Contour Ploughing',
    ];

    const PREVENTIVE_ACTIONS = [
        'Crop Rotation', 'Use Resistant Varieties', 'Timely Planting',
        'Integrated Pest Management', 'Soil Conservation Practices',
    ];

    const MONITORING_PLANS = [
        'Daily Observation', 'Weekly Monitoring', 'Monthly Monitoring',
        'Agronomist Follow-Up',
    ];

    const RESPONSIBLE_PERSONS = [
        'Farmer', 'Facilitator', 'Extension Officer', 'Agronomist',
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

    public function farmer()
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // ── Accessors ────────────────────────────────────

    public function getCropTypeDisplayAttribute(): ?string
    {
        if ($this->crop_type === 'Other' && $this->crop_type_other) {
            return $this->crop_type_other;
        }
        return $this->crop_type;
    }

    public function getFarmerDisplayAttribute(): ?string
    {
        if ($this->farmer) {
            return trim($this->farmer->first_name . ' ' . $this->farmer->last_name);
        }
        return $this->farmer_name;
    }

    public function getRiskLevelDisplayAttribute(): ?string
    {
        return $this->risk_level ? ucfirst($this->risk_level) : null;
    }

    /**
     * Calculate crop health score (0-100). Higher = healthier.
     * Deductions based on vigor, leaf/stem/root condition, pest/disease pressure.
     */
    public function getCropHealthScoreAttribute(): int
    {
        $score = 100;
        $deductions = 0;

        // Crop vigor
        if ($this->crop_vigor === 'Moderate') $deductions += 10;
        if ($this->crop_vigor === 'Poor')     $deductions += 25;

        // Leaf condition
        if ($this->leaf_condition === 'Wilting') $deductions += 10;
        if ($this->leaf_condition === 'Spotted')  $deductions += 15;
        if ($this->leaf_condition === 'Burnt')    $deductions += 20;

        // Stem / Root
        if ($this->stem_condition === 'Weak')    $deductions += 8;
        if ($this->stem_condition === 'Rotten')  $deductions += 20;
        if ($this->root_condition === 'Rotting') $deductions += 20;

        // Pest pressure
        $pestLevel = $this->getPestPressureLevelAttribute();
        if ($pestLevel === 'Low')    $deductions += 5;
        if ($pestLevel === 'Medium') $deductions += 12;
        if ($pestLevel === 'High')   $deductions += 22;

        // Disease pressure
        $diseaseLevel = $this->getDiseasePressureLevelAttribute();
        if ($diseaseLevel === 'Low')    $deductions += 5;
        if ($diseaseLevel === 'Medium') $deductions += 15;
        if ($diseaseLevel === 'High')   $deductions += 25;

        return max(0, $score - $deductions);
    }

    /**
     * Return the highest pest pressure level across all pest fields.
     */
    public function getPestPressureLevelAttribute(): string
    {
        $fields = [
            'aphids_level', 'caterpillars_armyworms_level', 'beetles_level',
            'grasshoppers_level', 'whiteflies_level', 'other_insect_pests_level',
        ];
        return $this->_maxLevel($fields);
    }

    /**
     * Return the highest disease pressure level across all disease fields.
     */
    public function getDiseasePressureLevelAttribute(): string
    {
        $fields = [
            'leaf_spot_level', 'blight_level', 'rust_level',
            'wilt_level', 'mosaic_virus_level', 'other_diseases_level',
        ];
        return $this->_maxLevel($fields);
    }

    private function _maxLevel(array $fields): string
    {
        $order = ['None' => 0, 'Low' => 1, 'Medium' => 2, 'High' => 3];
        $max   = 'None';
        foreach ($fields as $f) {
            $v = $this->$f ?? 'None';
            if (($order[$v] ?? 0) > ($order[$max] ?? 0)) {
                $max = $v;
            }
        }
        return $max;
    }

    /**
     * Get a display summary for the observation.
     */
    public function getDisplayNameAttribute(): string
    {
        $crop = $this->crop_type_display ?? 'Crop Observation';
        $plot = $this->plot_id ? " ({$this->plot_id})" : '';
        return $crop . $plot;
    }

    // ── Scopes ───────────────────────────────────────

    public function scopeByCropType($query, $type)
    {
        return $query->where('crop_type', $type);
    }

    public function scopeByRiskLevel($query, $level)
    {
        return $query->where('risk_level', $level);
    }

    // ── Static Helpers ───────────────────────────────

    public static function getCropDropdownOptions(): array
    {
        return [
            'crop_types'             => self::CROP_TYPES,
            'cropping_systems'       => self::CROPPING_SYSTEMS,
            'planting_methods'       => self::PLANTING_METHODS,
            'growth_stages'          => self::GROWTH_STAGES,
            'irrigation_methods'     => self::IRRIGATION_METHODS,
            'crop_weather_conditions'  => self::WEATHER_CONDITIONS,
            'crop_temperature_levels'  => self::TEMPERATURE_LEVELS,
            'crop_humidity_levels'     => self::HUMIDITY_LEVELS,
            'crop_wind_intensities'    => self::WIND_INTENSITIES,
            'population_densities'   => self::POPULATION_DENSITIES,
            'leaf_colours'           => self::LEAF_COLOURS,
            'leaf_conditions'        => self::LEAF_CONDITIONS,
            'stem_conditions'        => self::STEM_CONDITIONS,
            'root_conditions'        => self::ROOT_CONDITIONS,
            'flowering_statuses'     => self::FLOWERING_STATUSES,
            'fruit_grain_formations' => self::FRUIT_GRAIN_FORMATIONS,
            'crop_vigors'            => self::CROP_VIGORS,
            'pest_levels'            => self::PEST_LEVELS,
            'disease_levels'         => self::DISEASE_LEVELS,
            'beneficial_levels'      => self::BENEFICIAL_LEVELS,
            'soil_conditions'        => self::SOIL_CONDITIONS,
            'soil_fertility_statuses' => self::SOIL_FERTILITY_STATUSES,
            'soil_erosion_signs'     => self::SOIL_EROSION_SIGNS,
            'weed_levels'            => self::WEED_LEVELS,
            'crop_residue_covers'    => self::CROP_RESIDUE_COVERS,
            'water_drainages'        => self::WATER_DRAINAGES,
            'crop_main_problems'     => self::MAIN_PROBLEMS,
            'crop_causes_of_problem' => self::CAUSES_OF_PROBLEM,
            'crop_risk_levels'       => self::RISK_LEVELS,
            'crop_immediate_actions' => self::IMMEDIATE_ACTIONS,
            'soil_management_actions' => self::SOIL_MANAGEMENT_ACTIONS,
            'crop_preventive_actions' => self::PREVENTIVE_ACTIONS,
            'crop_monitoring_plans'  => self::MONITORING_PLANS,
            'crop_responsible_persons' => self::RESPONSIBLE_PERSONS,
        ];
    }
}
