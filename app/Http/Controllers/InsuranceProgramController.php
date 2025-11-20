<?php

namespace App\Http\Controllers;

use App\Models\InsuranceProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsuranceProgramController extends Controller
{
    /**
     * Display a listing of insurance programs
     */
    public function index(Request $request)
    {
        try {
            $query = InsuranceProgram::query();

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by billing frequency
            if ($request->has('billing_frequency') && !empty($request->billing_frequency)) {
                $query->where('billing_frequency', $request->billing_frequency);
            }

            // Search
            if ($request->has('search') && !empty($request->search)) {
                $search = strtolower($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
                });
            }

            // Filter by availability (active and within date range)
            if ($request->has('available') && $request->available == 'true') {
                $query->where('status', 'Active')
                      ->where(function ($q) {
                          $q->whereNull('start_date')
                            ->orWhere('start_date', '<=', now());
                      })
                      ->where(function ($q) {
                          $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                      });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 50);
            $programs = $query->paginate($perPage);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance programs retrieved successfully.',
                'data' => $programs->items(),
                'pagination' => [
                    'total' => $programs->total(),
                    'per_page' => $programs->perPage(),
                    'current_page' => $programs->currentPage(),
                    'last_page' => $programs->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve insurance programs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created insurance program
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'coverage_amount' => 'required|numeric|min:0',
                'premium_amount' => 'required|numeric|min:0',
                'billing_frequency' => 'required|in:Weekly,Monthly,Quarterly,Annually',
                'billing_day' => 'required|integer|min:1',
                'duration_months' => 'required|integer|min:1',
                'grace_period_days' => 'nullable|integer|min:0',
                'late_payment_penalty' => 'nullable|numeric|min:0',
                'penalty_type' => 'nullable|in:Fixed,Percentage',
                'min_age' => 'nullable|integer|min:0',
                'max_age' => 'nullable|integer|min:0',
                'status' => 'nullable|in:Active,Inactive,Suspended',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();
            
            // Set created_by if authenticated
            if (auth()->check()) {
                $data['created_by'] = auth()->id();
            }

            $program = InsuranceProgram::create($data);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance program created successfully.',
                'data' => $program,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to create insurance program: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified insurance program
     */
    public function show($id)
    {
        try {
            $program = InsuranceProgram::with(['subscriptions', 'activeSubscriptions'])->findOrFail($id);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance program retrieved successfully.',
                'data' => $program,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Insurance program not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified insurance program
     */
    public function update(Request $request, $id)
    {
        try {
            $program = InsuranceProgram::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'coverage_amount' => 'sometimes|required|numeric|min:0',
                'premium_amount' => 'sometimes|required|numeric|min:0',
                'billing_frequency' => 'sometimes|required|in:Weekly,Monthly,Quarterly,Annually',
                'billing_day' => 'sometimes|required|integer|min:1',
                'duration_months' => 'sometimes|required|integer|min:1',
                'grace_period_days' => 'nullable|integer|min:0',
                'late_payment_penalty' => 'nullable|numeric|min:0',
                'penalty_type' => 'nullable|in:Fixed,Percentage',
                'min_age' => 'nullable|integer|min:0',
                'max_age' => 'nullable|integer|min:0',
                'status' => 'nullable|in:Active,Inactive,Suspended',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'status' => 0,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();
            
            // Remove _method if present
            unset($data['_method']);
            
            // Set updated_by if authenticated
            if (auth()->check()) {
                $data['updated_by'] = auth()->id();
            }

            $program->update($data);

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance program updated successfully.',
                'data' => $program->fresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to update insurance program: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified insurance program
     */
    public function destroy($id)
    {
        try {
            $program = InsuranceProgram::findOrFail($id);
            $program->delete();

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Insurance program deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to delete insurance program: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get statistics for insurance programs
     */
    public function stats(Request $request)
    {
        try {
            $stats = [
                'total_programs' => InsuranceProgram::count(),
                'active_programs' => InsuranceProgram::where('status', 'Active')->count(),
                'inactive_programs' => InsuranceProgram::where('status', 'Inactive')->count(),
                'suspended_programs' => InsuranceProgram::where('status', 'Suspended')->count(),
                'total_subscribers' => InsuranceProgram::sum('total_subscribers'),
                'total_premiums_collected' => InsuranceProgram::sum('total_premiums_collected'),
                'total_premiums_expected' => InsuranceProgram::sum('total_premiums_expected'),
                'total_premiums_balance' => InsuranceProgram::sum('total_premiums_balance'),
            ];

            // Get specific program stats if requested
            if ($request->has('insurance_program_id')) {
                $program = InsuranceProgram::find($request->insurance_program_id);
                if ($program) {
                    $stats['program_name'] = $program->name;
                    $stats['program_subscribers'] = $program->total_subscribers;
                    $stats['program_collected'] = $program->total_premiums_collected;
                    $stats['program_expected'] = $program->total_premiums_expected;
                    $stats['program_balance'] = $program->total_premiums_balance;
                }
            }

            return response()->json([
                'code' => 1,
                'status' => 1,
                'message' => 'Statistics retrieved successfully.',
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'status' => 0,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
