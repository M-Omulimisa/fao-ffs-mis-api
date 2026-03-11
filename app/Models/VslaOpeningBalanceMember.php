<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VslaOpeningBalanceMember extends Model
{
    protected $table = 'vsla_opening_balance_members';

    protected $fillable = [
        'opening_balance_id',
        'member_id',
        'total_shares',
        'share_count',
        'total_loan_amount',
        'loan_balance',
        'total_social_fund',
    ];

    protected $casts = [
        'total_shares'      => 'decimal:2',
        'share_count'       => 'decimal:2',
        'total_loan_amount' => 'decimal:2',
        'loan_balance'      => 'decimal:2',
        'total_social_fund' => 'decimal:2',
    ];

    public function openingBalance()
    {
        return $this->belongsTo(VslaOpeningBalance::class, 'opening_balance_id');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
