<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectTransactionController extends Controller
{
    /**
     * Display a listing of transactions for a project.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = ProjectTransaction::with(['project', 'creator'])
                ->orderBy('transaction_date', 'desc');

            // Filter by project
            if ($request->has('project_id') && !empty($request->project_id)) {
                $query->where('project_id', $request->project_id);
            }

            // Filter by type
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Filter by source
            if ($request->has('source') && !empty($request->source)) {
                $query->where('source', $request->source);
            }

            $transactions = $query->get();

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
                'amount' => 'required|numeric|min:0',
                'transaction_date' => 'required|date',
                'description' => 'required|string',
                'type' => 'required|in:income,expense',
                'source' => 'required|in:share_purchase,project_profit,project_expense,returns_distribution',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $data = $validator->validated();
            $data['created_by_id'] = Auth::id();

            // Create transaction
            $transaction = ProjectTransaction::create($data);

            // Update project computed fields
            $project = Project::find($data['project_id']);
            $project->updateComputedFields();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction->load(['project', 'creator']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $transaction = ProjectTransaction::with(['project', 'creator', 'relatedShare'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $transaction = ProjectTransaction::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'amount' => 'sometimes|required|numeric|min:0',
                'transaction_date' => 'sometimes|required|date',
                'description' => 'sometimes|required|string',
                'type' => 'sometimes|required|in:income,expense',
                'source' => 'sometimes|required|in:share_purchase,project_profit,project_expense,returns_distribution',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $transaction->update($validator->validated());

            // Update project computed fields
            $project = Project::find($transaction->project_id);
            $project->updateComputedFields();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully',
                'data' => $transaction->load(['project', 'creator']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified transaction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $transaction = ProjectTransaction::findOrFail($id);
            $projectId = $transaction->project_id;

            $transaction->delete();

            // Update project computed fields
            $project = Project::find($projectId);
            $project->updateComputedFields();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
