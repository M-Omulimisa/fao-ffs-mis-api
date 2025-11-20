<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OneSignalMobileController extends Controller
{
    /**
     * Register a mobile device with OneSignal player ID
     */
    public function registerDevice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'player_id' => 'required|string',
                'device_type' => 'required|string|in:mobile,web',
                'app_version' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->input('user_id');
            $playerId = $request->input('player_id');
            $deviceType = $request->input('device_type', 'mobile');
            $appVersion = $request->input('app_version', '1.0.0');

            // Check if this user-device combination already exists
            $existingRecord = DB::table('onesignal_devices')
                ->where('user_id', $userId)
                ->where('player_id', $playerId)
                ->first();

            if ($existingRecord) {
                // Update existing record
                DB::table('onesignal_devices')
                    ->where('id', $existingRecord->id)
                    ->update([
                        'device_type' => $deviceType,
                        'app_version' => $appVersion,
                        'last_active' => now(),
                        'updated_at' => now(),
                    ]);

                Log::info('OneSignal Mobile: Updated existing device registration', [
                    'user_id' => $userId,
                    'player_id' => $playerId,
                    'device_type' => $deviceType,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Device registration updated successfully',
                    'data' => [
                        'user_id' => $userId,
                        'player_id' => $playerId,
                        'status' => 'updated'
                    ]
                ]);
            } else {
                // Create new record
                $deviceId = DB::table('onesignal_devices')->insertGetId([
                    'user_id' => $userId,
                    'player_id' => $playerId,
                    'device_type' => $deviceType,
                    'app_version' => $appVersion,
                    'last_active' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('OneSignal Mobile: Registered new device', [
                    'device_id' => $deviceId,
                    'user_id' => $userId,
                    'player_id' => $playerId,
                    'device_type' => $deviceType,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Device registered successfully',
                    'data' => [
                        'device_id' => $deviceId,
                        'user_id' => $userId,
                        'player_id' => $playerId,
                        'status' => 'registered'
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('OneSignal Mobile: Device registration failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user information for OneSignal
     */
    public function updateUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'player_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = $request->input('user_id');
            $playerId = $request->input('player_id');

            // Update the last active timestamp
            $updated = DB::table('onesignal_devices')
                ->where('user_id', $userId)
                ->where('player_id', $playerId)
                ->update([
                    'last_active' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated) {
                Log::info('OneSignal Mobile: Updated user information', [
                    'user_id' => $userId,
                    'player_id' => $playerId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'User information updated successfully',
                    'data' => [
                        'user_id' => $userId,
                        'player_id' => $playerId,
                        'last_active' => now()->toISOString()
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No device found for this user and player ID combination'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('OneSignal Mobile: User update failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unregister a device (on logout)
     */
    public function unregisterDevice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'player_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $playerId = $request->input('player_id');

            // Delete the device record
            $deleted = DB::table('onesignal_devices')
                ->where('player_id', $playerId)
                ->delete();

            if ($deleted) {
                Log::info('OneSignal Mobile: Unregistered device', [
                    'player_id' => $playerId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Device unregistered successfully',
                    'data' => [
                        'player_id' => $playerId,
                        'status' => 'unregistered'
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No device found with this player ID'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('OneSignal Mobile: Device unregistration failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device unregistration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
