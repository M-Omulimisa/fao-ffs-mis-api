<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmActivity;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FarmActivityController extends Controller
{
    /**
     * Get all activities for authenticated user's farms
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = FarmActivity::whereHas('farm', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['farm.enterprise', 'protocol']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by farm
        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }
        
        // Show overdue activities
        if ($request->has('overdue') && $request->overdue) {
            $query->where('status', 'overdue');
        }
        
        $activities = $query->orderBy('scheduled_date')->get();
        
        return response()->json([
            'code' => 1,
            'message' => 'Activities retrieved successfully',
            'data' => $activities
        ]);
    }
    
    /**
     * Create a custom activity
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'farm_id' => 'required|exists:farms,id',
            'activity_name' => 'required|string|min:3|max:255',
            'activity_description' => 'required|string|min:10',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'is_mandatory' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if farm belongs to user
        $farm = Farm::where('id', $request->farm_id)
                    ->where('user_id', $user->id)
                    ->first();
        
        if (!$farm) {
            return response()->json([
                'code' => 0,
                'message' => 'Farm not found or unauthorized'
            ], 404);
        }
        
        // Calculate scheduled week based on farm start date
        $scheduledDate = Carbon::parse($request->scheduled_date);
        $startDate = Carbon::parse($farm->start_date);
        $weeksDiff = $startDate->diffInWeeks($scheduledDate);
        $scheduledWeek = max(1, $weeksDiff + 1); // Week starts at 1
        
        // Create the custom activity
        $activity = new FarmActivity();
        $activity->farm_id = $request->farm_id;
        $activity->activity_name = $request->activity_name;
        $activity->activity_description = $request->activity_description;
        $activity->scheduled_date = $request->scheduled_date;
        $activity->is_mandatory = $request->is_mandatory ?? true;
        $activity->status = 'pending';
        $activity->weight = 3; // Default weight for custom activities
        $activity->is_custom = true; // Mark as custom activity
        $activity->scheduled_week = $scheduledWeek;
        $activity->save();
        
        return response()->json([
            'code' => 1,
            'message' => 'Custom activity created successfully',
            'data' => $activity
        ], 201);
    }
    
    /**
     * Get a single activity
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $activity = FarmActivity::whereHas('farm', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['farm.enterprise', 'protocol'])->find($id);
        
        if (!$activity) {
            return response()->json([
                'code' => 0,
                'message' => 'Activity not found'
            ], 404);
        }
        
        return response()->json([
            'code' => 1,
            'message' => 'Activity details retrieved successfully',
            'data' => $activity
        ]);
    }
    
    /**
     * Mark activity as done
     */
    public function complete(Request $request, $id)
    {
        $user = Auth::user();
        
        $activity = FarmActivity::whereHas('farm', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->find($id);
        
        if (!$activity) {
            return response()->json([
                'code' => 0,
                'message' => 'Activity not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'completion_date' => 'required|date',
            'actual_value' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Handle photo upload - centralized to public/storage/images/
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = \App\Models\Utils::uploadMedia(
                $request->file('photo'), 
                ['jpg', 'jpeg', 'png', 'webp'], 
                5
            );
        }
        
        $activity->markAsDone(
            Carbon::parse($request->completion_date),
            $request->actual_value,
            $request->notes,
            $photoPath
        );
        
        $activity->load(['farm.enterprise', 'protocol']);
        
        return response()->json([
            'code' => 1,
            'message' => 'Activity marked as done successfully',
            'data' => $activity
        ]);
    }
    
    /**
     * Skip an activity
     */
    public function skip(Request $request, $id)
    {
        $user = Auth::user();
        
        $activity = FarmActivity::whereHas('farm', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->find($id);
        
        if (!$activity) {
            return response()->json([
                'code' => 0,
                'message' => 'Activity not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $activity->markAsSkipped($request->notes);
        $activity->load(['farm.enterprise', 'protocol']);
        
        return response()->json([
            'code' => 1,
            'message' => 'Activity skipped successfully',
            'data' => $activity
        ]);
    }
    
    /**
     * Upload photo for an activity
     */
    public function uploadPhoto(Request $request, $id)
    {
        $user = Auth::user();
        
        $activity = FarmActivity::whereHas('farm', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->find($id);
        
        if (!$activity) {
            return response()->json([
                'code' => 0,
                'message' => 'Activity not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:5120', // 5MB max
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $photoPath = \App\Models\Utils::uploadMedia(
            $request->file('photo'), 
            ['jpg', 'jpeg', 'png', 'webp'], 
            5
        );
        
        if ($photoPath) {
            $activity->photo = $photoPath;
            $activity->save();
        } else {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to upload photo'
            ], 400);
        }
        
        return response()->json([
            'code' => 1,
            'message' => 'Photo uploaded successfully',
            'data' => $activity
        ]);
    }
    
    /**
     * Get overdue activities
     */
    public function getOverdue()
    {
        $user = Auth::user();
        
        $activities = FarmActivity::whereHas('farm', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', 'overdue')
        ->with(['farm.enterprise', 'protocol'])
        ->orderBy('scheduled_date')
        ->get();
        
        return response()->json([
            'code' => 1,
            'message' => 'Overdue activities retrieved successfully',
            'data' => $activities
        ]);
    }
    
    /**
     * Get upcoming activities (next 7 days)
     */
    public function getUpcoming()
    {
        $user = Auth::user();
        
        $activities = FarmActivity::whereHas('farm', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', 'pending')
        ->whereBetween('scheduled_date', [
            Carbon::now(),
            Carbon::now()->addDays(7)
        ])
        ->with(['farm.enterprise', 'protocol'])
        ->orderBy('scheduled_date')
        ->get();
        
        return response()->json([
            'code' => 1,
            'message' => 'Upcoming activities retrieved successfully',
            'data' => $activities
        ]);
    }
}
