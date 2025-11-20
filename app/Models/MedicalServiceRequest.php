<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MedicalServiceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'insurance_subscription_id',
        'service_type',
        'service_category',
        'urgency_level',
        'symptoms_description',
        'additional_notes',
        'preferred_hospital',
        'preferred_doctor',
        'preferred_date',
        'preferred_time',
        'contact_phone',
        'contact_email',
        'contact_address',
        'status',
        'admin_feedback',
        'reviewed_by',
        'reviewed_at',
        'assigned_hospital',
        'assigned_doctor',
        'scheduled_date',
        'scheduled_time',
        'appointment_details',
        'estimated_cost',
        'insurance_coverage',
        'patient_payment',
        'attachments',
        'reference_number',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'attachments' => 'array',
        'preferred_date' => 'date',
        'scheduled_date' => 'date',
        'reviewed_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'insurance_coverage' => 'decimal:2',
        'patient_payment' => 'decimal:2',
    ];

    protected $appends = ['status_label', 'urgency_label', 'service_type_label'];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Generate reference number before creating
        self::creating(function ($request) {
            if (empty($request->reference_number)) {
                $request->reference_number = 'MSR-' . strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function insuranceSubscription()
    {
        return $this->belongsTo(InsuranceSubscription::class, 'insurance_subscription_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeEmergency($query)
    {
        return $query->where('urgency_level', 'emergency');
    }

    public function scopeUrgent($query)
    {
        return $query->where('urgency_level', 'urgent');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getUrgencyLabelAttribute()
    {
        $labels = [
            'emergency' => 'Emergency',
            'urgent' => 'Urgent',
            'normal' => 'Normal',
        ];
        return $labels[$this->urgency_level] ?? $this->urgency_level;
    }

    public function getServiceTypeLabelAttribute()
    {
        $labels = [
            'consultation' => 'Medical Consultation',
            'emergency' => 'Emergency Service',
            'lab_test' => 'Laboratory Test',
            'prescription' => 'Prescription Refill',
            'surgery' => 'Surgery',
            'dental' => 'Dental Service',
            'optical' => 'Optical Service',
            'physiotherapy' => 'Physiotherapy',
            'mental_health' => 'Mental Health Service',
            'maternity' => 'Maternity Service',
            'vaccination' => 'Vaccination',
            'other' => 'Other Service',
        ];
        return $labels[$this->service_type] ?? $this->service_type;
    }

    /**
     * Helper methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isEmergency()
    {
        return $this->urgency_level === 'emergency';
    }
}
