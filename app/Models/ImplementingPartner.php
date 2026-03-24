<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ImplementingPartner extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'implementing_partners';

    protected $fillable = [
        'name',
        'short_name',
        'slug',
        'description',
        'logo',
        'loa',
        'project_code',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'region',
        'districts',
        'status',
        'start_date',
        'end_date',
        'kpi_target_facilitators',
        'kpi_target_groups',
        'kpi_target_trainings_per_week',
        'kpi_target_meetings_per_week',
        'kpi_target_members',
        'created_by_id',
    ];

    protected $casts = [
        'districts'  => 'array',
        'start_date' => 'date',
        'end_date'   => 'date',
        'kpi_target_facilitators'       => 'integer',
        'kpi_target_groups'             => 'integer',
        'kpi_target_trainings_per_week' => 'integer',
        'kpi_target_meetings_per_week'  => 'integer',
        'kpi_target_members'            => 'integer',
    ];

    // ── Constants ────────────────────────────────────
    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE   => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
        ];
    }

    // ── Boot ─────────────────────────────────────────
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ip) {
            if (empty($ip->slug) && !empty($ip->name)) {
                $ip->slug = Str::slug($ip->name);
            }
            if (empty($ip->short_name) && !empty($ip->name)) {
                $ip->short_name = strtoupper(Str::slug($ip->name, ''));
            }
        });
    }

    // ── Scopes ───────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // ── Relationships ────────────────────────────────
    public function users()
    {
        return $this->hasMany(User::class, 'ip_id');
    }

    public function groups()
    {
        return $this->hasMany(FfsGroup::class, 'ip_id');
    }

    public function trainingSessions()
    {
        return $this->hasMany(FfsTrainingSession::class, 'ip_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // ── Helpers ──────────────────────────────────────

    /** Quick stats for dashboard cards */
    public function getStatsAttribute(): array
    {
        return [
            'users'    => $this->users()->count(),
            'groups'   => $this->groups()->count(),
            'sessions' => $this->trainingSessions()->count(),
        ];
    }

    /** Selector array for dropdowns: [ id => "NAME (SHORT)" ] */
    public static function getDropdownOptions(): array
    {
        return self::active()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($ip) => [
                $ip->id => $ip->short_name
                    ? "{$ip->name} ({$ip->short_name})"
                    : $ip->name,
            ])
            ->toArray();
    }

    use \App\Traits\TitleCase;

    // ── Title Case accessors & mutators ──────────────────────────────────────

    public function getNameAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value !== null ? $this->toTitleCase($value) : null;
    }

    public function getContactPersonAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setContactPersonAttribute($value): void
    {
        $this->attributes['contact_person'] = $value !== null ? $this->toTitleCase($value) : null;
    }
}
