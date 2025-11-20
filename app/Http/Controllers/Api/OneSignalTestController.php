<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationModel;
use App\Services\OneSignalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OneSignalTestController extends Controller
{
    protected $oneSignalService;

    public function __construct(OneSignalService $oneSignalService)
    {
        $this->oneSignalService = $oneSignalService;
    }

    /**
     * Test OneSignal connection
     */
    public function testConnection()
    {
        $stats = $this->oneSignalService->getAppStats();

        return response()->json([
            'success' => $stats['success'],
            'total_users' => $stats['total_users'] ?? 0,
            'messageable_users' => $stats['messageable_users'] ?? 0,
            'error' => $stats['error'] ?? null,
        ]);
    }

    /**
     * Send a simple notification
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'string|in:general,promotion,order,urgent',
            'url' => 'nullable|url',
            'target' => 'string|in:all,segments',
            'segments' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 400);
        }

        try {
            // Create notification record
            $notification = NotificationModel::create([
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type ?? 'general',
                'url' => $request->url,
                'target_segments' => $request->target === 'segments' ? $request->segments : null,
                'status' => 'pending',
                'created_by' => Auth::id() ?? 1, // Default to admin user
            ]);

            // Send notification
            $result = $notification->send();

            return response()->json([
                'success' => $result['success'],
                'recipients' => $result['recipients'] ?? 0,
                'notification_id' => $result['notification_id'] ?? null,
                'database_id' => $notification->id,
                'error' => $result['error'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send an advanced notification
     */
    public function sendAdvanced(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'subtitle' => 'nullable|string|max:255',
            'large_icon' => 'nullable|url',
            'big_picture' => 'nullable|url',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
            ], 400);
        }

        try {
            // Send advanced notification directly
            $result = $this->oneSignalService->sendAdvanced(
                [], // Empty filters = all users
                $request->title,
                $request->message,
                $request->data ?? [],
                null, // url
                $request->subtitle,
                $request->large_icon,
                $request->big_picture
            );

            // Save to database
            $notification = NotificationModel::create([
                'title' => $request->title,
                'message' => $request->message,
                'type' => 'general',
                'large_icon' => $request->large_icon,
                'big_picture' => $request->big_picture,
                'data' => $request->data,
                'onesignal_id' => $result['notification_id'] ?? null,
                'recipients' => $result['recipients'] ?? 0,
                'status' => $result['success'] ? 'sent' : 'failed',
                'error_message' => $result['success'] ? null : $result['error'],
                'sent_at' => $result['success'] ? now() : null,
                'created_by' => Auth::id() ?? 1,
            ]);

            return response()->json([
                'success' => $result['success'],
                'recipients' => $result['recipients'] ?? 0,
                'notification_id' => $result['notification_id'] ?? null,
                'database_id' => $notification->id,
                'error' => $result['error'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent notifications
     */
    public function recent()
    {
        try {
            $notifications = NotificationModel::latest()
                ->take(10)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'status' => $notification->status,
                        'recipients' => $notification->recipients,
                        'target_description' => $notification->getTargetDescriptionAttribute(),
                        'error_message' => $notification->error_message,
                        'created_at' => $notification->created_at,
                        'sent_at' => $notification->sent_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function stats()
    {
        try {
            $stats = [
                'total_notifications' => NotificationModel::count(),
                'sent_notifications' => NotificationModel::where('status', 'sent')->count(),
                'failed_notifications' => NotificationModel::where('status', 'failed')->count(),
                'pending_notifications' => NotificationModel::where('status', 'pending')->count(),
                'total_recipients' => NotificationModel::where('status', 'sent')->sum('recipients'),
                'recent_activity' => NotificationModel::where('created_at', '>=', now()->subDays(7))
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->get(),
            ];

            // Get OneSignal app stats
            $oneSignalStats = $this->oneSignalService->getAppStats();
            if ($oneSignalStats['success']) {
                $stats['onesignal_total_users'] = $oneSignalStats['total_users'] ?? 0;
                $stats['onesignal_messageable_users'] = $oneSignalStats['messageable_users'] ?? 0;
            }

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a notification
     */
    public function cancel(Request $request, $id)
    {
        try {
            $notification = NotificationModel::findOrFail($id);
            
            if ($notification->cancel()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification cancelled successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Unable to cancel notification',
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification details from OneSignal
     */
    public function getNotificationDetails($id)
    {
        try {
            $notification = NotificationModel::findOrFail($id);
            $stats = $notification->getStats();

            return response()->json([
                'success' => true,
                'notification' => $notification,
                'onesignal_stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
