<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OrderedItem extends Model
{
    use HasFactory;
    
    protected $table = 'ordered_items';
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order',
        'product',
        'qty',
        'amount',
        'unit_price',
        'subtotal',
        'color',
        'size',
        // Payment tracking
        'item_is_paid',
        'item_paid_date',
        'item_paid_amount',
        // DTEHM Seller
        'has_detehm_seller',
        'dtehm_seller_id',
        'dtehm_user_id',
        // Commission processing
        'commission_is_processed',
        'commission_processed_date',
        'total_commission_amount',
        'balance_after_commission',
        'commission_seller',
        'commission_parent_1',
        'commission_parent_2',
        'commission_parent_3',
        'commission_parent_4',
        'commission_parent_5',
        'commission_parent_6',
        'commission_parent_7',
        'commission_parent_8',
        'commission_parent_9',
        'commission_parent_10',
        'parent_1_user_id',
        'parent_2_user_id',
        'parent_3_user_id',
        'parent_4_user_id',
        'parent_5_user_id',
        'parent_6_user_id',
        'parent_7_user_id',
        'parent_8_user_id',
        'parent_9_user_id',
        'parent_10_user_id',
    ];

    /**
     * Boot method - automatically set prices when saving
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($item) {
            // Get product to fetch price if unit_price is not set or is zero
            if ((empty($item->unit_price) || $item->unit_price == 0) && $item->product) {
                $product = Product::find($item->product);
                if ($product) {
                    $item->unit_price = $product->price_1;
                    Log::info("OrderedItem boot: Set unit_price from product {$product->name}: {$product->price_1}");
                }
            }
            
            // Ensure unit_price is numeric
            $item->unit_price = floatval($item->unit_price ?? 0);
            
            // Ensure quantity is numeric
            $quantity = floatval($item->qty ?? 1);
            
            // Calculate subtotal
            $item->subtotal = $item->unit_price * $quantity;
            
            // Set amount for backward compatibility
            $item->amount = $item->unit_price;
            
            Log::info("OrderedItem saving: Product {$item->product}, Qty: {$quantity}, Unit Price: {$item->unit_price}, Subtotal: {$item->subtotal}");
        });
        
        // Auto-process commission when item is marked as paid
        static::saved(function ($item) {
            // Check if item_is_paid was just changed to 'Yes'
            if ($item->item_is_paid === 'Yes' && 
                $item->has_detehm_seller === 'Yes' && 
                $item->commission_is_processed !== 'Yes') {
                
                Log::info("OrderedItem paid - triggering commission processing", [
                    'item_id' => $item->id,
                    'seller_id' => $item->dtehm_user_id,
                ]);
                
                // Process commission asynchronously (optional: can be queued for better performance)
                try {
                    $commissionService = new \App\Services\CommissionService();
                    $result = $commissionService->processCommission($item);
                    
                    if ($result['success']) {
                        Log::info("Commission auto-processed successfully", $result);
                    } else {
                        Log::warning("Commission auto-processing failed", $result);
                    }
                } catch (\Exception $e) {
                    Log::error("Commission auto-processing exception", [
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * Belongs to Product
     */
    public function pro()
    {
        return $this->belongsTo(Product::class, 'product')->withDefault([
            'name' => 'Product Not Found',
            'price_1' => 0,
            'feature_photo' => null,
        ]);
    }
    
    /**
     * Belongs to Order - renamed to avoid conflict with 'order' column
     */
    public function parentOrder()
    {
        return $this->belongsTo(Order::class, 'order')->withDefault();
    }
}
