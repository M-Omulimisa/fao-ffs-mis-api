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
        'name',
        'type',
        'code',
        'registration_date',
        'district_id',
        'subcounty_id',
        'parish_id',
        'village',
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
        'facilitator_id',
        'contact_person_name',
        'contact_person_phone',
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
        'created_by_id',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'total_members' => 'integer',
        'male_members' => 'integer',
        'female_members' => 'integer',
        'youth_members' => 'integer',
        'pwd_members' => 'integer',
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

    // TODO: Uncomment when these models are created
    // public function members()
    // {
    //     return $this->hasMany(GroupMember::class, 'group_id');
    // }

    // public function trainingSessions()
    // {
    //     return $this->hasMany(TrainingSession::class, 'group_id');
    // }

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
        });

        // Commenting out updating hook until members() relationship is available
        // static::updating(function ($group) {
        //     // Update member counts automatically
        //     $group->calculateMemberCounts();
        // });
        
        // Prevent deletion of groups
        static::deleting(function ($group) {
            throw new \Exception('Groups cannot be deleted. Please set status to Inactive instead.');
        });
    }

    /**
     * Generate unique group code
     */
    public static function generateGroupCode($type, $districtId)
    {
        $district = Location::find($districtId);
        $districtCode = $district ? strtoupper(substr($district->name, 0, 3)) : 'XXX';
        
        $typeCode = substr($type, 0, 3);
        $year = date('y');
        
        // Get count of groups of this type in this district
        $count = self::where('type', $type)
            ->where('district_id', $districtId)
            ->whereYear('created_at', date('Y'))
            ->count() + 1;
        
        return sprintf('%s-%s-%s-%04d', $districtCode, $typeCode, $year, $count);
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
