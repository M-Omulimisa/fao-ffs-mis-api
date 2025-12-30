<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnterpriseController extends Controller
{
    /**
     * Get all enterprises with optional filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            \Log::info('========== ENTERPRISES INDEX REQUEST ==========');
            \Log::info('Request Parameters:', $request->all());
            
            $query = Enterprise::with(['productionProtocols' => function ($q) {
                $q->where('is_active', true)->orderBy('start_time', 'asc');
            }]);

            // Filter by type
            if ($request->has('type') && in_array($request->type, ['livestock', 'crop'])) {
                $query->where('type', $request->type);
                \Log::info('Filtering by type:', ['type' => $request->type]);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
                \Log::info('Filtering by active status:', ['is_active' => $request->is_active]);
            } else {
                // Default: only show active enterprises
                $query->where('is_active', true);
                \Log::info('Using default filter: is_active = true');
            }

            // Search by name
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
                \Log::info('Searching by name:', ['search' => $request->search]);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            \Log::info('Sorting:', ['sort_by' => $sortBy, 'sort_order' => $sortOrder]);

            // Get all enterprises
            $enterprises = $query->get();
            \Log::info('Enterprises Retrieved:', ['count' => $enterprises->count()]);
            
            if ($enterprises->count() > 0) {
                \Log::info('First Enterprise Sample:', [
                    'id' => $enterprises->first()->id,
                    'name' => $enterprises->first()->name,
                    'type' => $enterprises->first()->type,
                    'protocols_count' => $enterprises->first()->productionProtocols->count()
                ]);
            }
            
            \Log::info('========== ENTERPRISES INDEX COMPLETED ==========');

            return response()->json([
                'code' => 1,
                'message' => 'Enterprises retrieved successfully',
                'data' => $enterprises,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('========== ENTERPRISES INDEX FAILED ==========');
            \Log::error('Error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve enterprises: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single enterprise by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            \Log::info('========== ENTERPRISE SHOW REQUEST ==========');
            \Log::info('Enterprise ID:', ['id' => $id]);
            
            $enterprise = Enterprise::with(['productionProtocols' => function ($q) {
                $q->where('is_active', true)->orderBy('order', 'asc')->orderBy('start_time', 'asc');
            }])->find($id);

            if (!$enterprise) {
                \Log::warning('Enterprise not found:', ['id' => $id]);
                return response()->json([
                    'code' => 0,
                    'message' => 'Enterprise not found',
                ], 404);
            }

            \Log::info('Enterprise Found:', [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'type' => $enterprise->type,
                'protocols_count' => $enterprise->productionProtocols->count()
            ]);
            \Log::info('========== ENTERPRISE SHOW COMPLETED ==========');

            return response()->json([
                'code' => 1,
                'message' => 'Enterprise retrieved successfully',
                'data' => $enterprise,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('========== ENTERPRISE SHOW FAILED ==========');
            \Log::error('Error:', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve enterprise: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new enterprise.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:enterprises,name',
                'type' => 'required|in:livestock,crop',
                'duration' => 'required|integer|min:1|max:120',
                'description' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $data['created_by_id'] = auth()->user()->id ?? null;
            $data['is_active'] = $request->get('is_active', true);

            $enterprise = Enterprise::create($data);

            return response()->json([
                'code' => 1,
                'message' => 'Enterprise created successfully',
                'data' => $enterprise,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to create enterprise: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing enterprise.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $enterprise = Enterprise::find($id);

            if (!$enterprise) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Enterprise not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:enterprises,name,' . $id,
                'type' => 'required|in:livestock,crop',
                'duration' => 'required|integer|min:1|max:120',
                'description' => 'nullable|string',
                'photo' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $enterprise->update($validator->validated());

            return response()->json([
                'code' => 1,
                'message' => 'Enterprise updated successfully',
                'data' => $enterprise->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to update enterprise: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an enterprise.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $enterprise = Enterprise::find($id);

            if (!$enterprise) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Enterprise not found',
                ], 404);
            }

            $enterprise->delete();

            return response()->json([
                'code' => 1,
                'message' => 'Enterprise deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to delete enterprise: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics about enterprises.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_enterprises' => Enterprise::count(),
                'active_enterprises' => Enterprise::where('is_active', true)->count(),
                'livestock_enterprises' => Enterprise::where('type', 'livestock')->count(),
                'crop_enterprises' => Enterprise::where('type', 'crop')->count(),
                'total_protocols' => \App\Models\ProductionProtocol::count(),
                'by_type' => [
                    'livestock' => [
                        'count' => Enterprise::where('type', 'livestock')->count(),
                        'active' => Enterprise::where('type', 'livestock')->where('is_active', true)->count(),
                    ],
                    'crop' => [
                        'count' => Enterprise::where('type', 'crop')->count(),
                        'active' => Enterprise::where('type', 'crop')->where('is_active', true)->count(),
                    ],
                ],
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
