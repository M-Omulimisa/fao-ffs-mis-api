<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'otp_code',
        'expires_at',
        'is_verified',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function isExpired()
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    public function isValid()
    {
        return !$this->isExpired() && !$this->is_verified && $this->attempts < 5;
    }

    public static function generate($phoneNumber)
    {
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        self::where('phone_number', $phoneNumber)->delete();
        
        return self::create([
            'phone_number' => $phoneNumber,
            'otp_code' => $code,
            'expires_at' => Carbon::now()->addMinutes(10),
            'is_verified' => false,
            'attempts' => 0,
        ]);
    }

    public static function verify($phoneNumber, $code)
    {
        $otp = self::where('phone_number', $phoneNumber)
            ->where('otp_code', $code)
            ->where('is_verified', false)
            ->latest()
            ->first();

        if (!$otp) {
            return ['success' => false, 'message' => 'Invalid OTP code'];
        }

        $otp->increment('attempts');

        if ($otp->isExpired()) {
            return ['success' => false, 'message' => 'OTP code has expired'];
        }

        if ($otp->attempts > 5) {
            return ['success' => false, 'message' => 'Too many attempts'];
        }

        $otp->is_verified = true;
        $otp->save();

        return ['success' => true, 'message' => 'OTP verified successfully'];
    }
}
