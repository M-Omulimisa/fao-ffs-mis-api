<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'owner_type',
        'group_id',
        'meeting_id',
        'cycle_id',
        'amount',
        'transaction_date',
        'description',
        'source',
        'account_type',
        'contra_entry_id',
        'is_contra_entry',
        'related_disbursement_id',
        'created_by_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'is_contra_entry' => 'boolean',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_date',
        'source_label',
        'type',
    ];

    // Boot method - keep users.balance and users.loan_balance in sync
    protected static function boot()
    {
        parent::boot();

        $updateBalance = function ($txn) {
            if ($txn->owner_type === 'member' && $txn->user_id) {
                static::updateMemberBalance($txn->user_id);
            }
        };

        static::created($updateBalance);
        static::updated($updateBalance);
        static::deleted($updateBalance);
        static::restored($updateBalance);
    }

    /**
     * Recalculate and persist a member's balance and loan_balance from account_transactions.
     *
     * balance      = shares (positive member records)
     * loan_balance = ABS(loans) - loan_repayments  (net outstanding)
     */
    public static function updateMemberBalance(int $userId): void
    {
        try {
            $user = User::find($userId);
            if (!$user) return;

            $row = DB::selectOne("
                SELECT
                    COALESCE(SUM(CASE WHEN account_type = 'share' THEN amount ELSE 0 END), 0) AS shares,
                    COALESCE(SUM(CASE WHEN account_type = 'loan'  THEN amount ELSE 0 END), 0) AS loans,
                    COALESCE(SUM(CASE WHEN account_type = 'loan_repayment' THEN amount ELSE 0 END), 0) AS repayments,
                    COALESCE(SUM(CASE WHEN account_type = 'fine'  THEN amount ELSE 0 END), 0) AS fines
                FROM account_transactions
                WHERE owner_type = 'member'
                  AND user_id = ?
                  AND deleted_at IS NULL
            ", [$userId]);

            // Savings balance = shares (member share records are positive)
            $user->balance = (float) ($row->shares ?? 0);
            // Loan balance = outstanding loan amount (loans are negative, repayments positive)
            $loanNet = abs((float) ($row->loans ?? 0)) - (float) ($row->repayments ?? 0);
            $user->loan_balance = max(0, $loanNet);
            $user->save();
        } catch (\Exception $e) {
            Log::error('AccountTransaction::updateMemberBalance failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function relatedDisbursement()
    {
        return $this->belongsTo(Disbursement::class, 'related_disbursement_id');
    }

    public function group()
    {
        return $this->belongsTo(FfsGroup::class, 'group_id');
    }

    public function contraEntry()
    {
        return $this->belongsTo(AccountTransaction::class, 'contra_entry_id');
    }

    public function contraTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'contra_entry_id');
    }

    public function meeting()
    {
        return $this->belongsTo(VslaMeeting::class, 'meeting_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Project::class, 'cycle_id');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        $amount = $this->amount ?? 0;
        $prefix = $amount >= 0 ? '+' : '';
        return $prefix . 'UGX ' . number_format(abs($amount), 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->transaction_date ? $this->transaction_date->format('d M Y') : null;
    }

    public function getSourceLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->source));
    }

    public function getTypeAttribute()
    {
        return $this->amount >= 0 ? 'credit' : 'debit';
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeCredit($query)
    {
        return $query->where('amount', '>=', 0);
    }

    public function scopeDebit($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeForMeeting($query, $meetingId)
    {
        return $query->where('meeting_id', $meetingId);
    }

    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeGroupTransactions($query)
    {
        return $query->where('owner_type', 'group');
    }

    public function scopeMemberTransactions($query)
    {
        return $query->where('owner_type', 'member');
    }
}
