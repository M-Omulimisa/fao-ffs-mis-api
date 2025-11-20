<?php

namespace App\Http\Controllers;

use App\Models\ProjectTransaction;
use App\Models\Project;
use App\Models\Utils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvestmentTransactionController extends Controller
{
    /**
     * Get all investment transactions with filtering
     * GET /api/investment-transactions
     */
    public function index(Request $request)
    {
        try {
            $query = ProjectTransaction::with([
                'project:id,title,image,status',
                'creator:id,name,email',
                'relatedShare.investor:id,name,email'
            ]);

            // Filter by project
            if ($request->has('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by source
            if ($request->has('source')) {
                $query->where('source', $request->source);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('transaction_date', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->whereDate('transaction_date', '<=', $request->end_date);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhereHas('project', function ($pq) use ($search) {
                          $pq->where('title', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'transaction_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

                        // Calculate summary BEFORE pagination (using the same filters as the query)
            // Clone the query to calculate totals with all applied filters
            $summaryQuery = clone $query;
            
            $totalIncome = (clone $summaryQuery)->where('type', 'income')->sum('amount');
            $totalExpense = (clone $summaryQuery)->where('type', 'expense')->sum('amount');
            $netAmount = $totalIncome - $totalExpense;
            $totalCount = (clone $summaryQuery)->count();

            $summary = [
                'total_count' => $totalCount,
                'total_income' => floatval($totalIncome),
                'total_expense' => floatval($totalExpense),
                'net_amount' => floatval($netAmount),
            ];

            // Pagination
            $perPage = $request->input('per_page', 20);
            $paginatedTransactions = $query->paginate($perPage);

            // Format response
            $transactions = collect($paginatedTransactions->items())->map(function ($transaction) {
                return $this->formatTransaction($transaction);
            });

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'summary' => $summary,
                'pagination' => [
                    'total' => $paginatedTransactions->total(),
                    'per_page' => $paginatedTransactions->perPage(),
                    'current_page' => $paginatedTransactions->currentPage(),
                    'last_page' => $paginatedTransactions->lastPage(),
                    'from' => $paginatedTransactions->firstItem(),
                    'to' => $paginatedTransactions->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch investment transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single transaction details
     * GET /api/investment-transactions/{id}
     */
    public function show($id)
    {
        try {
            $transaction = ProjectTransaction::with([
                'project',
                'creator',
                'relatedShare.investor'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatTransaction($transaction, true),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }
    }

    /**
     * Create new investment transaction (Profit or Expense only)
     * POST /api/investment-transactions
     */
    public function store(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
                'amount' => 'required|numeric|min:0',
                'type' => 'required|in:income,expense',
                'description' => 'required|string|max:500',
                'transaction_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Verify project exists and is not cancelled
            $project = Project::findOrFail($request->project_id);
            if ($project->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add transactions to cancelled projects',
                ], 400);
            }

            DB::beginTransaction();

            // Determine source based on type (must match database enum values)
            $source = $request->type === 'income' ? 'project_profit' : 'project_expense';

            // Get user ID from request (sent by Utils.http_post)
            $userId = $request->user_id ?? $request->input('User-Id') ?? Auth::id();

            // Create transaction
            $transaction = ProjectTransaction::create([
                'project_id' => $request->project_id,
                'amount' => $request->amount,
                'type' => $request->type,
                'source' => $source,
                'description' => $request->description,
                'transaction_date' => $request->transaction_date,
                'created_by_id' => $userId,
            ]);

            // Update project net profit
            $this->updateProjectNetProfit($project->id);

            DB::commit();

            Log::info('Investment transaction created', [
                'transaction_id' => $transaction->id,
                'project_id' => $project->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'created_by' => $userId,
            ]);

            return Utils::success(
                $this->formatTransaction($transaction->fresh(['project', 'creator'])),
                'Transaction created successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create investment transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Utils::error('Failed to create transaction: ' . $e->getMessage());
        }
    }

    /**
     * Update investment transaction
     * PUT /api/investment-transactions/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $transaction = ProjectTransaction::findOrFail($id);

            // Check if transaction is automated (cannot edit automated transactions)
            if (in_array($transaction->source, ['share_purchase', 'returns_distribution'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit automated transactions (Share Purchase or Returns Distribution)',
                ], 403);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'amount' => 'sometimes|numeric|min:0',
                'type' => 'sometimes|in:income,expense',
                'description' => 'sometimes|string|max:500',
                'transaction_date' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Update transaction
            $transaction->update($request->only([
                'amount',
                'type',
                'description',
                'transaction_date',
            ]));

            // Update project net profit
            $this->updateProjectNetProfit($transaction->project_id);

            DB::commit();

            Log::info('Investment transaction updated', [
                'transaction_id' => $transaction->id,
                'updated_by' => Auth::id(),
            ]);

            return Utils::success(
                $this->formatTransaction($transaction->fresh(['project', 'creator'])),
                'Transaction updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update investment transaction', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return Utils::error('Failed to update transaction: ' . $e->getMessage());
        }
    }

    /**
     * Delete investment transaction (soft delete)
     * DELETE /api/investment-transactions/{id}
     */
    public function destroy($id)
    {
        try {
            $transaction = ProjectTransaction::findOrFail($id);

            // Check if transaction is automated (cannot delete automated transactions)
            if (in_array($transaction->source, ['share_purchase', 'returns_distribution'])) {
                return Utils::error('Cannot delete automated transactions (Share Purchase or Returns Distribution)');
            }

            DB::beginTransaction();

            $projectId = $transaction->project_id;

            // Soft delete transaction
            $transaction->delete();

            // Update project net profit
            $this->updateProjectNetProfit($projectId);

            DB::commit();

            Log::info('Investment transaction deleted', [
                'transaction_id' => $id,
                'deleted_by' => Auth::id(),
            ]);

            return Utils::success([], 'Transaction deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete investment transaction', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return Utils::error('Failed to delete transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get projects list for dropdown
     * GET /api/investment-transactions/projects
     */
    public function getProjects()
    {
        try {
            $projects = Project::select('id', 'title', 'status', 'image')
                ->whereIn('status', ['ongoing', 'completed'])
                ->orderBy('title')
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'title' => $project->title,
                        'status' => $project->status,
                        'status_label' => ucfirst($project->status),
                        'image' => $project->image,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load projects',
            ], 500);
        }
    }

    /**
     * Format transaction for response
     */
    private function formatTransaction($transaction, $detailed = false)
    {
        $data = [
            'id' => $transaction->id,
            'project_id' => $transaction->project_id,
            'project_title' => $transaction->project ? $transaction->project->title : 'N/A',
            'project_image' => $transaction->project ? $transaction->project->image : null,
            'project_status' => $transaction->project ? $transaction->project->status : null,
            'amount' => floatval($transaction->amount),
            'type' => $transaction->type ?? 'expense',
            'type_label' => $transaction->type_label ?? 'Expense',
            'source' => $transaction->source ?? 'manual',
            'source_label' => $transaction->source_label ?? 'Manual',
            'description' => $transaction->description ?? '',
            'transaction_date' => $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : now()->format('Y-m-d'),
            'formatted_amount' => $transaction->formatted_amount ?? '0',
            'created_by' => $transaction->creator ? $transaction->creator->name : 'System',
            'created_by_id' => $transaction->created_by_id,
            'is_automated' => in_array($transaction->source, ['share_purchase', 'returns_distribution']),
            'can_edit' => !in_array($transaction->source, ['share_purchase', 'returns_distribution']),
            'can_delete' => !in_array($transaction->source, ['share_purchase', 'returns_distribution']),
            'created_at' => $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : null,
        ];

        if ($detailed) {
            $data['updated_at'] = $transaction->updated_at ? $transaction->updated_at->format('Y-m-d H:i:s') : null;
            $data['related_share_id'] = $transaction->related_share_id;
            
            if ($transaction->relatedShare) {
                $data['related_investor'] = [
                    'id' => $transaction->relatedShare->investor_id,
                    'name' => $transaction->relatedShare->investor ? $transaction->relatedShare->investor->name : 'N/A',
                    'shares' => $transaction->relatedShare->number_of_shares,
                ];
            }
        }

        return $data;
    }

    /**
     * Update project net profit after transaction changes
     */
    private function updateProjectNetProfit($projectId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return;
        }

        $income = ProjectTransaction::where('project_id', $projectId)
            ->where('type', 'income')
            ->sum('amount');

        $expense = ProjectTransaction::where('project_id', $projectId)
            ->where('type', 'expense')
            ->sum('amount');

        $netProfit = floatval($income) - floatval($expense);

        $project->update(['net_profit' => $netProfit]);

        Log::info('Project net profit updated', [
            'project_id' => $projectId,
            'income' => $income,
            'expense' => $expense,
            'net_profit' => $netProfit,
        ]);
    }
}
