<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FfsGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ffs_groups';

    protected $fillable = [
        'ip_id',
        'name',
        'type',
        'code',
        'loa',
        'ip_name',
        'project_code',
        'registration_date',
        'establishment_date',
        'district_id',
        'district_text',
        'subcounty_id',
        'parish_id',
        'village',
        'subcounty_text',
        'parish_text',
        'meeting_venue',
        'meeting_day',
        'meeting_frequency',
        'primary_value_chain',
        'secondary_value_chains',
        'total_members',
        'male_members',
        'female_members',
        'youth_members',
        'pwd_members',
        'pwd_male_members',
        'pwd_female_members',
        'estimated_members',
        'facilitator_id',
        'admin_id',
        'secretary_id',
        'treasurer_id',
        'contact_person_name',
        'contact_person_phone',
        'facilitator_sex',
        'latitude',
        'longitude',
        'status',
        'cycle_number',
        'cycle_start_date',
        'cycle_end_date',
        'description',
        'objectives',
        'achievements',
        'challenges',
        'photo',
        'source_file',
        'original_id',
        'created_by_id',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'establishment_date' => 'date',
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'total_members' => 'integer',
        'male_members' => 'integer',
        'female_members' => 'integer',
        'youth_members' => 'integer',
        'pwd_members' => 'integer',
        'estimated_members' => 'integer',
        'cycle_number' => 'integer',
        'secondary_value_chains' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = [
        'type_text',
        'status_text',
        'district_name',
        'facilitator_name',
    ];

    // Group Types
    const TYPE_FFS = 'FFS'; // Farmer Field School
    const TYPE_FBS = 'FBS'; // Farmer Business School
    const TYPE_VSLA = 'VSLA'; // Village Savings and Loan Association
    const TYPE_ASSOCIATION = 'Association'; // Group Association

    // Group Status
    const STATUS_ACTIVE = 'Active';
    const STATUS_INACTIVE = 'Inactive';
    const STATUS_SUSPENDED = 'Suspended';
    const STATUS_GRADUATED = 'Graduated';

    // Meeting Frequencies
    const FREQUENCY_WEEKLY = 'Weekly';
    const FREQUENCY_BIWEEKLY = 'Bi-weekly';
    const FREQUENCY_MONTHLY = 'Monthly';

    /**
     * Get available group types
     */
    public static function getTypes()
    {
        return [
            self::TYPE_FFS => 'Farmer Field School (FFS)',
            self::TYPE_FBS => 'Farmer Business School (FBS)',
            self::TYPE_VSLA => 'Village Savings & Loan Association (VSLA)',
            self::TYPE_ASSOCIATION => 'Group Association',
        ];
    }

    /**
     * Get available statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_GRADUATED => 'Graduated',
        ];
    }

    /**
     * Get available meeting frequencies
     */
    public static function getMeetingFrequencies()
    {
        return [
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_BIWEEKLY => 'Bi-weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
        ];
    }

    /**
     * Relationships
     */
    
    public function implementingPartner()
    {
        return $this->belongsTo(ImplementingPartner::class, 'ip_id');
    }

    public function district()
    {
        return $this->belongsTo(Location::class, 'district_id');
    }

    public function subcounty()
    {
        return $this->belongsTo(Location::class, 'subcounty_id');
    }

    public function parish()
    {
        return $this->belongsTo(Location::class, 'parish_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function secretary()
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function treasurer()
    {
        return $this->belongsTo(User::class, 'treasurer_id');
    }

    // VSLA Relationships
    public function vslaMeetings()
    {
        return $this->hasMany(VslaMeeting::class, 'group_id');
    }

    public function vslaLoans()
    {
        // vsla_loans has no group_id; loans belong to a cycle (project) which belongs to a group
        return $this->hasManyThrough(VslaLoan::class, \App\Models\Project::class, 'group_id', 'cycle_id');
    }

    public function vslaActionPlans()
    {
        return $this->hasMany(VslaActionPlan::class, 'group_id');
    }

    // Users (members) belonging to this group
    public function users()
    {
        return $this->hasMany(User::class, 'group_id');
    }

    // Alias for users
    public function members()
    {
        return $this->users();
    }

    // Training sessions targeting this group (many-to-many)
    public function trainingSessions()
    {
        return $this->belongsToMany(
            FfsTrainingSession::class,
            'ffs_session_target_groups',
            'group_id',
            'session_id'
        )->withTimestamps();
    }

    // TODO: Uncomment when these models are created
    // public function vslaRecords()
    // {
    //     return $this->hasMany(VslaRecord::class, 'group_id');
    // }

    // public function aesaRecords()
    // {
    //     return $this->hasMany(AesaRecord::class, 'group_id');
    // }

    /**
     * Accessors
     */
    
    public function getTypeTextAttribute()
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
    }

    public function getStatusTextAttribute()
    {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? $this->status;
    }

    public function getDistrictNameAttribute()
    {
        return $this->district ? $this->district->name : 'N/A';
    }

    public function getFacilitatorNameAttribute()
    {
        return $this->facilitator ? $this->facilitator->name : 'Not Assigned';
    }

    public function getGenderBalanceAttribute()
    {
        if ($this->total_members == 0) {
            return ['male' => 0, 'female' => 0];
        }

        return [
            'male' => round(($this->male_members / $this->total_members) * 100, 1),
            'female' => round(($this->female_members / $this->total_members) * 100, 1),
        ];
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeFfs($query)
    {
        return $query->where('type', self::TYPE_FFS);
    }

    public function scopeFbs($query)
    {
        return $query->where('type', self::TYPE_FBS);
    }

    public function scopeVsla($query)
    {
        return $query->where('type', self::TYPE_VSLA);
    }

    public function scopeAssociation($query)
    {
        return $query->where('type', self::TYPE_ASSOCIATION);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            // Auto-generate group code if not provided
            if (empty($group->code)) {
                $group->code = self::generateGroupCode(
                    $group->type ?? 'VSLA',
                    $group->district_id
                );
            }

            // Final safety: if the code was set by a controller but already exists
            // in the DB (race condition between generate and save), regenerate now
            // IMPORTANT: withTrashed() because the unique constraint covers soft-deleted rows
            if (self::withTrashed()->where('code', $group->code)->exists()) {
                \Log::warning("FfsGroup: code [{$group->code}] already exists, regenerating...");
                // Determine typeCode from existing code pattern (e.g. 'VSLA' from 'MOR-VSLA-26-0001')
                $typeCode = null;
                if (preg_match('/^[A-Z]{3}-([A-Z]+)-\d{2}-\d{4}$/', $group->code, $m)) {
                    $typeCode = $m[1];
                }
                $group->code = self::generateGroupCode(
                    $group->type ?? 'VSLA',
                    $group->district_id,
                    $typeCode
                );
            }

            // Default meeting_frequency to 'Weekly' if null/empty (enum column rejects null)
            if (empty($group->meeting_frequency)) {
                $group->meeting_frequency = 'Weekly';
            }

            // Set created_by_id if not set
            if (empty($group->created_by_id) && auth()->check()) {
                $group->created_by_id = auth()->id();
            }

            // Auto-assign ip_id from current admin user if not set
            if (empty($group->ip_id)) {
                try {
                    $adminUser = \Encore\Admin\Facades\Admin::user();
                    if ($adminUser && $adminUser->ip_id) {
                        $group->ip_id = $adminUser->ip_id;
                    }
                } catch (\Throwable $e) {
                    // Admin facade may not be available (e.g. API context)
                }
            }
        });

        // Auto-inherit IP from facilitator only when ip_id was not explicitly changed
        static::saving(function ($group) {
            self::normalizeCaseFields($group);

            if ($group->isDirty('facilitator_id') && $group->facilitator_id && !$group->isDirty('ip_id')) {
                $facilitator = \DB::table('users')->where('id', $group->facilitator_id)->first();
                if ($facilitator && $facilitator->ip_id) {
                    $group->ip_id = $facilitator->ip_id;
                }
            }
        });

        // Cascade delete group data when a group is deleted
        // Users/members are intentionally preserved (only unlinked)
        static::deleting(function ($group) {
            $id = $group->id;

            // ── 1. Gather IDs ────────────────────────────────────────────
            $cycleIds = \DB::table('projects')
                ->where('group_id', $id)
                ->pluck('id');

            $meetingIds = \DB::table('vsla_meetings')
                ->where('group_id', $id)
                ->pluck('id');

            $loanIds = $cycleIds->isNotEmpty()
                ? \DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->pluck('id')
                : collect();

            $shareoutIds = \DB::table('vsla_shareouts')
                ->where('group_id', $id)
                ->pluck('id');

            $openingBalanceIds = \DB::table('vsla_opening_balances')
                ->where('group_id', $id)
                ->pluck('id');

            $aesaSessionIds = \DB::table('aesa_sessions')
                ->where('group_id', $id)
                ->pluck('id');

            // ── 2. Delete deepest children first (FK order) ──────────────

            // Loan transactions → loans
            if ($loanIds->isNotEmpty()) {
                \DB::table('loan_transactions')->whereIn('loan_id', $loanIds)->delete();
            }
            if ($cycleIds->isNotEmpty()) {
                \DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->delete();
            }

            // Project transactions & shares
            if ($cycleIds->isNotEmpty()) {
                \DB::table('project_transactions')->whereIn('project_id', $cycleIds)->delete();
                \DB::table('project_shares')->whereIn('project_id', $cycleIds)->delete();
            }

            // Shareout distributions → shareouts
            if ($shareoutIds->isNotEmpty()) {
                \DB::table('vsla_shareout_distributions')->whereIn('shareout_id', $shareoutIds)->delete();
            }
            \DB::table('vsla_shareouts')->where('group_id', $id)->delete();

            // Action plans (linked via meeting_id and/or cycle_id)
            if ($meetingIds->isNotEmpty()) {
                \DB::table('vsla_action_plans')->whereIn('meeting_id', $meetingIds)->delete();
            }
            if ($cycleIds->isNotEmpty()) {
                \DB::table('vsla_action_plans')->whereIn('cycle_id', $cycleIds)->delete();
            }

            // Meeting attendance → meetings
            if ($meetingIds->isNotEmpty()) {
                \DB::table('vsla_meeting_attendance')->whereIn('meeting_id', $meetingIds)->delete();
            }
            \DB::table('vsla_meetings')->where('group_id', $id)->delete();

            // Social fund & account transactions
            \DB::table('social_fund_transactions')->where('group_id', $id)->delete();
            \DB::table('account_transactions')->where('group_id', $id)->delete();

            // Opening balance members → opening balances
            if ($openingBalanceIds->isNotEmpty()) {
                \DB::table('vsla_opening_balance_members')->whereIn('opening_balance_id', $openingBalanceIds)->delete();
            }
            \DB::table('vsla_opening_balances')->where('group_id', $id)->delete();

            // VSLA profiles
            \DB::table('vsla_profiles')->where('group_id', $id)->delete();

            // AESA observations → sessions
            if ($aesaSessionIds->isNotEmpty()) {
                \DB::table('aesa_observations')->whereIn('aesa_session_id', $aesaSessionIds)->delete();
                \DB::table('aesa_crop_observations')->whereIn('aesa_session_id', $aesaSessionIds)->delete();
            }
            \DB::table('aesa_sessions')->where('group_id', $id)->delete();

            // Training session pivot & KPI entries
            \DB::table('ffs_session_target_groups')->where('group_id', $id)->delete();
            \DB::table('ffs_kpi_ip_entries')->where('group_id', $id)->delete();
            \DB::table('ffs_kpi_facilitator_entries')->where('group_id', $id)->delete();

            // Delete cycles
            if ($cycleIds->isNotEmpty()) {
                \DB::table('projects')->whereIn('id', $cycleIds)->delete();
            }

            // ── 3. Delete all group members ──────────────────────────────
            \DB::table('users')->where('group_id', $id)->delete();
        });
    }

    /**
     * Normalize casing policy for group write-paths.
     */
    protected static function normalizeCaseFields($group): void
    {
        $group->name = $group->name !== null ? mb_strtoupper(trim($group->name)) : null;

        foreach (['contact_person_name', 'ip_name', 'subcounty_text', 'parish_text', 'village'] as $field) {
            if ($group->$field !== null) {
                $group->$field = ucwords(mb_strtolower(trim($group->$field)));
            }
        }
    }

    /**
     * Generate a guaranteed-unique group code.
     *
     * Strategy:
     *  1. Find the highest existing sequence number for this prefix via ORDER BY DESC.
     *  2. Start from max+1 and loop until the code doesn't exist in the DB.
     *  3. Up to 20 attempts — more than enough to skip any gaps or race-condition leftovers.
     *
     * @param  string      $type        Group type, e.g. 'VSLA', 'FFS'
     * @param  int|null    $districtId  District ID (for the 3-letter prefix)
     * @param  string|null $typeCode    Optional override for the type portion (e.g. 'VSLA' instead of 'VSL')
     * @return string
     */
    public static function generateGroupCode($type, $districtId, ?string $typeCode = null)
    {
        $district = Location::find($districtId);
        $districtCode = $district ? strtoupper(substr($district->name, 0, 3)) : 'XXX';

        // Default type code: first 3 chars of type (VSL, FFS, etc.)
        // Controllers can pass 'VSLA' explicitly to get the 4-letter form
        $typeCode = $typeCode ?? strtoupper(substr($type, 0, 3));
        $year = date('y');
        $prefix = "$districtCode-$typeCode-$year-";

        // Find the highest sequence number for this prefix
        // IMPORTANT: use withTrashed() because the DB unique constraint covers soft-deleted rows too
        $lastGroup = self::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastGroup && preg_match('/-(\d{4})$/', $lastGroup->code, $m)) {
            $nextNumber = intval($m[1]) + 1;
        }

        // Loop until we find a code that doesn't exist (including soft-deleted rows)
        $attempts = 0;
        $code = sprintf('%s%04d', $prefix, $nextNumber);
        while (self::withTrashed()->where('code', $code)->exists() && $attempts < 20) {
            $nextNumber++;
            $code = sprintf('%s%04d', $prefix, $nextNumber);
            $attempts++;
        }

        return $code;
    }

    /**
     * Calculate member counts from related members
     * TODO: Uncomment when members() relationship is available
     */
    // public function calculateMemberCounts()
    // {
    //     $this->total_members = $this->members()->count();
    //     $this->male_members = $this->members()->where('gender', 'Male')->count();
    //     $this->female_members = $this->members()->where('gender', 'Female')->count();
    //     $this->youth_members = $this->members()->where('is_youth', true)->count();
    //     $this->pwd_members = $this->members()->where('has_disability', true)->count();
    // }

    use \App\Traits\TitleCase;

    // ── Title Case accessors & mutators ──────────────────────────────────────

    public function getNameAttribute($value): ?string
    {
        return $value !== null ? $this->toUpperCase($value) : null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value !== null ? $this->toUpperCase($value) : null;
    }

    public function getContactPersonNameAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setContactPersonNameAttribute($value): void
    {
        $this->attributes['contact_person_name'] = $value !== null ? $this->toTitleCase($value) : null;
    }

    public function getIpNameAttribute($value): ?string
    {
        return $value !== null ? $this->toTitleCase($value) : null;
    }

    public function setIpNameAttribute($value): void
    {
        $this->attributes['ip_name'] = $value !== null ? $this->toTitleCase($value) : null;
    }
}
