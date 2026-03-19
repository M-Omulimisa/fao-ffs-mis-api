<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FfsKpiFacilitatorEntry extends Model
{
    protected $table = 'ffs_kpi_facilitator_entries';

    protected $guarded = [];

    protected $casts = [
        'session_date' => 'date',
        'value'        => 'float',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function ip()
    {
        return $this->belongsTo(ImplementingPartner::class, 'ip_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function indicator()
    {
        return $this->belongsTo(FfsKpiIndicator::class, 'indicator_id');
    }

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    // ── Scope ─────────────────────────────────────────────────────────────

    public function scopeForIp($query, int $ipId)
    {
        return $query->where('ip_id', $ipId);
    }
}
