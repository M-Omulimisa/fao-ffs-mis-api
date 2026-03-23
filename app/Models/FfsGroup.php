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
                $group->code = self::generateGroupCode($group->type, $group->district_id);
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

        // Commenting out updating hook until members() relationship is available
        // static::updating(function ($group) {
        //     // Update member counts automatically
        //     $group->calculateMemberCounts();
        // });
        
        // Cascade delete group data when a group is deleted
        // Users/members are intentionally preserved (only unlinked)
        static::deleting(function ($group) {
            $id = $group->id;

            // Unlink members (keep the user accounts, just remove group association)
            \DB::table('users')->where('group_id', $id)->update(['group_id' => null]);

            // Delete action plans linked to meetings before meetings are removed
            $meetingIds = \DB::table('vsla_meetings')->where('group_id', $id)->pluck('id');
            if ($meetingIds->isNotEmpty()) {
                \DB::table('vsla_action_plans')->whereIn('meeting_id', $meetingIds)->delete();
            }

            // Delete VSLA meetings
            \DB::table('vsla_meetings')->where('group_id', $id)->delete();

            // Delete cycles (projects) and their related data
            // vsla_loans link to a group via cycle_id (not group_id), so handle them here
            $cycleIds = \DB::table('projects')->where('group_id', $id)->pluck('id');
            if ($cycleIds->isNotEmpty()) {
                // Delete loan transactions first (FK child of vsla_loans)
                $loanIds = \DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->pluck('id');
                if ($loanIds->isNotEmpty()) {
                    \DB::table('loan_transactions')->whereIn('loan_id', $loanIds)->delete();
                }
                \DB::table('vsla_loans')->whereIn('cycle_id', $cycleIds)->delete();

                \DB::table('project_transactions')->whereIn('project_id', $cycleIds)->delete();
                \DB::table('project_shares')->whereIn('project_id', $cycleIds)->delete();
                \DB::table('vsla_shareouts')->whereIn('cycle_id', $cycleIds)->delete();

                // Delete action plans linked to cycles
                \DB::table('vsla_action_plans')->whereIn('cycle_id', $cycleIds)->delete();
            }
            \DB::table('projects')->where('group_id', $id)->delete();

            // Delete account transactions owned by this group
            \DB::table('account_transactions')
                ->where('owner_type', 'group')
                ->where('group_id', $id)
                ->delete();

            // Delete social fund transactions
            \DB::table('social_fund_transactions')->where('group_id', $id)->delete();

            // Delete VSLA profiles and opening balances
            \DB::table('vsla_profiles')->where('group_id', $id)->delete();
            \DB::table('vsla_opening_balances')->where('group_id', $id)->delete();

            // Delete AESA sessions
            \DB::table('aesa_sessions')->where('group_id', $id)->delete();

            // Detach from training sessions pivot
            \DB::table('ffs_session_target_groups')->where('group_id', $id)->delete();

            // Delete shareouts
            \DB::table('vsla_shareouts')->where('group_id', $id)->delete();
        });
    }

    /**
     * Generate unique group code (retries if duplicate exists)
     */
    public static function generateGroupCode($type, $districtId)
    {
        $district = Location::find($districtId);
        $districtCode = $district ? strtoupper(substr($district->name, 0, 3)) : 'XXX';
        
        $typeCode = substr($type, 0, 3);
        $year = date('y');
        
        // Start from count+1 and increment until a unique code is found
        $count = self::where('type', $type)
            ->where('district_id', $districtId)
            ->whereYear('created_at', date('Y'))
            ->count() + 1;
        
        do {
            $code = sprintf('%s-%s-%s-%04d', $districtCode, $typeCode, $year, $count);
            $count++;
        } while (self::where('code', $code)->exists());
        
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
}
