<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VslaProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vsla_profiles';

    protected $fillable = [
        // Linkage
        'group_id',
        'cycle_id',
        'chairperson_id',
        'facilitator_id',
        'ip_id',
        'created_by_id',
        // Group info
        'group_name',
        'district_id',
        'village',
        'meeting_frequency',
        'meeting_day',
        'registration_date',
        'meeting_venue',
        'estimated_members',
        // Cycle info
        'cycle_name',
        'share_value',
        'saving_type',
        'loan_interest_rate',
        'interest_frequency',
        'minimum_loan_amount',
        'maximum_loan_multiple',
        'late_payment_penalty',
        'cycle_start_date',
        'cycle_end_date',
        // Chairperson info
        'chair_first_name',
        'chair_last_name',
        'chair_phone',
        'chair_sex',
        'chair_email',
        'chair_national_id',
        // Meta
        'status',
    ];

    protected $casts = [
        'share_value'          => 'decimal:2',
        'loan_interest_rate'   => 'decimal:2',
        'minimum_loan_amount'  => 'decimal:2',
        'maximum_loan_multiple' => 'decimal:2',
        'late_payment_penalty' => 'decimal:2',
        'registration_date'    => 'date',
        'cycle_start_date'     => 'date',
        'cycle_end_date'       => 'date',
        'estimated_members'    => 'integer',
    ];

    // ── Relationships ──

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    public function chairperson()
    {
        return $this->belongsTo(User::class, 'chairperson_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function implementingPartner()
    {
        return $this->belongsTo(ImplementingPartner::class, 'ip_id');
    }

    public function district()
    {
        return $this->belongsTo(Location::class, 'district_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    // ── Boot ──

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profile) {
            // Auto-assign ip_id from admin user if not set
            if (empty($profile->ip_id)) {
                try {
                    $adminUser = \Encore\Admin\Facades\Admin::user();
                    if ($adminUser && $adminUser->ip_id) {
                        $profile->ip_id = $adminUser->ip_id;
                    }
                } catch (\Throwable $e) {
                    // Admin facade not available
                }
            }

            // Set created_by_id
            if (empty($profile->created_by_id)) {
                try {
                    $adminUser = \Encore\Admin\Facades\Admin::user();
                    if ($adminUser) {
                        $profile->created_by_id = $adminUser->id;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        });
    }
}
