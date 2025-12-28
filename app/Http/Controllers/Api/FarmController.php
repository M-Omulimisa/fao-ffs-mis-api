<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FarmController extends Controller
{
    /**
     * Get all farms for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Farm::where('user_id', $user->id)
            ->with(['enterprise', 'activities' => function($q) {
                $q->orderBy('scheduled_date');
            }]);
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by enterprise if provided
        if ($request->has('enterprise_id')) {
            $query->where('enterprise_id', $request->enterprise_id);
        }
        
        $farms = $query->latest()->get();
        
        return response()->json([
            'code' => 1,
            'message' => 'Farms retrieved successfully',
            'data' => $farms
        ]);
    }
    
    /**
     * Create a new farm
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enterprise_id' => 'required|exists:enterprises,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
            'location_text' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = Auth::user();
        
        // Handle photo upload - centralized to public/storage/images/
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = \App\Models\Utils::uploadMedia(
                $request->file('photo'), 
                ['jpg', 'jpeg', 'png', 'webp'], 
                5
            );
        }
        
        $farm = Farm::create([
            'enterprise_id' => $request->enterprise_id,
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => 'planning',
            'start_date' => $request->start_date,
            'gps_latitude' => $request->gps_latitude,
            'gps_longitude' => $request->gps_longitude,
            'location_text' => $request->location_text,
            'photo' => $photoPath,
        ]);
        
        $farm->load(['enterprise', 'activities']);
        
        return response()->json([
            'code' => 1,
            'message' => 'Farm created successfully',
            'data' => $farm
        ], 201);
    }
    
    /**
     * Get a single farm with details
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $farm = Farm::where('user_id', $user->id)
            ->with(['enterprise', 'activities' => function($q) {
                $q->orderBy('scheduled_date');
            }])
            ->find($id);
        
        if (!$farm) {
            return response()->json([
                'code' => 0,
                'message' => 'Farm not found'
            ], 404);
        }
        
        return response()->json([
            'code' => 1,
            'message' => 'Farm details retrieved successfully',
            'data' => $farm
        ]);
    }
    
    /**
     * Update farm details
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        $farm = Farm::where('user_id', $user->id)->find($id);
        
        if (!$farm) {
            return response()->json([
                'code' => 0,
                'message' => 'Farm not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:planning,active,completed,abandoned',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
            'location_text' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:5120',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'code' => 0,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Handle photo upload - centralized to public/storage/images/
        if ($request->hasFile('photo')) {
            $photoPath = \App\Models\Utils::uploadMedia(
                $request->file('photo'), 
                ['jpg', 'jpeg', 'png', 'webp'], 
                5
            );
            if ($photoPath) {
                $farm->photo = $photoPath;
            }
        }
        
        // Only allow updating these fields, NOT enterprise_id
        $farm->fill($request->only([
            'name', 'description', 'status', 'gps_latitude', 
            'gps_longitude', 'location_text'
        ]));
        
        $farm->save();
        $farm->load(['enterprise', 'activities']);
        
        return response()->json([
            'code' => 1,
            'message' => 'Farm updated successfully',
            'data' => $farm
        ]);
    }
    
    /**
     * Delete a farm
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $farm = Farm::where('user_id', $user->id)->find($id);
        
        if (!$farm) {
            return response()->json([
                'code' => 0,
                'message' => 'Farm not found'
            ], 404);
        }
        
        $farm->delete();
        
        return response()->json([
            'code' => 1,
            'message' => 'Farm deleted successfully'
        ]);
    }
    
    /**
     * Get farm activities
     */
    public function getActivities($id, Request $request)
    {
        $user = Auth::user();
        
        $farm = Farm::where('user_id', $user->id)->find($id);
        
        if (!$farm) {
            return response()->json([
                'code' => 0,
                'message' => 'Farm not found'
            ], 404);
        }
        
        $query = $farm->activities();
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $activities = $query->orderBy('scheduled_date')->get();
        
        return response()->json([
            'code' => 1,
            'message' => 'Farm activities retrieved successfully',
            'data' => $activities
        ]);
    }
    
    /**
     * Get farm calendar view
     */
    public function getCalendarView($id, Request $request)
    {
        $user = Auth::user();
        
        $farm = Farm::where('user_id', $user->id)->find($id);
        
        if (!$farm) {
            return response()->json([
                'code' => 0,
                'message' => 'Farm not found'
            ], 404);
        }
        
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        $activities = $farm->activities()
            ->whereYear('scheduled_date', $year)
            ->whereMonth('scheduled_date', $month)
            ->orderBy('scheduled_date')
            ->get();
        
        return response()->json([
            'code' => 1,
            'message' => 'Calendar view retrieved successfully',
            'data' => [
                'year' => $year,
                'month' => $month,
                'activities' => $activities
            ]
        ]);
    }
    
    /**
     * Get farm statistics
     */
    public function getStats($id)
    {
        $user = Auth::user();
        
        $farm = Farm::where('user_id', $user->id)->find($id);
        
        if (!$farm) {
            return response()->json([
                'code' => 0,
                'message' => 'Farm not found'
            ], 404);
        }
        
        $stats = [
            'overall_score' => $farm->overall_score,
            'progress_percentage' => $farm->progress_percentage,
            'days_running' => $farm->days_running,
            'completed_activities' => $farm->completed_activities_count,
            'total_activities' => $farm->total_activities_count,
            'status_breakdown' => $farm->getStatusBreakdown(),
        ];
        
        return response()->json([
            'code' => 1,
            'message' => 'Farm statistics retrieved successfully',
            'data' => $stats
        ]);
    }
}
