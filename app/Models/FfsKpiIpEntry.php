<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FfsKpiIpEntry extends Model
{
    protected $table = 'ffs_kpi_ip_entries';

    protected $guarded = [];

    protected $appends = ['overall', 'performance_pct', 'variance', 'location_display'];

    // ── Relationships ─────────────────────────────────────────────────────

    public function ip()
    {
        return $this->belongsTo(ImplementingPartner::class, 'ip_id');
    }

    public function indicator()
    {
        return $this->belongsTo(FfsKpiIndicator::class, 'indicator_id');
    }

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    // ── Computed Accessors ────────────────────────────────────────────────

    /**
     * Sum of all non-null monthly values.
     */
    public function getOverallAttribute(): float
    {
        $months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
        $total  = 0;
        foreach ($months as $m) {
            if ($this->attributes[$m] !== null) {
                $total += (float) $this->attributes[$m];
            }
        }
        return round($total, 2);
    }

    /**
     * Overall achieved as a percentage of target.
     */
    public function getPerformancePctAttribute(): float
    {
        if ((float) $this->target <= 0) return 0;
        return round($this->overall / (float) $this->target * 100, 1);
    }

    /**
     * Target minus Overall (positive = shortfall, negative = exceeded).
     */
    public function getVarianceAttribute(): float
    {
        return round((float) $this->target - $this->overall, 2);
    }

    /**
     * Human-readable location summary based on location_config.
     */
    public function getLocationDisplayAttribute(): string
    {
        $parts = array_filter([
            $this->district,
            $this->sub_county,
        ]);
        $base = implode(', ', $parts) ?: '—';

        if ($this->group_id && $this->relationLoaded('group') && $this->group) {
            return $base . ' · ' . $this->group->name;
        }
        if ($this->institution) {
            return $base . ' · ' . $this->institution;
        }
        if ($this->location_type) {
            return $base . ' · ' . $this->location_type;
        }
        return $base;
    }

    // ── Performance label helper ───────────────────────────────────────────

    public static function performanceLabel(float $pct): string
    {
        if ($pct >= 100) return 'Exceeding';
        if ($pct >= 85)  return 'On Track';
        if ($pct >= 70)  return 'Slightly Behind';
        return 'Needs Attention';
    }

    public static function performanceColor(float $pct): string
    {
        if ($pct >= 100) return '#1565c0';
        if ($pct >= 85)  return '#4caf50';
        if ($pct >= 70)  return '#ff9800';
        return '#f44336';
    }

    // ── Scope ─────────────────────────────────────────────────────────────

    public function scopeForIp($query, int $ipId)
    {
        return $query->where('ip_id', $ipId);
    }
}
