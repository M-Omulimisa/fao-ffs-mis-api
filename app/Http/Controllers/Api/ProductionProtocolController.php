<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionProtocol;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductionProtocolController extends Controller
{
    /**
     * Get all production protocols with optional filters.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = ProductionProtocol::with('enterprise');

            // Filter by enterprise
            if ($request->has('enterprise_id')) {
                $query->where('enterprise_id', $request->enterprise_id);
            }

            // Filter by compulsory
            if ($request->has('is_compulsory')) {
                $query->where('is_compulsory', $request->is_compulsory);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            } else {
                // Default: only show active protocols
                $query->where('is_active', true);
            }

            // Search by activity name
            if ($request->has('search') && !empty($request->search)) {
                $query->where('activity_name', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'start_time');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Secondary sort by order
            if ($sortBy !== 'order') {
                $query->orderBy('order', 'asc');
            }

            // Get all protocols
            $protocols = $query->get();

            return response()->json([
                'code' => 1,
                'message' => 'Production protocols retrieved successfully',
                'data' => $protocols,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve production protocols: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get production protocols for a specific enterprise.
     *
     * @param int $enterpriseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByEnterprise($enterpriseId)
    {
        try {
            $enterprise = Enterprise::find($enterpriseId);

            if (!$enterprise) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Enterprise not found',
                ], 404);
            }

            $protocols = ProductionProtocol::where('enterprise_id', $enterpriseId)
                ->where('is_active', true)
                ->orderBy('order', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Production protocols retrieved successfully',
                'data' => [
                    'enterprise' => $enterprise,
                    'protocols' => $protocols,
                    'summary' => [
                        'total_protocols' => $protocols->count(),
                        'compulsory' => $protocols->where('is_compulsory', true)->count(),
                        'optional' => $protocols->where('is_compulsory', false)->count(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve production protocols: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single production protocol by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $protocol = ProductionProtocol::with('enterprise')->find($id);

            if (!$protocol) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Production protocol not found',
                ], 404);
            }

            return response()->json([
                'code' => 1,
                'message' => 'Production protocol retrieved successfully',
                'data' => $protocol,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve production protocol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new production protocol.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'enterprise_id' => 'required|exists:enterprises,id',
                'activity_name' => 'required|string|max:255',
                'activity_description' => 'nullable|string',
                'start_time' => 'required|integer|min:0',
                'end_time' => 'required|integer|min:0',
                'is_compulsory' => 'boolean',
                'photo' => 'nullable|string',
                'order' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Additional validation: end_time >= start_time
            if ($request->end_time < $request->start_time) {
                return response()->json([
                    'code' => 0,
                    'message' => 'End time must be greater than or equal to start time',
                ], 422);
            }

            // Validate against enterprise duration
            $enterprise = Enterprise::find($request->enterprise_id);
            $maxWeeks = $enterprise->duration * 4;
            if ($request->end_time > $maxWeeks) {
                return response()->json([
                    'code' => 0,
                    'message' => "End time cannot exceed enterprise duration of {$maxWeeks} weeks",
                ], 422);
            }

            $data = $validator->validated();
            $data['created_by_id'] = auth()->user()->id ?? null;
            $data['is_active'] = $request->get('is_active', true);
            $data['is_compulsory'] = $request->get('is_compulsory', true);
            $data['order'] = $request->get('order', 0);

            $protocol = ProductionProtocol::create($data);

            return response()->json([
                'code' => 1,
                'message' => 'Production protocol created successfully',
                'data' => $protocol->load('enterprise'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to create production protocol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing production protocol.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $protocol = ProductionProtocol::find($id);

            if (!$protocol) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Production protocol not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'enterprise_id' => 'required|exists:enterprises,id',
                'activity_name' => 'required|string|max:255',
                'activity_description' => 'nullable|string',
                'start_time' => 'required|integer|min:0',
                'end_time' => 'required|integer|min:0',
                'is_compulsory' => 'boolean',
                'photo' => 'nullable|string',
                'order' => 'integer|min:0',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Additional validation: end_time >= start_time
            if ($request->end_time < $request->start_time) {
                return response()->json([
                    'code' => 0,
                    'message' => 'End time must be greater than or equal to start time',
                ], 422);
            }

            // Validate against enterprise duration
            $enterprise = Enterprise::find($request->enterprise_id);
            $maxWeeks = $enterprise->duration * 4;
            if ($request->end_time > $maxWeeks) {
                return response()->json([
                    'code' => 0,
                    'message' => "End time cannot exceed enterprise duration of {$maxWeeks} weeks",
                ], 422);
            }

            $protocol->update($validator->validated());

            return response()->json([
                'code' => 1,
                'message' => 'Production protocol updated successfully',
                'data' => $protocol->fresh()->load('enterprise'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to update production protocol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a production protocol.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $protocol = ProductionProtocol::find($id);

            if (!$protocol) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Production protocol not found',
                ], 404);
            }

            $protocol->delete();

            return response()->json([
                'code' => 1,
                'message' => 'Production protocol deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to delete production protocol: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get timeline view of protocols for an enterprise.
     *
     * @param int $enterpriseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function timeline($enterpriseId)
    {
        try {
            $enterprise = Enterprise::find($enterpriseId);

            if (!$enterprise) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Enterprise not found',
                ], 404);
            }

            $protocols = ProductionProtocol::where('enterprise_id', $enterpriseId)
                ->where('is_active', true)
                ->orderBy('start_time', 'asc')
                ->get();

            // Organize protocols by weeks
            $timeline = [];
            $maxWeeks = $enterprise->duration * 4;

            for ($week = 0; $week <= $maxWeeks; $week++) {
                $weekProtocols = $protocols->filter(function ($protocol) use ($week) {
                    return $week >= $protocol->start_time && $week <= $protocol->end_time;
                });

                if ($weekProtocols->count() > 0) {
                    $timeline[] = [
                        'week' => $week,
                        'activities' => $weekProtocols->values(),
                    ];
                }
            }

            return response()->json([
                'code' => 1,
                'message' => 'Timeline retrieved successfully',
                'data' => [
                    'enterprise' => $enterprise,
                    'timeline' => $timeline,
                    'summary' => [
                        'total_weeks' => $maxWeeks,
                        'active_weeks' => count($timeline),
                        'total_protocols' => $protocols->count(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve timeline: ' . $e->getMessage(),
            ], 500);
        }
    }
}
