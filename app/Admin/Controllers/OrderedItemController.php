<?php

namespace App\Admin\Controllers;

use App\Models\OrderedItem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class OrderedItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Product Sales';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderedItem());
        $grid->model()->orderBy('id', 'desc');
        
        // Disable automatic column display to avoid relationship conflicts
        $grid->disableCreateButton(false);

        // Grid title and description
        $grid->header(function ($query) {
            $total = $query->count();
            $totalRevenue = $query->sum('subtotal');
            return "<div class='alert alert-info'>
                        <strong>Total Sales:</strong> {$total} items | 
                        <strong>Total Revenue:</strong> UGX " . number_format($totalRevenue, 0) . "
                    </div>";
        });

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            // Product filter
            $filter->equal('product', 'Product')->select(\App\Models\Product::pluck('name', 'id'));
            
            // Order ID filter - use simple text input to avoid relationship conflict
            $filter->equal('ordered_items.order', 'Order ID')->placeholder('Enter Order ID');
            
            // Date range filter
            $filter->between('created_at', 'Sale Date')->datetime();
            
            // Quantity range
            $filter->between('qty', 'Quantity');
            
            // Price range
            $filter->between('subtotal', 'Subtotal Amount');
        });

        // Quick search
        $grid->quickSearch('id')->placeholder('Search by ID...');

        // Export
        $grid->export(function ($export) {
            $export->filename('Product Sales');
            $export->column('id', 'ID');
            $export->column('created_at', 'Date');
            $export->column('order', 'Order ID');
            $export->column('product', 'Product ID');
            $export->column('qty', 'Quantity');
            $export->column('unit_price', 'Unit Price');
            $export->column('subtotal', 'Subtotal');
            $export->column('color', 'Color');
            $export->column('size', 'Size');
        });

        // Columns
        $grid->column('id', __('ID'))->sortable()->width(80);
        
        $grid->column('created_at', __('Sale Date'))->display(function ($date) {
            return date('Y-m-d H:i', strtotime($date));
        })->sortable()->width(150);
        
        // Display order ID and receipt number - use getOriginal to avoid relationship conflict
        $grid->column('order_ref', __('Order Reference'))->display(function () {
            $orderId = $this->getOriginal('order');
            if (!$orderId) {
                return '<span class="label label-default">Standalone Sale</span>';
            }
            $order = \App\Models\Order::find($orderId);
            if ($order && $order->receipt_number) {
                return "<a href='/admin/orders/{$orderId}' target='_blank'>#{$orderId} ({$order->receipt_number})</a>";
            }
            return "<a href='/admin/orders/{$orderId}' target='_blank'>Order #{$orderId}</a>";
        })->width(200);
        
        // Display product name with image - use getOriginal to avoid relationship conflict
        $grid->column('product_info', __('Product'))->display(function () {
            $productId = $this->getOriginal('product');
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $image = $product->feature_photo ? "<img src='{$product->feature_photo}' style='width:40px;height:40px;object-fit:cover;margin-right:10px;border-radius:4px;'>" : "";
                return "{$image}<a href='/admin/products/{$productId}' target='_blank'>{$product->name}</a>";
            }
            return "Product #{$productId}";
        })->width(300);
        
        $grid->column('qty', __('Qty'))->sortable()->totalRow(function ($amount) {
            return "<strong>Total: " . number_format($amount, 0) . "</strong>";
        })->width(80);
        
        $grid->column('unit_price', __('Unit Price'))->display(function ($price) {
            return 'UGX ' . number_format($price, 0);
        })->sortable()->width(120);
        
        $grid->column('subtotal', __('Subtotal'))->display(function ($subtotal) {
            return '<strong>UGX ' . number_format($subtotal, 0) . '</strong>';
        })->sortable()->totalRow(function ($amount) {
            return "<strong style='color:#00a65a;'>UGX " . number_format($amount, 0) . "</strong>";
        })->width(150);
        
        // Commission Columns - 10 Levels
        $grid->column('commission_seller', __('Seller (10%)'))->display(function ($amount) {
            if (!$amount || $this->commission_is_processed !== 'Yes') {
                return '<span class="text-muted">-</span>';
            }
            $seller = \App\Models\User::find($this->dtehm_user_id);
            $name = $seller ? $seller->name : 'User #' . $this->dtehm_user_id;
            return "<div style='white-space:nowrap;'><strong>{$name}</strong><br><span style='color:#00a65a;'>UGX " . number_format($amount, 0) . "</span></div>";
        })->width(150);
        
        for ($level = 1; $level <= 10; $level++) {
            $percentage = [3, 2.5, 2, 1.5, 1, 0.8, 0.6, 0.4, 0.3, 0.2][$level - 1];
            $grid->column("commission_parent_{$level}", __("Parent {$level} ({$percentage}%)"))->display(function ($amount) use ($level) {
                if (!$amount || $this->commission_is_processed !== 'Yes') {
                    return '<span class="text-muted">-</span>';
                }
                $userId = $this->{"parent_{$level}_user_id"};
                if (!$userId) {
                    return '<span class="text-muted">No Parent</span>';
                }
                $user = \App\Models\User::find($userId);
                $name = $user ? $user->name : 'User #' . $userId;
                return "<div style='white-space:nowrap;'><strong>{$name}</strong><br><span style='color:#00a65a;'>UGX " . number_format($amount, 0) . "</span></div>";
            })->width(150);
        }
        
        $grid->column('color', __('Color'))->label('primary')->width(100);
        $grid->column('size', __('Size'))->label('info')->width(100);

        // Row actions
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        // Batch actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(OrderedItem::findOrFail($id));

        $show->panel()
            ->title('Product Sale Details')
            ->style('primary');

        $show->divider('Sale Information');

        $show->field('id', __('Sale ID'));
        $show->field('created_at', __('Sale Date'))->as(function ($date) {
            return date('l, F j, Y - H:i:s', strtotime($date));
        });
        
        $show->divider('Order Reference');
        
        // Display order ID - use getOriginal to avoid relationship conflict
        $show->field('order', __('Order'))->as(function () {
            $orderId = $this->getOriginal('order');
            if (!$orderId) {
                return '<span class="label label-default">Standalone Sale (No Order Attached)</span>';
            }
            $order = \App\Models\Order::find($orderId);
            if ($order) {
                $receipt = $order->receipt_number ?? "Order #{$orderId}";
                $customer = $order->customer_name ?? 'N/A';
                $status = [
                    0 => '<span class="label label-warning">Pending</span>',
                    1 => '<span class="label label-info">Processing</span>',
                    2 => '<span class="label label-success">Completed</span>',
                    3 => '<span class="label label-danger">Cancelled</span>',
                    4 => '<span class="label label-danger">Failed</span>',
                    5 => '<span class="label label-warning">Refunded</span>',
                ][$order->status] ?? '<span class="label label-default">Unknown</span>';
                
                return "
                    <strong>Receipt:</strong> {$receipt}<br>
                    <strong>Customer:</strong> {$customer}<br>
                    <strong>Status:</strong> {$status}
                ";
            }
            return "Order #{$orderId} (Not Found)";
        })->unescape();
        
        $show->divider('Product Details');
        
        // Display product with image - use getOriginal to avoid relationship conflict
        $show->field('product', __('Product'))->as(function () {
            $productId = $this->getOriginal('product');
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $image = $product->feature_photo 
                    ? "<img src='{$product->feature_photo}' style='width:200px;height:200px;object-fit:cover;border-radius:8px;margin-bottom:10px;'><br>" 
                    : "";
                $category = $product->category ? \App\Models\ProductCategory::find($product->category)?->name ?? 'N/A' : 'N/A';
                
                return "
                    {$image}
                    <strong>Name:</strong> {$product->name}<br>
                    <strong>Category:</strong> {$category}<br>
                    <strong>Product ID:</strong> {$product->local_id}<br>
                    <strong>Current Stock:</strong> " . ($product->quantity ?? 'N/A') . "
                ";
            }
            return "Product #{$productId} (Not Found)";
        })->unescape();
        
        $show->divider('Sale Breakdown');
        
        $show->field('qty', __('Quantity Sold'))->as(function ($qty) {
            return '<strong style="font-size:18px;">' . number_format($qty, 0) . ' units</strong>';
        })->unescape();
        
        $show->field('unit_price', __('Unit Price'))->as(function ($price) {
            return '<strong style="color:#00a65a;font-size:18px;">UGX ' . number_format($price, 0) . '</strong>';
        })->unescape();
        
        $show->field('subtotal', __('Subtotal (Total Amount)'))->as(function ($subtotal) {
            return '<strong style="color:#00a65a;font-size:24px;">UGX ' . number_format($subtotal, 0) . '</strong>';
        })->unescape();
        
        $show->divider('💰 Commission Distribution (10-Level MLM)');
        
        $show->field('commission_status', __('Commission Status'))->as(function () {
            if ($this->commission_is_processed === 'Yes') {
                $date = $this->commission_processed_date ? date('Y-m-d H:i', strtotime($this->commission_processed_date)) : 'N/A';
                $total = number_format($this->total_commission_amount ?? 0, 0);
                return "<span class='label label-success'>✓ Processed</span> on {$date}<br><strong>Total Commission: UGX {$total}</strong>";
            } elseif ($this->item_is_paid === 'Yes' && $this->has_detehm_seller === 'Yes') {
                return "<span class='label label-warning'>⏳ Pending Processing</span>";
            } else {
                return "<span class='label label-default'>Not Applicable</span>";
            }
        })->unescape();
        
        // Show commission breakdown if processed
        $show->field('commission_breakdown', __('Commission Breakdown'))->as(function () {
            if ($this->commission_is_processed !== 'Yes') {
                return '<p class="text-muted">Commission not yet processed or not applicable.</p>';
            }
            
            $html = '<table class="table table-bordered table-striped" style="margin-top:10px;">';
            $html .= '<thead><tr><th style="width:15%;">Level</th><th style="width:35%;">Beneficiary</th><th style="width:15%;">Rate</th><th style="width:35%;">Amount Earned</th></tr></thead>';
            $html .= '<tbody>';
            
            // Seller
            if ($this->commission_seller) {
                $seller = \App\Models\User::find($this->dtehm_user_id);
                $sellerName = $seller ? $seller->name : 'User #' . $this->dtehm_user_id;
                $html .= "<tr>";
                $html .= "<td><strong>Seller</strong></td>";
                $html .= "<td>{$sellerName}</td>";
                $html .= "<td>10%</td>";
                $html .= "<td><strong style='color:#00a65a;'>UGX " . number_format($this->commission_seller, 0) . "</strong></td>";
                $html .= "</tr>";
            }
            
            // Parents 1-10
            $percentages = [3, 2.5, 2, 1.5, 1, 0.8, 0.6, 0.4, 0.3, 0.2];
            for ($level = 1; $level <= 10; $level++) {
                $commissionField = "commission_parent_{$level}";
                $userIdField = "parent_{$level}_user_id";
                
                if ($this->$commissionField) {
                    $userId = $this->$userIdField;
                    $user = $userId ? \App\Models\User::find($userId) : null;
                    $userName = $user ? $user->name : ($userId ? "User #{$userId}" : 'Unknown');
                    
                    $html .= "<tr>";
                    $html .= "<td><strong>Parent {$level}</strong></td>";
                    $html .= "<td>{$userName}</td>";
                    $html .= "<td>{$percentages[$level - 1]}%</td>";
                    $html .= "<td><strong style='color:#00a65a;'>UGX " . number_format($this->$commissionField, 0) . "</strong></td>";
                    $html .= "</tr>";
                }
            }
            
            $html .= '</tbody></table>';
            return $html;
        })->unescape();
        
        $show->divider('Additional Information');
        
        $show->field('color', __('Color'))->as(function ($color) {
            return $color ?: '<span class="text-muted">Not specified</span>';
        })->unescape();
        
        $show->field('size', __('Size'))->as(function ($size) {
            return $size ?: '<span class="text-muted">Not specified</span>';
        })->unescape();
        
        $show->field('amount', __('Amount (Legacy)'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('updated_at', __('Last Updated'))->as(function ($date) {
            return date('Y-m-d H:i:s', strtotime($date));
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderedItem());

        $form->divider('Product Sale Information');

        // Product Selection - regular dropdown
        $form->select('product', __('Product'))
            ->options(\App\Models\Product::orderBy('name', 'asc')->get()->mapWithKeys(function ($product) {
                $price = number_format($product->price_1, 0);
                return [$product->id => "{$product->name} (UGX {$price})"];
            }))
            ->rules('required')
            ->help('Select the product to sell');

        // Quantity
        $form->decimal('qty', __('Quantity'))

            ->rules('required|min:1')
            ->help('Number of units to sell');

        // Unit Price - auto-filled from product but editable
        $form->decimal('unit_price', __('Unit Price (UGX)'))
            ->default(0)
            ->rules('required|numeric|min:0')
            ->help('Price per unit (auto-filled from product, but can be edited for discounts)');

        // Subtotal - calculated field (read-only display)
        $form->display('subtotal', __('Subtotal'))
            ->with(function ($value) {
                if ($value) {
                    return 'UGX ' . number_format($value, 0);
                }
                return 'Will be calculated automatically';
            });

        $form->divider('Additional Options');

        // Color (optional)
        $form->text('color', __('Color'))
            ->help('Specify color if applicable (e.g., Red, Blue, Black)');

        // Size (optional)
        $form->text('size', __('Size'))
            ->help('Specify size if applicable (e.g., S, M, L, XL or measurements)');

        $form->divider('Order Reference (Optional)');

        // Order - optional, can be null for standalone sales
        // Use number input to avoid relationship conflict with order() method
        $form->number('order', __('Order ID (Optional)'))
            ->min(0)
            ->help('Optional: Enter Order ID to link this sale to an existing order. Leave empty for standalone sale.');
        
        // Display order info if order exists (read-only helper)
        $form->html(function ($model) {
            if ($model->id && !empty($model->getOriginal('order'))) {
                $orderId = $model->getOriginal('order');
                $order = \App\Models\Order::find($orderId);
                if ($order) {
                    $receipt = $order->receipt_number ?? "Order #{$orderId}";
                    $customer = $order->customer_name ?? 'N/A';
                    $status = [
                        0 => 'Pending',
                        1 => 'Processing',
                        2 => 'Completed',
                        3 => 'Cancelled',
                        4 => 'Failed',
                        5 => 'Refunded',
                    ][$order->status] ?? 'Unknown';
                    
                    return "
                        <div class='form-group'>
                            <label class='col-sm-2 control-label'></label>
                            <div class='col-sm-8'>
                                <div class='alert alert-info'>
                                    <strong>Linked Order Info:</strong><br>
                                    Receipt: {$receipt}<br>
                                    Customer: {$customer}<br>
                                    Status: {$status}
                                </div>
                            </div>
                        </div>
                    ";
                }
            }
            return '';
        });

        // JavaScript to auto-calculate subtotal and fetch product price
        $form->html('
            <script>
            $(function() {
                // Function to update subtotal
                function updateSubtotal() {
                    var qty = parseFloat($(\'input[name="qty"]\').val()) || 1;
                    var unitPrice = parseFloat($(\'input[name="unit_price"]\').val()) || 0;
                    var subtotal = qty * unitPrice;
                    
                    // Display formatted subtotal
                    var subtotalDisplay = \'UGX \' + subtotal.toLocaleString(\'en-US\', {maximumFractionDigits: 0});
                    $(\'.subtotal .form-control-static\').html(subtotalDisplay);
                }
                
                // When product is selected, fetch and set unit price
                $(\'select[name="product"]\').on(\'change\', function() {
                    var productId = $(this).val();
                    if (productId) {
                        $.ajax({
                            url: \'/api/ajax/product-details/\' + productId,
                            type: \'GET\',
                            success: function(data) {
                                if (data.price_1) {
                                    $(\'input[name="unit_price"]\').val(data.price_1);
                                    updateSubtotal();
                                }
                            }
                        });
                    }
                });
                
                // Update subtotal when qty or unit_price changes
                $(\'input[name="qty"], input[name="unit_price"]\').on(\'input change\', function() {
                    updateSubtotal();
                });
                
                // Initial calculation on page load
                updateSubtotal();
            });
            </script>
        ');

        // Saving hook - ensure prices are calculated
        $form->saving(function (Form $form) {
            // Get product details if product is set
            if ($form->product) {
                $product = \App\Models\Product::find($form->product);
                
                if (!$product) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Selected product not found.',
                    ]);
                }
                
                // If unit_price is empty or zero, use product price
                if (empty($form->unit_price) || floatval($form->unit_price) == 0) {
                    $form->unit_price = $product->price_1;
                }
            }
            
            // Ensure quantity is at least 1
            if (empty($form->qty) || floatval($form->qty) <= 0) {
                $form->qty = 1;
            }
            
            // Calculate subtotal
            $form->unit_price = floatval($form->unit_price);
            $form->qty = floatval($form->qty);
            $form->subtotal = $form->unit_price * $form->qty;
            
            // Set amount for backward compatibility
            $form->amount = $form->unit_price;
        });

        // Saved hook - log the sale
        $form->saved(function (Form $form) {
            $item = $form->model();
            \Illuminate\Support\Facades\Log::info("Single Product Sale Created: Item #{$item->id}, Product: {$item->product}, Qty: {$item->qty}, Unit Price: {$item->unit_price}, Subtotal: {$item->subtotal}");
            
            // Show success message
            admin_success('Success', 'Product sale recorded successfully!');
        });

        // Disable delete and view buttons when editing
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
