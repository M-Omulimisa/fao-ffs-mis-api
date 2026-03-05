<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AesaSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aesa_sessions';

    protected $fillable = [
        'data_sheet_number',
        'group_id',
        'group_name_other',
        'district_id',
        'district_text',
        'sub_county_id',
        'sub_county_text',
        'village_id',
        'village_text',
        'observation_date',
        'observation_time',
        'facilitator_id',
        'facilitator_name',
        'mini_group_name',
        'observation_location',
        'observation_location_other',
        'gps_latitude',
        'gps_longitude',
        'status',
        'ip_id',
        'created_by_id',
    ];

    protected $casts = [
        'observation_date' => 'date',
        'gps_latitude' => 'decimal:7',
        'gps_longitude' => 'decimal:7',
    ];

    protected $appends = [
        'status_text',
        'group_name_display',
        'facilitator_name_display',
        'district_name_display',
        'sub_county_name_display',
        'village_name_display',
        'location_display',
        'observations_count',
        'formatted_date',
        'formatted_time',
    ];

    // ── Constants ────────────────────────────────────

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVIEWED = 'reviewed';

    const OBSERVATION_LOCATIONS = [
        'Farm',
        'Grazing Field',
        'Livestock Shelter',
        'Market',
    ];

    const MINI_GROUPS = [
        'Mini-Group A',
        'Mini-Group B',
        'Mini-Group C',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_REVIEWED => 'Reviewed',
        ];
    }

    // ── Boot ─────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate data sheet number
            if (empty($model->data_sheet_number)) {
                $model->data_sheet_number = self::generateDataSheetNumber($model->observation_date);
            }

            // Inherit ip_id from group if available
            if (empty($model->ip_id) && $model->group_id) {
                $group = FfsGroup::find($model->group_id);
                if ($group && $group->ip_id) {
                    $model->ip_id = $group->ip_id;
                }
            }

            // Try to get ip_id from the authenticated user
            if (empty($model->ip_id)) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user && isset($user->ip_id)) {
                    $model->ip_id = $user->ip_id;
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

    /**
     * Generate a unique data sheet number in format: AESA-YYYYMMDD-NNN
     */
    public static function generateDataSheetNumber($date = null): string
    {
        $dateStr = $date 
            ? Carbon::parse($date)->format('Ymd') 
            : Carbon::now()->format('Ymd');
        
        $lastSession = self::where('data_sheet_number', 'like', "AESA-{$dateStr}-%")
            ->orderBy('data_sheet_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastSession) {
            $parts = explode('-', $lastSession->data_sheet_number);
            $lastSeq = end($parts);
            $sequence = intval($lastSeq) + 1;
        }

        return sprintf('AESA-%s-%03d', $dateStr, $sequence);
    }

    // ── Relationships ────────────────────────────────

    public function observations()
    {
        return $this->hasMany(AesaObservation::class, 'aesa_session_id');
    }

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function district()
    {
        return $this->belongsTo(Location::class, 'district_id');
    }

    public function subCounty()
    {
        return $this->belongsTo(Location::class, 'sub_county_id');
    }

    public function village()
    {
        return $this->belongsTo(Location::class, 'village_id');
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

    public function getStatusTextAttribute(): string
    {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getGroupNameDisplayAttribute(): ?string
    {
        if ($this->group) {
            return $this->group->name;
        }
        return $this->group_name_other;
    }

    public function getFacilitatorNameDisplayAttribute(): ?string
    {
        if ($this->facilitator) {
            return trim($this->facilitator->first_name . ' ' . $this->facilitator->last_name);
        }
        return $this->facilitator_name;
    }

    public function getDistrictNameDisplayAttribute(): ?string
    {
        if ($this->district) {
            return $this->district->name;
        }
        return $this->district_text;
    }

    public function getSubCountyNameDisplayAttribute(): ?string
    {
        if ($this->subCounty) {
            return $this->subCounty->name;
        }
        return $this->sub_county_text;
    }

    public function getVillageNameDisplayAttribute(): ?string
    {
        if ($this->village) {
            return $this->village->name;
        }
        return $this->village_text;
    }

    public function getLocationDisplayAttribute(): ?string
    {
        if ($this->observation_location === 'Other' && $this->observation_location_other) {
            return $this->observation_location_other;
        }
        return $this->observation_location;
    }

    public function getObservationsCountAttribute(): int
    {
        return $this->observations()->count();
    }

    public function getFormattedDateAttribute(): ?string
    {
        return $this->observation_date 
            ? Carbon::parse($this->observation_date)->format('d/m/Y') 
            : null;
    }

    public function getFormattedTimeAttribute(): ?string
    {
        return $this->observation_time 
            ? Carbon::parse($this->observation_time)->format('h:i A') 
            : null;
    }

    // ── Scopes ───────────────────────────────────────

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeByFacilitator($query, $facilitatorId)
    {
        return $query->where('facilitator_id', $facilitatorId);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('observation_date', [$from, $to]);
    }

    public function scopeByIp($query, $ipId)
    {
        return $query->where('ip_id', $ipId);
    }
}
