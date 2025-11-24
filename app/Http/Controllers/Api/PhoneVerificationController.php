<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PhoneVerificationController extends Controller
{
    /**
     * Check if phone number exists and user is chairperson
     */
    public function checkPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        $phone = $request->phone_number;
        
        // Normalize phone number - try with and without Uganda country code
        $phoneVariants = [
            $phone,
            Utils::prepare_phone_number($phone), // Adds +256 if needed
            preg_replace('/^(\+?256|0)/', '', $phone), // Remove prefix
            '0' . preg_replace('/^(\+?256|0)/', '', $phone), // Add 0 prefix
        ];

        // Search for user with any phone variant and position = Chairperson
        $user = User::whereIn('phone_number', $phoneVariants)
            ->orWhereIn('phone_number_2', $phoneVariants)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'Phone number not registered in the system. Please contact admin for registration.',
                'data' => null,
            ]);
        }

        // Check if user is chairperson
        if ($user->is_group_admin !== 'Yes') {
            return response()->json([
                'status' => 0,
                'message' => 'Only chairpersons can register through this portal. Please contact admin.',
                'data' => null,
            ]);
        }

        // Check if user has already completed registration
        if ($user->status == 1) {
            return response()->json([
                'status' => 0,
                'message' => 'This account is already registered. Please use login instead.',
                'data' => ['already_registered' => true],
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Phone number verified. You can request OTP code.',
            'data' => [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'can_request_otp' => true,
            ],
        ]);
    }

    /**
     * Send OTP code to phone number
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        $phone = $request->phone_number;
        
        // Normalize phone number - try with and without Uganda country code
        $phoneVariants = [
            $phone,
            Utils::prepare_phone_number($phone),
            preg_replace('/^(\+?256|0)/', '', $phone),
            '0' . preg_replace('/^(\+?256|0)/', '', $phone),
        ];

        // Verify user exists and is group admin
        $user = User::whereIn('phone_number', $phoneVariants)
            ->orWhereIn('phone_number_2', $phoneVariants)
            ->first();

        if (!$user || $user->is_group_admin !== 'Yes') {
            return response()->json([
                'status' => 0,
                'message' => 'Phone number not verified',
            ]);
        }

        // Delete any existing OTP for this phone
        OtpVerification::whereIn('phone_number', $phoneVariants)->delete();

        // Generate 6-digit OTP
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store OTP in database
        $otp = OtpVerification::create([
            'phone_number' => $phone,
            'otp_code' => $otpCode,
            'expires_at' => Carbon::now()->addMinutes(10),
            'user_id' => $user->id,
        ]);

        // Send SMS
        $message = "Your FAO FFS-MIS verification code is: {$otpCode}. Valid for 10 minutes.";
        
        try {
            Utils::send_sms($phone, $message);
            
            return response()->json([
                'status' => 1,
                'message' => 'OTP code sent to your phone number',
                'data' => [
                    'otp_sent' => true,
                    'expires_in_minutes' => 10,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to send OTP. Please try again.',
            ]);
        }
    }

    /**
     * Verify OTP code and return user data for pre-filling
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        $phone = $request->phone_number;
        $preparedPhone = Utils::prepare_phone_number($phone);
        $code = $request->otp_code;

        // Find and verify OTP (search with all phone variants)
        $phoneVariants = [
            $phone,
            $preparedPhone,
            preg_replace('/^(\+?256|0)/', '', $phone),
            '0' . preg_replace('/^(\+?256|0)/', '', $phone),
        ];
        
        $otp = OtpVerification::whereIn('phone_number', $phoneVariants)
            ->where('otp_code', $code)
            ->whereNull('verified_at')
            ->first();

        if (!$otp) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid OTP code',
            ]);
        }

        // Check if OTP has expired
        if (Carbon::now()->greaterThan($otp->expires_at)) {
            return response()->json([
                'status' => 0,
                'message' => 'OTP code has expired. Please request a new one.',
            ]);
        }

        // Mark OTP as verified
        $otp->verified_at = Carbon::now();
        $otp->save();

        // Get user data with group information
        $user = User::whereIn('phone_number', $phoneVariants)
            ->orWhereIn('phone_number_2', $phoneVariants)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 0,
                'message' => 'User not found',
            ]);
        }

        // Get group information
        $group = null;
        if ($user->group_id) {
            $group = \App\Models\FfsGroup::find($user->group_id);
        }

        // Get secretary and treasurer information if available
        $secretary = null;
        $treasurer = null;
        
        if ($group) {
            $secretary = User::where('group_id', $group->id)
                ->where('is_group_secretary', 'Yes')
                ->first();
            
            $treasurer = User::where('group_id', $group->id)
                ->where('is_group_treasurer', 'Yes')
                ->first();
        }

        return response()->json([
            'status' => 1,
            'message' => 'OTP verified successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                    'phone_number_2' => $user->phone_number_2,
                    'email' => $user->email,
                    'is_group_admin' => $user->is_group_admin,
                    'is_group_secretary' => $user->is_group_secretary,
                    'is_group_treasurer' => $user->is_group_treasurer,
                    'group_id' => $user->group_id,
                ],
                'group' => $group ? [
                    'id' => $group->id,
                    'name' => $group->name,
                    'type' => $group->type,
                ] : null,
                'secretary' => $secretary ? [
                    'name' => $secretary->name,
                    'phone_number' => $secretary->phone_number,
                    'email' => $secretary->email,
                ] : null,
                'treasurer' => $treasurer ? [
                    'name' => $treasurer->name,
                    'phone_number' => $treasurer->phone_number,
                    'email' => $treasurer->email,
                ] : null,
            ],
        ]);
    }
}
