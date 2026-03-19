<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FfsKpiIndicator extends Model
{
    protected $table = 'ffs_kpi_indicators';

    protected $guarded = [];

    protected $casts = [
        'possible_disaggregations' => 'array',
        'default_target'           => 'float',
    ];

    // ── Location config constants ─────────────────────────────────────────
    const LOCATION_GROUP       = 'group';
    const LOCATION_INSTITUTION = 'institution';
    const LOCATION_TYPE        = 'location_type';
    const LOCATION_DISTRICT    = 'district_only';

    // ── Scopes ────────────────────────────────────────────────────────────

    public static function ipIndicators()
    {
        return static::where('type', 'ip')->orderBy('sort_order');
    }

    public static function facilitatorIndicators()
    {
        return static::where('type', 'facilitator')->orderBy('sort_order');
    }

    /**
     * Returns all indicators as an optgroup-friendly array for select dropdowns.
     * Format: [ id => "Output N — Indicator Name" ]
     */
    public static function indicatorOptionsForType(string $type): array
    {
        return static::where('type', $type)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($i) => [$i->id => "Output {$i->output_number} — {$i->indicator_name}"])
            ->toArray();
    }

    /**
     * Returns disaggregations and location_config keyed by indicator ID,
     * for injection into JavaScript.
     */
    public static function asJsData(string $type = null): string
    {
        $query = static::orderBy('sort_order');
        if ($type) {
            $query->where('type', $type);
        }
        $data = $query->get()->mapWithKeys(fn($i) => [$i->id => [
            'disaggregations' => $i->possible_disaggregations,
            'location_config' => $i->location_config,
        ]]);
        return json_encode($data);
    }
}
