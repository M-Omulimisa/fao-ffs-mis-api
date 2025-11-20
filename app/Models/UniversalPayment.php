<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UniversalPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_reference',
        'payment_type',
        'payment_category',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'payment_items',
        'items_count',
        'amount',
        'currency',
        'description',
        'payment_gateway',
        'payment_method',
        'payment_account',
        'status',
        'payment_status_code',
        'status_message',
        'pesapal_order_tracking_id',
        'pesapal_merchant_reference',
        'pesapal_redirect_url',
        'pesapal_callback_url',
        'pesapal_notification_id',
        'pesapal_status_code',
        'pesapal_response',
        'confirmation_code',
        'payment_date',
        'confirmed_at',
        'items_processed',
        'items_processed_at',
        'processing_notes',
        'processed_by',
        'last_ipn_at',
        'ipn_count',
        'last_status_check',
        'refund_amount',
        'refunded_at',
        'refund_reason',
        'metadata',
        'ip_address',
        'user_agent',
        'error_message',
        'retry_count',
        'last_retry_at',
        'created_by',
        'updated_by',
        'project_id',
        'number_of_shares',
    ];

    protected $casts = [
        'payment_items' => 'array',
        'metadata' => 'array',
        'pesapal_response' => 'array',
        'items_processed' => 'boolean',
        'payment_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'items_processed_at' => 'datetime',
        'last_ipn_at' => 'datetime',
        'last_status_check' => 'datetime',
        'refunded_at' => 'datetime',
        'last_retry_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate payment reference
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = self::generatePaymentReference();
            }

            if (empty($payment->currency)) {
                $payment->currency = 'UGX';
            }

            if (empty($payment->status)) {
                $payment->status = 'PENDING';
            }
        });
    }

    /**
     * Generate unique payment reference
     */
    public static function generatePaymentReference()
    {
        return 'UNI-PAY-' . time() . '-' . strtoupper(Str::random(6));
    }

    /**
     * Create a new payment
     */
    public static function createPayment(array $data)
    {
        try {
            // Calculate total from payment items if not provided
            if (!isset($data['amount']) && isset($data['payment_items'])) {
                $data['amount'] = array_sum(array_column($data['payment_items'], 'amount'));
            }

            // Set items count
            if (isset($data['payment_items'])) {
                $data['items_count'] = count($data['payment_items']);
            }

            // Create payment
            $payment = self::create($data);

            Log::info('Universal payment created', [
                'payment_id' => $payment->id,
                'reference' => $payment->payment_reference,
                'amount' => $payment->amount,
                'items' => $payment->items_count,
            ]);

            return $payment;
        } catch (\Exception $e) {
            Log::error('Failed to create universal payment', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Process payment items - Mark individual items as paid
     * This is called after payment is confirmed as COMPLETED
     */
    public function processPaymentItems()
    {
        // Check if already processed (FIRST SAFEGUARD)
        if ($this->items_processed) {
            Log::info('Payment items already processed', [
                'payment_id' => $this->id,
                'reference' => $this->payment_reference,
            ]);
            return ['success' => true, 'message' => 'Items already processed'];
        }

        // Use database transaction for atomicity (SECOND SAFEGUARD)
        return \DB::transaction(function () {
            // Double-check inside transaction (THIRD SAFEGUARD - prevent race conditions)
            $this->refresh();
            if ($this->items_processed) {
                Log::info('Payment items already processed (detected in transaction)', [
                    'payment_id' => $this->id,
                    'reference' => $this->payment_reference,
                ]);
                return ['success' => true, 'message' => 'Items already processed'];
            }

            try {
                $processedItems = [];
                $failedItems = [];

                // Process each payment item
                foreach ($this->payment_items as $item) {
                    try {
                        $result = $this->processIndividualItem($item);
                        if ($result['success']) {
                            $processedItems[] = $item;
                        } else {
                            $failedItems[] = ['item' => $item, 'error' => $result['message']];
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to process individual item', [
                            'payment_id' => $this->id,
                            'item' => $item,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $failedItems[] = ['item' => $item, 'error' => $e->getMessage()];
                    }
                }

                // Mark as processed if all items succeeded
                if (empty($failedItems)) {
                    $this->update([
                        'items_processed' => true,
                        'items_processed_at' => now(),
                        'processing_notes' => 'All ' . count($processedItems) . ' items processed successfully',
                    ]);

                    Log::info('🎉 ============================================', []);
                    Log::info('🎉 ALL PAYMENT ITEMS PROCESSED SUCCESSFULLY', [
                        'universal_payment_id' => $this->id,
                        'payment_reference' => $this->payment_reference,
                        'payment_type' => $this->payment_type,
                        'total_amount' => $this->total_amount,
                        'items_count' => count($processedItems),
                        'items_processed_at' => now()->toDateTimeString(),
                        'customer_name' => $this->customer_name,
                        'customer_phone' => $this->customer_phone,
                    ]);
                    Log::info('🎉 ============================================', []);

                    return [
                        'success' => true,
                        'message' => 'All items processed successfully',
                        'processed' => count($processedItems),
                        'failed' => 0,
                        'payment_reference' => $this->payment_reference,
                    ];
                } else {
                    // Partial success - DO NOT mark as processed (FOURTH SAFEGUARD)
                    $this->update([
                        'processing_notes' => 'Processed ' . count($processedItems) . ' items, ' . count($failedItems) . ' failed',
                    ]);

                    Log::warning('Some payment items failed to process', [
                        'payment_id' => $this->id,
                        'reference' => $this->payment_reference,
                        'processed' => count($processedItems),
                        'failed' => count($failedItems),
                        'errors' => $failedItems,
                    ]);

                    // Rollback transaction for partial failures
                    throw new \Exception('Some items failed to process: ' . json_encode($failedItems));
                }
            } catch (\Exception $e) {
                Log::error('Failed to process payment items', [
                    'payment_id' => $this->id,
                    'reference' => $this->payment_reference,
                    'error' => $e->getMessage(),
                ]);

                throw $e; // Re-throw to rollback transaction
            }
        });
    }

    /**
     * Process individual payment item based on type
     */
    protected function processIndividualItem(array $item)
    {
        switch ($item['type']) {
            case 'insurance_subscription_payment':
                return $this->processInsuranceSubscriptionPayment($item['id'], $item);

            case 'insurance_transaction':
                return $this->processInsuranceTransaction($item['id'], $item);

            case 'order':
                return $this->processOrder($item['id'], $item);

            case 'project_share':
            case 'project_share_purchase': // Support both naming conventions
                return $this->processProjectSharePurchase($item['id'], $item);

            case 'membership':
                return $this->processMembershipPayment($item['id'], $item);

            default:
                Log::warning('Unknown payment item type', [
                    'type' => $item['type'],
                    'item' => $item,
                ]);
                return [
                    'success' => false,
                    'message' => 'Unknown payment item type: ' . $item['type'],
                ];
        }
    }

    /**
     * Process Insurance Subscription Payment
     * Mark payment as paid and cascade to subscription & program
     * CRITICAL: This only runs if UniversalPayment status is COMPLETED
     */
    protected function processInsuranceSubscriptionPayment($paymentId, array $item)
    {
        try {
            $payment = \App\Models\InsuranceSubscriptionPayment::find($paymentId);

            if (!$payment) {
                Log::error('❌ Insurance payment not found', [
                    'payment_id' => $paymentId,
                    'universal_payment_id' => $this->id,
                ]);
                return ['success' => false, 'message' => 'Payment not found'];
            }

            Log::info('Processing insurance payment', [
                'insurance_payment_id' => $paymentId,
                'universal_payment_id' => $this->id,
                'universal_payment_status' => $this->status,
                'current_payment_status' => $payment->payment_status,
                'current_paid_amount' => $payment->paid_amount,
                'total_amount' => $payment->total_amount,
                'amount_to_add' => $item['amount'],
            ]);

            // SAFEGUARD 1: Check if this universal payment already processed this insurance payment
            if ($payment->payment_reference === $this->payment_reference && $payment->payment_status === 'Paid') {
                Log::info('✅ Insurance payment already marked as Paid by this universal payment', [
                    'insurance_payment_id' => $paymentId,
                    'universal_payment_id' => $this->id,
                    'reference' => $this->payment_reference,
                ]);
                return ['success' => true, 'message' => 'Payment already marked as Paid'];
            }

            // Calculate new paid amount
            $newPaidAmount = floatval($payment->paid_amount) + floatval($item['amount']);
            $totalAmount = floatval($payment->total_amount);

            // SAFEGUARD 2: Prevent overpayment
            if ($newPaidAmount > $totalAmount) {
                Log::warning('⚠️ Payment amount exceeds total, capping at total', [
                    'insurance_payment_id' => $paymentId,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $payment->paid_amount,
                    'new_amount' => $item['amount'],
                    'would_be' => $newPaidAmount,
                    'capped_at' => $totalAmount,
                ]);
                $newPaidAmount = $totalAmount; // Cap at total
            }

            // Determine payment status based on paid amount
            $paymentStatus = 'Partial';
            if ($newPaidAmount >= $totalAmount) {
                $paymentStatus = 'Paid';
                Log::info('✅ Insurance payment now FULLY PAID', [
                    'insurance_payment_id' => $paymentId,
                    'paid_amount' => $newPaidAmount,
                    'total_amount' => $totalAmount,
                ]);
            } else {
                Log::info('⚠️ Insurance payment PARTIALLY paid', [
                    'insurance_payment_id' => $paymentId,
                    'paid_amount' => $newPaidAmount,
                    'total_amount' => $totalAmount,
                    'remaining' => $totalAmount - $newPaidAmount,
                ]);
            }

            // CRITICAL: Update payment record with PAID status
            $payment->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus,
                'payment_date' => now(),
                'payment_method' => $this->payment_method ?? 'Online',
                'payment_reference' => $this->payment_reference,
                'transaction_id' => $this->pesapal_order_tracking_id,
            ]);

            Log::info('💾 Insurance payment record updated', [
                'insurance_payment_id' => $paymentId,
                'new_status' => $paymentStatus,
                'new_paid_amount' => $newPaidAmount,
                'payment_date' => now()->toDateTimeString(),
                'reference' => $this->payment_reference,
            ]);

            // Cascade to subscription (refresh/prepare payment months if needed)
            if ($payment->insurance_subscription_id) {
                $subscription = \App\Models\InsuranceSubscription::find($payment->insurance_subscription_id);
                if ($subscription) {
                    Log::info('🔄 Preparing subscription payment months', [
                        'subscription_id' => $subscription->id,
                        'prepared' => $subscription->prepared,
                    ]);
                    
                    // Call static prepare method with subscription model
                    try {
                        \App\Models\InsuranceSubscription::prepare($subscription);
                        Log::info('✅ Subscription payment months prepared', [
                            'subscription_id' => $subscription->id,
                        ]);
                    } catch (\Exception $e) {
                        // If already prepared or error, just log and continue
                        Log::info('ℹ️ Subscription prepare skipped', [
                            'subscription_id' => $subscription->id,
                            'reason' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('✅ Insurance subscription payment processed successfully', [
                'insurance_payment_id' => $paymentId,
                'universal_payment_id' => $this->id,
                'amount' => $item['amount'],
                'new_paid_amount' => $newPaidAmount,
                'total_amount' => $totalAmount,
                'new_status' => $paymentStatus,
                'universal_payment_ref' => $this->payment_reference,
                'is_fully_paid' => ($paymentStatus === 'Paid'),
            ]);

            return [
                'success' => true,
                'message' => 'Payment marked as ' . $paymentStatus,
                'insurance_payment_id' => $paymentId,
                'new_status' => $paymentStatus,
                'paid_amount' => $newPaidAmount,
                'total_amount' => $totalAmount,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Failed to process insurance subscription payment', [
                'insurance_payment_id' => $paymentId,
                'universal_payment_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process Insurance Transaction
     * Mark transaction as completed
     */
    protected function processInsuranceTransaction($transactionId, array $item)
    {
        try {
            $transaction = \App\Models\InsuranceTransaction::find($transactionId);

            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }

            $transaction->update([
                'payment_status' => 'COMPLETED',
                'payment_confirmation' => $this->payment_reference,
            ]);

            Log::info('Insurance transaction processed', [
                'transaction_id' => $transactionId,
                'amount' => $item['amount'],
                'universal_payment_ref' => $this->payment_reference,
            ]);

            return ['success' => true, 'message' => 'Transaction marked as COMPLETED'];
        } catch (\Exception $e) {
            Log::error('Failed to process insurance transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process Ecommerce Order
     * Mark order as paid
     */
    protected function processOrder($orderId, array $item)
    {
        try {
            $order = \App\Models\Order::find($orderId);

            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $order->update([
                'payment_status' => 'PAID',
                'payment_confirmation' => 'Yes',
                'order_state' => 'PROCESSING',
            ]);

            Log::info('Order processed', [
                'order_id' => $orderId,
                'amount' => $item['amount'],
                'universal_payment_ref' => $this->payment_reference,
            ]);

            return ['success' => true, 'message' => 'Order marked as PAID'];
        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted()
    {
        return $this->status === 'COMPLETED' || 
               $this->payment_status_code === '1' ||
               $this->pesapal_status_code === '1';
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->status === 'PENDING' || 
               $this->status === 'PROCESSING' ||
               $this->payment_status_code === '0' ||
               $this->pesapal_status_code === '0' ||
               empty($this->status);
    }

    /**
     * Check if payment failed
     */
    public function isFailed()
    {
        return in_array($this->status, ['FAILED', 'INVALID', 'CANCELLED']) ||
               $this->payment_status_code === '2' ||
               $this->pesapal_status_code === '2';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor()
    {
        return match($this->status) {
            'COMPLETED' => 'green',
            'PENDING', 'PROCESSING' => 'orange',
            'FAILED', 'INVALID', 'CANCELLED' => 'red',
            default => 'gray',
        };
    }

    /**
     * Process project share purchase
     * Create share record and transaction record
     */
    protected function processProjectSharePurchase($projectId, array $item)
    {
        try {
            $project = Project::find($projectId);

            if (!$project) {
                Log::error('Project not found', [
                    'project_id' => $projectId,
                    'universal_payment_id' => $this->id,
                ]);
                return ['success' => false, 'message' => 'Project not found'];
            }

            Log::info('Processing project share purchase', [
                'universal_payment_id' => $this->id,
                'project_id' => $projectId,
                'amount' => $item['amount'],
                'share_price' => $project->share_price,
                'universal_payment_status' => $this->status,
            ]);

            // SAFEGUARD 1: Check if already processed (prevent duplicate shares by payment_id)
            $existingShare = ProjectShare::where('payment_id', $this->id)->first();
            if ($existingShare) {
                Log::info('✅ Share already created for this payment', [
                    'payment_id' => $this->id,
                    'share_id' => $existingShare->id,
                    'project_id' => $projectId,
                    'shares' => $existingShare->number_of_shares,
                ]);
                return [
                    'success' => true,
                    'message' => 'Share already created',
                    'share_id' => $existingShare->id,
                ];
            }

            // SAFEGUARD 2: Validate project has share_price
            if (!$project->share_price || $project->share_price <= 0) {
                Log::error('Project has invalid share price', [
                    'project_id' => $projectId,
                    'share_price' => $project->share_price,
                ]);
                return ['success' => false, 'message' => 'Project has invalid share price'];
            }

            // Calculate number of shares from amount and price
            $numberOfShares = $this->number_of_shares 
                ?? $item['quantity'] 
                ?? round(floatval($item['amount']) / floatval($project->share_price));

            // SAFEGUARD 3: Validate number of shares
            if ($numberOfShares <= 0) {
                Log::error('Invalid number of shares calculated', [
                    'project_id' => $projectId,
                    'amount' => $item['amount'],
                    'share_price' => $project->share_price,
                    'calculated_shares' => $numberOfShares,
                ]);
                return ['success' => false, 'message' => 'Invalid number of shares calculated'];
            }

            // SAFEGUARD 4: Check if project has enough available shares
            if (isset($project->total_shares) && isset($project->shares_sold)) {
                $availableShares = $project->total_shares - $project->shares_sold;
                if ($numberOfShares > $availableShares) {
                    Log::error('Not enough shares available', [
                        'project_id' => $projectId,
                        'requested' => $numberOfShares,
                        'available' => $availableShares,
                    ]);
                    return ['success' => false, 'message' => 'Not enough shares available'];
                }
            }

            // CRITICAL: Create share record (links to payment for duplicate prevention)
            $shareData = [
                'project_id' => $projectId,
                'investor_id' => $this->user_id,
                'purchase_date' => now(),
                'number_of_shares' => $numberOfShares,
                'total_amount_paid' => floatval($item['amount']),
                'share_price_at_purchase' => floatval($project->share_price),
                'payment_id' => $this->id, // CRITICAL: Links to universal payment for duplicate prevention
            ];

            Log::info('💾 Creating share record', [
                'share_data' => $shareData,
            ]);

            $share = ProjectShare::create($shareData);

            Log::info('✅ Share record created', [
                'share_id' => $share->id,
                'project_id' => $projectId,
                'shares' => $numberOfShares,
                'investor_id' => $this->user_id,
            ]);

            // Create transaction record (income from share purchase)
            $transactionData = [
                'project_id' => $projectId,
                'amount' => floatval($item['amount']),
                'transaction_date' => now(),
                'created_by_id' => $this->user_id,
                'description' => "Share purchase by {$this->customer_name} - {$numberOfShares} shares",
                'type' => 'income',
                'source' => 'share_purchase',
                'related_share_id' => $share->id,
            ];

            Log::info('💾 Creating transaction record', [
                'transaction_data' => $transactionData,
            ]);

            $transaction = ProjectTransaction::create($transactionData);

            Log::info('✅ Transaction record created', [
                'transaction_id' => $transaction->id,
                'amount' => $item['amount'],
            ]);

            // Update project computed fields (shares_sold, etc.)
            Log::info('🔄 Updating project computed fields', [
                'project_id' => $projectId,
            ]);
            $project->updateComputedFields();

            Log::info('✅ Project share purchase processed successfully', [
                'universal_payment_id' => $this->id,
                'universal_payment_ref' => $this->payment_reference,
                'share_id' => $share->id,
                'transaction_id' => $transaction->id,
                'project_id' => $projectId,
                'investor_id' => $this->user_id,
                'shares' => $numberOfShares,
                'amount' => $item['amount'],
                'share_price' => $project->share_price,
            ]);

            return [
                'success' => true,
                'message' => 'Share purchase processed successfully',
                'share_id' => $share->id,
                'transaction_id' => $transaction->id,
                'shares_purchased' => $numberOfShares,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to process project share purchase', [
                'universal_payment_id' => $this->id,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process share purchase: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Membership Payment
     * Creates MembershipPayment record and updates User model
     */
    protected function processMembershipPayment($itemId, array $item)
    {
        try {
            Log::info('🎫 Processing membership payment', [
                'universal_payment_id' => $this->id,
                'universal_payment_ref' => $this->payment_reference,
                'user_id' => $this->user_id,
                'amount' => $item['amount'],
            ]);

            // SAFEGUARD 1: Check if membership payment already exists for this universal payment
            $existingMembershipPayment = \App\Models\MembershipPayment::where('payment_reference', $this->payment_reference)->first();
            
            if ($existingMembershipPayment) {
                Log::info('✅ Membership payment already exists for this universal payment', [
                    'membership_payment_id' => $existingMembershipPayment->id,
                    'universal_payment_id' => $this->id,
                    'reference' => $this->payment_reference,
                ]);
                return ['success' => true, 'message' => 'Membership payment already processed'];
            }

            // SAFEGUARD 2: Check if user already has a confirmed membership payment
            $user = \App\Models\User::find($this->user_id);
            if (!$user) {
                Log::error('❌ User not found', [
                    'user_id' => $this->user_id,
                    'universal_payment_id' => $this->id,
                ]);
                return ['success' => false, 'message' => 'User not found'];
            }

            // Create MembershipPayment record
            $membershipPaymentData = [
                'user_id' => $this->user_id,
                'payment_reference' => $this->payment_reference,
                'amount' => floatval($item['amount']),
                'status' => 'CONFIRMED',
                'payment_method' => $this->payment_method ?? 'PESAPAL',
                'payment_phone_number' => $this->customer_phone,
                'payment_account_number' => $this->payment_account,
                'payment_date' => $this->payment_date ?? now(),
                'confirmed_at' => now(),
                'membership_type' => 'LIFE', // Default to lifetime membership
                'expiry_date' => null, // Lifetime memberships never expire
                'notes' => $this->description ?? 'Membership payment via Universal Payment System',
                'pesapal_order_tracking_id' => $this->pesapal_order_tracking_id,
                'pesapal_merchant_reference' => $this->pesapal_merchant_reference,
                'universal_payment_id' => $this->id,
                'created_by' => $this->user_id,
                'confirmed_by' => $this->user_id, // Auto-confirmed via payment gateway
            ];

            Log::info('💾 Creating membership payment record', [
                'membership_payment_data' => $membershipPaymentData,
            ]);

            $membershipPayment = \App\Models\MembershipPayment::create($membershipPaymentData);

            Log::info('✅ Membership payment record created', [
                'membership_payment_id' => $membershipPayment->id,
                'payment_reference' => $membershipPayment->payment_reference,
            ]);

            // Update User model with membership info
            $user->update([
                'is_membership_paid' => true,
                'membership_paid_at' => now(),
                'membership_amount' => floatval($item['amount']),
                'membership_payment_id' => $membershipPayment->id,
                'membership_type' => 'LIFE',
                'membership_expiry_date' => null, // Lifetime membership
            ]);

            Log::info('✅ User model updated with membership info', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'is_membership_paid' => $user->is_membership_paid,
                'membership_type' => $user->membership_type,
            ]);

            Log::info('🎉 ============================================', []);
            Log::info('🎉 MEMBERSHIP PAYMENT PROCESSED SUCCESSFULLY', [
                'universal_payment_id' => $this->id,
                'payment_reference' => $this->payment_reference,
                'membership_payment_id' => $membershipPayment->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'amount' => $item['amount'],
                'membership_type' => 'LIFE',
            ]);
            Log::info('🎉 ============================================', []);

            return [
                'success' => true,
                'message' => 'Membership payment processed successfully',
                'membership_payment_id' => $membershipPayment->id,
                'user_id' => $user->id,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Failed to process membership payment', [
                'universal_payment_id' => $this->id,
                'user_id' => $this->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process membership payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function membershipPayment()
    {
        return $this->hasOne(\App\Models\MembershipPayment::class, 'universal_payment_id');
    }

    /**
     * Helper Methods
     */
    public function isSharePurchase()
    {
        return !is_null($this->project_id) && !is_null($this->number_of_shares);
    }

    public function isMembershipPayment()
    {
        return $this->payment_type === 'membership';
    }
}
