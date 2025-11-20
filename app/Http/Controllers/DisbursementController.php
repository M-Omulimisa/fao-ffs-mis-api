<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\ProjectTransaction;
use App\Models\AccountTransaction;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DisbursementController extends Controller
{
    /**
     * Get all disbursements with filters
     */
    public function index(Request $request)
    {
        try {
            $query = Disbursement::with(['project', 'creator']);

            // Filter by project
            if ($request->has('project_id') && $request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $query->where('disbursement_date', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date) {
                $query->where('disbursement_date', '<=', $request->end_date);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($pq) use ($search) {
                            $pq->where('title', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'disbursement_date');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->input('per_page', 20);
            $disbursements = $query->paginate($perPage);

            // Calculate summary
            $summary = [
                'total_disbursed' => Disbursement::sum('amount'),
                'total_disbursements' => Disbursement::count(),
            ];

            // Format disbursements
            $formattedDisbursements = $disbursements->map(function ($disbursement) {
                return $this->formatDisbursement($disbursement);
            });

            return Utils::success([
                'disbursements' => $formattedDisbursements,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $disbursements->currentPage(),
                    'last_page' => $disbursements->lastPage(),
                    'per_page' => $disbursements->perPage(),
                    'total' => $disbursements->total(),
                ],
            ], 'Disbursements retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve disbursements: ' . $e->getMessage());
        }
    }

    /**
     * Create a new disbursement and distribute to investors
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric|min:0.01',
            'disbursement_date' => 'required|date',
            'description' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return Utils::error($validator->errors()->first());
        }

        try {
            $project = Project::findOrFail($request->project_id);
            
            // Calculate available balance (project transactions)
            $availableBalance = ProjectTransaction::where('project_id', $request->project_id)
                ->sum(DB::raw('CASE WHEN type = "income" THEN amount ELSE -amount END'));

            // Check if disbursement amount is valid
            if ($request->amount > $availableBalance) {
                return Utils::error("Insufficient project balance. Available: UGX " . number_format($availableBalance, 2));
            }

            // Get all investors for this project
            $investors = ProjectShare::where('project_id', $request->project_id)
                ->select('investor_id', DB::raw('SUM(total_amount_paid) as total_invested'))
                ->groupBy('investor_id')
                ->get();

            if ($investors->isEmpty()) {
                return Utils::error('No investors found for this project');
            }

            // Calculate total investment
            $totalInvestment = $investors->sum('total_invested');

            if ($totalInvestment <= 0) {
                return Utils::error('Invalid total investment amount');
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Create disbursement
                $disbursement = Disbursement::create([
                    'project_id' => $request->project_id,
                    'amount' => $request->amount,
                    'disbursement_date' => $request->disbursement_date,
                    'description' => $request->description,
                    'created_by_id' => $request->user_id,
                ]);

                // Create project transaction for this disbursement
                ProjectTransaction::create([
                    'project_id' => $request->project_id,
                    'amount' => $request->amount,
                    'transaction_date' => $request->disbursement_date,
                    'created_by_id' => $request->user_id,
                    'description' => 'Disbursement: ' . ($request->description ?? 'Returns distribution to investors'),
                    'type' => 'expense',
                    'source' => 'returns_distribution',
                ]);

                // Distribute proportionally to each investor
                $accountTransactions = [];
                foreach ($investors as $investor) {
                    // Calculate proportional amount
                    $proportion = $investor->total_invested / $totalInvestment;
                    $investorAmount = $request->amount * $proportion;

                    // Create account transaction for investor
                    $accountTransaction = AccountTransaction::create([
                        'user_id' => $investor->investor_id,
                        'amount' => $investorAmount,
                        'transaction_date' => $request->disbursement_date,
                        'description' => "Disbursement from {$project->title}: " . ($request->description ?? 'Returns distribution'),
                        'source' => 'disbursement',
                        'related_disbursement_id' => $disbursement->id,
                        'created_by_id' => $request->user_id,
                    ]);

                    $accountTransactions[] = [
                        'user_id' => $investor->investor_id,
                        'amount' => $investorAmount,
                        'formatted_amount' => 'UGX ' . number_format($investorAmount, 2),
                        'proportion' => round($proportion * 100, 2) . '%',
                    ];
                }

                DB::commit();

                // Load relationships
                $disbursement->load(['project', 'creator', 'accountTransactions']);

                return Utils::success([
                    'disbursement' => $this->formatDisbursement($disbursement),
                    'account_transactions' => $accountTransactions,
                    'total_investors' => count($accountTransactions),
                ], 'Disbursement created successfully and distributed to investors');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return Utils::error('Failed to create disbursement: ' . $e->getMessage());
        }
    }

    /**
     * Get a single disbursement with details
     */
    public function show($id)
    {
        try {
            $disbursement = Disbursement::with(['project', 'creator', 'accountTransactions.user'])
                ->findOrFail($id);

            return Utils::success([
                'disbursement' => $this->formatDisbursement($disbursement),
                'account_transactions' => $disbursement->accountTransactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'user_id' => $transaction->user_id,
                        'user_name' => $transaction->user->name ?? 'N/A',
                        'amount' => $transaction->amount,
                        'formatted_amount' => $transaction->formatted_amount,
                        'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                        'formatted_date' => $transaction->formatted_date,
                    ];
                }),
            ], 'Disbursement retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve disbursement: ' . $e->getMessage());
        }
    }

    /**
     * Update a disbursement (limited - cannot change amount after creation)
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'disbursement_date' => 'sometimes|date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return Utils::error($validator->errors()->first());
        }

        try {
            $disbursement = Disbursement::findOrFail($id);

            // Only allow updating date and description, not amount
            $disbursement->update($request->only(['disbursement_date', 'description']));

            $disbursement->load(['project', 'creator']);

            return Utils::success(
                $this->formatDisbursement($disbursement),
                'Disbursement updated successfully'
            );
        } catch (\Exception $e) {
            return Utils::error('Failed to update disbursement: ' . $e->getMessage());
        }
    }

    /**
     * Delete a disbursement (soft delete)
     */
    public function destroy($id)
    {
        try {
            $disbursement = Disbursement::findOrFail($id);

            DB::beginTransaction();

            try {
                // Soft delete related account transactions
                AccountTransaction::where('related_disbursement_id', $id)->delete();

                // Soft delete the disbursement
                $disbursement->delete();

                DB::commit();

                return Utils::success(null, 'Disbursement deleted successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return Utils::error('Failed to delete disbursement: ' . $e->getMessage());
        }
    }

    /**
     * Get projects for dropdown
     */
    public function getProjects()
    {
        try {
            $projects = Project::select('id', 'title', 'status')
                ->orderBy('title')
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'title' => $project->title,
                        'status' => $project->status,
                        'status_label' => $project->status_label ?? ucfirst($project->status),
                    ];
                });

            return Utils::success($projects, 'Projects retrieved successfully');
        } catch (\Exception $e) {
            return Utils::error('Failed to retrieve projects: ' . $e->getMessage());
        }
    }

    /**
     * Format disbursement for API response
     */
    private function formatDisbursement($disbursement)
    {
        return [
            'id' => $disbursement->id,
            'project_id' => $disbursement->project_id,
            'project_title' => $disbursement->project->title ?? 'N/A',
            'project_status' => $disbursement->project->status ?? 'N/A',
            'project_status_label' => $disbursement->project->status_label ?? 'N/A',
            'amount' => $disbursement->amount,
            'formatted_amount' => $disbursement->formatted_amount,
            'disbursement_date' => $disbursement->disbursement_date->format('Y-m-d'),
            'formatted_date' => $disbursement->formatted_date,
            'description' => $disbursement->description,
            'created_by' => $disbursement->creator->name ?? 'N/A',
            'created_by_id' => $disbursement->created_by_id,
            'created_at' => $disbursement->created_at->format('Y-m-d H:i:s'),
            'investors_count' => $disbursement->accountTransactions->count() ?? 0,
        ];
    }
}
