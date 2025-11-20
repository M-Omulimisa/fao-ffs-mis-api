<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CommissionService
{
    /**
     * Commission rates for each level (in percentage)
     */
    const COMMISSION_RATES = [
        'seller' => 10.0,    // 10%
        'parent_1' => 3.0,   // 3%
        'parent_2' => 2.5,   // 2.5%
        'parent_3' => 2.0,   // 2.0%
        'parent_4' => 1.5,   // 1.5%
        'parent_5' => 1.0,   // 1.0%
        'parent_6' => 0.8,   // 0.8%
        'parent_7' => 0.6,   // 0.6%
        'parent_8' => 0.4,   // 0.4%
        'parent_9' => 0.3,   // 0.3%
        'parent_10' => 0.2,  // 0.2%
    ];

    /**
     * Process commissions for an ordered item
     * 
     * @param OrderedItem $orderedItem
     * @return array Result with success status and details
     */
    public function processCommission(OrderedItem $orderedItem)
    {
        // Validation checks
        if ($orderedItem->commission_is_processed === 'Yes') {
            return [
                'success' => false,
                'message' => 'Commission already processed for this item',
                'item_id' => $orderedItem->id,
            ];
        }

        if ($orderedItem->item_is_paid !== 'Yes') {
            return [
                'success' => false,
                'message' => 'Item must be paid before processing commission',
                'item_id' => $orderedItem->id,
            ];
        }

        if ($orderedItem->has_detehm_seller !== 'Yes' || empty($orderedItem->dtehm_user_id)) {
            return [
                'success' => false,
                'message' => 'No DTEHM seller associated with this item',
                'item_id' => $orderedItem->id,
            ];
        }

        // Begin transaction for data integrity
        DB::beginTransaction();

        try {
            $seller = User::find($orderedItem->dtehm_user_id);

            if (!$seller) {
                throw new Exception("Seller user not found: ID {$orderedItem->dtehm_user_id}");
            }

            $itemSubtotal = floatval($orderedItem->subtotal ?? $orderedItem->item_paid_amount ?? 0);

            if ($itemSubtotal <= 0) {
                throw new Exception("Invalid item subtotal: {$itemSubtotal}");
            }

            Log::info("Starting commission processing", [
                'item_id' => $orderedItem->id,
                'seller_id' => $seller->id,
                'seller_name' => $seller->name,
                'subtotal' => $itemSubtotal,
            ]);

            $commissionsProcessed = [];
            $totalCommissionAmount = 0;

            // Process seller commission (10%)
            $sellerCommission = $this->calculateCommission($itemSubtotal, self::COMMISSION_RATES['seller']);
            $sellerTransaction = $this->createCommissionTransaction(
                $seller,
                $sellerCommission,
                $orderedItem,
                'Seller',
                self::COMMISSION_RATES['seller']
            );

            if ($sellerTransaction) {
                $orderedItem->commission_seller = $sellerCommission;
                $totalCommissionAmount += $sellerCommission;
                $commissionsProcessed[] = [
                    'level' => 'seller',
                    'user_id' => $seller->id,
                    'amount' => $sellerCommission,
                ];
                Log::info("Seller commission processed", [
                    'user_id' => $seller->id,
                    'amount' => $sellerCommission,
                ]);
            }

            // Process parent commissions (Parent 1 to Parent 10)
            for ($level = 1; $level <= 10; $level++) {
                $parentField = "parent_{$level}";
                $parentUserId = $seller->$parentField;

                // Store parent user ID in ordered_item for tracking
                $orderedItem->{"parent_{$level}_user_id"} = $parentUserId;

                if (empty($parentUserId)) {
                    Log::info("No parent at level {$level}, skipping");
                    continue;
                }

                $parentUser = User::find($parentUserId);

                if (!$parentUser) {
                    Log::warning("Parent user not found", [
                        'level' => $level,
                        'parent_id' => $parentUserId,
                    ]);
                    continue;
                }

                $commissionRate = self::COMMISSION_RATES["parent_{$level}"];
                $commissionAmount = $this->calculateCommission($itemSubtotal, $commissionRate);

                $parentTransaction = $this->createCommissionTransaction(
                    $parentUser,
                    $commissionAmount,
                    $orderedItem,
                    "Parent Level {$level}",
                    $commissionRate
                );

                if ($parentTransaction) {
                    $orderedItem->{"commission_parent_{$level}"} = $commissionAmount;
                    $totalCommissionAmount += $commissionAmount;
                    $commissionsProcessed[] = [
                        'level' => $level,
                        'user_id' => $parentUser->id,
                        'user_name' => $parentUser->name,
                        'amount' => $commissionAmount,
                    ];
                    Log::info("Parent {$level} commission processed", [
                        'user_id' => $parentUser->id,
                        'amount' => $commissionAmount,
                    ]);
                }
            }

            // Update ordered item with commission info
            $orderedItem->commission_is_processed = 'Yes';
            $orderedItem->commission_processed_date = now();
            $orderedItem->total_commission_amount = $totalCommissionAmount;
            $orderedItem->balance_after_commission = $itemSubtotal - $totalCommissionAmount;
            $orderedItem->save();

            DB::commit();

            Log::info("Commission processing completed successfully", [
                'item_id' => $orderedItem->id,
                'total_commission' => $totalCommissionAmount,
                'beneficiaries' => count($commissionsProcessed),
            ]);

            return [
                'success' => true,
                'message' => 'Commission processed successfully',
                'item_id' => $orderedItem->id,
                'total_commission' => $totalCommissionAmount,
                'beneficiaries' => count($commissionsProcessed),
                'commissions' => $commissionsProcessed,
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("Commission processing failed", [
                'item_id' => $orderedItem->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Commission processing failed: ' . $e->getMessage(),
                'item_id' => $orderedItem->id,
            ];
        }
    }

    /**
     * Calculate commission amount
     * 
     * @param float $amount
     * @param float $percentage
     * @return float
     */
    private function calculateCommission($amount, $percentage)
    {
        return round(($amount * $percentage) / 100, 2);
    }

    /**
     * Create account transaction for commission
     * 
     * @param User $user
     * @param float $amount
     * @param OrderedItem $orderedItem
     * @param string $level
     * @param float $percentage
     * @return AccountTransaction|null
     */
    private function createCommissionTransaction(User $user, $amount, OrderedItem $orderedItem, $level, $percentage)
    {
        try {
            $balanceBefore = $user->calculateAccountBalance();
            
            $order = $orderedItem->parentOrder;
            $orderInfo = $order ? "Order #{$order->id}" : "Order N/A";
            if ($order && $order->receipt_number) {
                $orderInfo = "Order {$order->receipt_number}";
            }

            $description = "Commission earned from product sale\n";
            $description .= "{$orderInfo}, Item #{$orderedItem->id}\n";
            $description .= "Level: {$level}, Rate: {$percentage}%\n";
            $description .= "Item Amount: UGX " . number_format($orderedItem->subtotal, 2);

            $transaction = AccountTransaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_date' => now(),
                'description' => $description,
                'source' => 'deposit', // Commission is income (deposit) for the user
                'created_by_id' => $orderedItem->dtehm_user_id, // Seller ID
            ]);

            Log::info("Commission transaction created", [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
            ]);

            return $transaction;

        } catch (Exception $e) {
            Log::error("Failed to create commission transaction", [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process commissions for all items in an order
     * 
     * @param Order $order
     * @return array
     */
    public function processOrderCommissions(Order $order)
    {
        $results = [
            'success' => true,
            'message' => 'Order commissions processing initiated',
            'order_id' => $order->id,
            'items_processed' => 0,
            'items_failed' => 0,
            'total_commission' => 0,
            'details' => [],
        ];

        $orderedItems = OrderedItem::where('order', $order->id)->get();

        foreach ($orderedItems as $item) {
            $result = $this->processCommission($item);
            
            $results['details'][] = $result;

            if ($result['success']) {
                $results['items_processed']++;
                $results['total_commission'] += $result['total_commission'] ?? 0;
            } else {
                $results['items_failed']++;
                $results['success'] = false;
            }
        }

        Log::info("Order commission processing completed", $results);

        return $results;
    }

    /**
     * Get commission summary for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserCommissionSummary($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        // Get all commission transactions
        $commissions = AccountTransaction::where('user_id', $userId)
            ->where('source', 'product_commission')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalEarned = $commissions->sum('amount');
        $transactionCount = $commissions->count();

        // Get commission breakdown by level (from description)
        $byLevel = [
            'seller' => 0,
            'parent_levels' => array_fill(1, 10, 0),
        ];

        foreach ($commissions as $commission) {
            if (str_contains($commission->description, 'Level: Seller')) {
                $byLevel['seller'] += $commission->amount;
            } else {
                for ($i = 1; $i <= 10; $i++) {
                    if (str_contains($commission->description, "Level: Parent Level {$i}")) {
                        $byLevel['parent_levels'][$i] += $commission->amount;
                        break;
                    }
                }
            }
        }

        return [
            'success' => true,
            'user_id' => $userId,
            'user_name' => $user->name,
            'total_earned' => $totalEarned,
            'transaction_count' => $transactionCount,
            'breakdown' => $byLevel,
            'recent_transactions' => $commissions->take(10),
        ];
    }
}
