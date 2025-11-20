<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectShare;
use App\Models\UniversalPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectShareController extends Controller
{
    /**
     * Display shares for the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Get shares for the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserShares(Request $request)
    {
        try {
            $userId = Auth::id();

            $shares = ProjectShare::with(['project', 'payment'])
                ->where('investor_id', $userId)
                ->orderBy('purchase_date', 'desc')
                ->get();

            // Calculate total investment
            $totalInvestment = $shares->sum('total_amount_paid');
            $totalShares = $shares->sum('number_of_shares');

            // Group by project
            $projectsInvested = $shares->groupBy('project_id')->map(function ($projectShares) {
                $project = $projectShares->first()->project;
                return [
                    'project' => $project,
                    'total_shares' => $projectShares->sum('number_of_shares'),
                    'total_invested' => $projectShares->sum('total_amount_paid'),
                    'purchases' => $projectShares,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'shares' => $shares,
                    'summary' => [
                        'total_investment' => $totalInvestment,
                        'total_shares' => $totalShares,
                        'projects_count' => $projectsInvested->count(),
                    ],
                    'projects_invested' => $projectsInvested,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user shares',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate share purchase (creates payment record).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function initiatePurchase(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
                'number_of_shares' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $projectId = $request->project_id;
            $numberOfShares = $request->number_of_shares;

            // Get project
            $project = Project::findOrFail($projectId);

            // Check if project is available for purchase
            if (!$project->available_for_purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'This project is not currently accepting investments',
                ], 400);
            }

            // Calculate total amount
            $totalAmount = $project->share_price * $numberOfShares;

            // Create payment record
            $user = Auth::user();
            $paymentData = [
                'payment_type' => 'project_share_purchase',
                'payment_category' => 'investment',
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone ?? '',
                'amount' => $totalAmount,
                'currency' => 'UGX',
                'description' => "Purchase of {$numberOfShares} shares in {$project->title}",
                'payment_gateway' => 'pesapal',
                'status' => 'PENDING',
                'project_id' => $projectId,
                'number_of_shares' => $numberOfShares,
                'payment_items' => [
                    [
                        'item_type' => 'project_share',
                        'item_id' => $projectId,
                        'name' => $project->title,
                        'quantity' => $numberOfShares,
                        'unit_price' => $project->share_price,
                        'amount' => $totalAmount,
                    ]
                ],
            ];

            $payment = UniversalPayment::createPayment($paymentData);

            return response()->json([
                'success' => true,
                'message' => 'Share purchase initiated successfully',
                'data' => [
                    'payment' => $payment,
                    'project' => $project,
                    'number_of_shares' => $numberOfShares,
                    'total_amount' => $totalAmount,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate share purchase',
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
            $share = ProjectShare::with(['project', 'investor', 'payment'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $share,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Share not found',
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
