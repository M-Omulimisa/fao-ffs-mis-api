<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Debug: Log what we receive
            \Log::info('ProjectController::index called');
            \Log::info('User from middleware: ' . ($request->user ?? 'NOT SET'));
            \Log::info('UserModel from middleware: ' . (isset($request->userModel) ? 'SET' : 'NOT SET'));
            
            $query = Project::orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Search by title
            if ($request->has('search') && !empty($request->search)) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            $projects = $query->get();

            // Manually load createdBy relationship where it exists
            $projects->load(['createdBy' => function ($query) {
                $query->select('id', 'name', 'email', 'avatar');
            }]);

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'status' => 'required|in:ongoing,completed,on_hold',
                'share_price' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            
            // Set created_by_id if authenticated user exists
            if (Auth::check()) {
                $data['created_by_id'] = Auth::id();
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('projects', 'public');
                $data['image'] = $imagePath;
            }

            $project = Project::create($data);
            
            // Load createdBy relationship where it exists
            if ($project->created_by_id) {
                $project->load(['createdBy' => function ($query) {
                    $query->select('id', 'name', 'email', 'avatar');
                }]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => $project,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $project = Project::findOrFail($id);
            
            // Load createdBy relationship where it exists
            $project->load(['createdBy' => function ($query) {
                $query->select('id', 'name', 'email', 'avatar');
            }]);

            return response()->json([
                'success' => true,
                'data' => $project,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $project = Project::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'nullable|date|after:start_date',
                'status' => 'sometimes|required|in:ongoing,completed,on_hold',
                'share_price' => 'sometimes|required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($project->image) {
                    Storage::disk('public')->delete($project->image);
                }
                $imagePath = $request->file('image')->store('projects', 'public');
                $data['image'] = $imagePath;
            }

            $project->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => $project->load('createdBy'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $project = Project::findOrFail($id);

            // Delete associated image
            if ($project->image) {
                Storage::disk('public')->delete($project->image);
            }

            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get project details with shares and transactions
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getDetails($id)
    {
        try {
            $project = Project::findOrFail($id);
            
            // Load relationships where they exist
            $project->load([
                'createdBy' => function ($query) {
                    $query->select('id', 'name', 'email', 'avatar');
                },
                'shares.investor' => function ($query) {
                    $query->select('id', 'name', 'email', 'avatar');
                },
                'transactions.creator' => function ($query) {
                    $query->select('id', 'name', 'email', 'avatar');
                }
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'project' => $project,
                    'shares_summary' => [
                        'total_shares' => $project->total_shares,
                        'shares_sold' => $project->shares_sold,
                        'total_investors' => $project->shares->groupBy('investor_id')->count(),
                    ],
                    'financial_summary' => [
                        'total_investment' => $project->total_investment,
                        'total_returns' => $project->total_returns,
                        'total_expenses' => $project->total_expenses,
                        'total_profits' => $project->total_profits,
                        'net_profit' => $project->net_profit,
                        'roi_percentage' => $project->roi_percentage,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
