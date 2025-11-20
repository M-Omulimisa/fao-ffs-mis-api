<?php

namespace App\Admin\Controllers;

use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Orders';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order());

        // Eager load relationships to prevent N+1 queries
        $grid->model()
            ->with(['orderedItems:id,order,product,qty,amount', 'customer:id,name,email'])
            ->select([
                'id',
                'receipt_number',
                'invoice_number',
                'order_date',
                'created_at',
                'user',
                'order_state',
                'customer_name',
                'customer_phone_number_1',
                'mail',
                'order_total',
                'sub_total',
                'delivery_amount',
                'payment_confirmation',
                'payment_status',
                'payment_gateway',
                'delivery_district',
            ])
            ->orderBy('created_at', 'desc');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('customer_name', __('Customer Name'));
            $filter->like('customer_phone_number_1', __('Customer Phone'));
            $filter->like('receipt_number', __('Receipt Number'));
            $filter->like('invoice_number', __('Invoice Number'));
            $filter->like('mail', __('Email'));

            $filter->equal('order_state', __('Order Status'))
                ->select([
                    0 => 'Pending',
                    1 => 'Processing',
                    2 => 'Completed',
                    3 => 'Cancelled',
                    4 => 'Failed',
                    5 => 'Refunded'
                ]);

            $filter->equal('payment_status', __('Payment Status'))
                ->select([
                    'PAID' => 'Paid',
                    'PENDING_PAYMENT' => 'Pending Payment',
                    'PAY_ON_DELIVERY' => 'Pay on Delivery',
                    'FAILED' => 'Failed'
                ]);

            $filter->equal('payment_gateway', __('Payment Gateway'))
                ->select([
                    'pesapal' => 'PesaPal',
                    'cash_on_delivery' => 'Cash on Delivery',
                    'manual' => 'Manual'
                ]);

            // Optimized user query
            $filter->equal('user', __('Customer'))
                ->select(DB::table('users')
                    ->orderBy('name')
                    ->pluck('name', 'id'));

            $filter->between('order_date', __('Order Date'))->date();
            $filter->between('created_at', __('Created Date'))->datetime();
            $filter->between('order_total', __('Order Total (UGX)'))->integer();
        });

        // Export functionality
        $grid->exporter(function ($export) {
            $export->filename('Orders_' . date('Y-m-d_His'));
            $export->column('id', 'ID');
            $export->column('receipt_number', 'Receipt #');
            $export->column('invoice_number', 'Invoice #');
            $export->column('order_date', 'Order Date');
            $export->column('customer_name', 'Customer');
            $export->column('customer_phone_number_1', 'Phone');
            $export->column('mail', 'Email');
            $export->column('order_total', 'Total');
            $export->column('payment_status', 'Payment Status');
            $export->column('order_state', 'Order Status');
            $export->column('payment_gateway', 'Payment Gateway');
            
            // Use original numeric values for export
            $export->originalValue(['order_total', 'sub_total', 'delivery_amount']);
        });

        $grid->quickSearch('customer_name', 'customer_phone_number_1', 'mail', 'receipt_number')
            ->placeholder('Search by customer, phone, email, or receipt');

        // Batch actions
        $grid->batchActions(function ($batch) {
            $batch->disableDelete(); // Prevent accidental batch deletion of orders
        });

        // Pagination
        $grid->perPages([10, 20, 30, 50, 100]);
        $grid->paginate(20);

        // Actions
        $grid->actions(function ($actions) {
            // Disable create from row action
            $actions->disableEdit(false);
            $actions->disableView(false);
            $actions->disableDelete(true); // Orders should not be deleted, only cancelled

            // Add custom enhanced view action
            $actions->append('<a href="' . admin_url('orders/' . $actions->getKey() . '/detail') . '" class="btn btn-sm btn-success" title="View Enhanced Order Details"><i class="fa fa-star"></i></a>');
        });

        // Columns
        $grid->column('id', __('ID'))->sortable();

        $grid->column('receipt_number', __('Receipt #'))
            ->display(function ($receipt_number) {
                return $receipt_number ? '<strong>' . $receipt_number . '</strong>' : '<span class="text-muted">N/A</span>';
            })
            ->sortable();

        $grid->column('order_date', __('Order Date'))
            ->display(function ($order_date) {
                $model = $this;
                return $order_date ? date('d M Y', strtotime($order_date)) : date('d M Y', strtotime($model->created_at));
            })
            ->sortable();

        $grid->column('customer_name', __('Customer'))
            ->display(function ($customer_name) {
                $model = $this;
                $phone = $model->customer_phone_number_1 ? '<br><small>' . $model->customer_phone_number_1 . '</small>' : '';
                return $customer_name . $phone;
            })
            ->sortable();

        $grid->column('items_count', __('Items'))
            ->display(function () {
                $model = $this;
                $count = $model->orderedItems ? $model->orderedItems->count() : 0;
                return $count . ' item' . ($count != 1 ? 's' : '');
            });

        $grid->column('order_total', __('Total'))
            ->display(function ($order_total) {
                return '<strong>UGX ' . number_format((float)$order_total, 0) . '</strong>';
            })
            ->sortable();

        $grid->column('payment_status', __('Payment'))
            ->display(function ($payment_status) {
                $status = $payment_status ?? 'PENDING_PAYMENT';
                $colors = [
                    'PAID' => 'success',
                    'PENDING_PAYMENT' => 'warning',
                    'PAY_ON_DELIVERY' => 'info',
                    'FAILED' => 'danger'
                ];
                $color = $colors[$status] ?? 'default';
                $label = str_replace('_', ' ', $status);
                return '<span class="label label-' . $color . '">' . $label . '</span>';
            })
            ->sortable();

        $grid->column('order_state', __('Status'))
            ->editable('select', [
                0 => 'Pending',
                1 => 'Processing',
                2 => 'Completed',
                3 => 'Cancelled',
                4 => 'Failed',
                5 => 'Refunded'
            ])
            ->display(function ($order_state) {
                $state = intval($order_state ?? 0);
                $statuses = [
                    0 => ['text' => 'Pending', 'color' => 'warning'],
                    1 => ['text' => 'Processing', 'color' => 'info'],
                    2 => ['text' => 'Completed', 'color' => 'success'],
                    3 => ['text' => 'Cancelled', 'color' => 'danger'],
                    4 => ['text' => 'Failed', 'color' => 'danger'],
                    5 => ['text' => 'Refunded', 'color' => 'secondary']
                ];
                $status = $statuses[$state] ?? ['text' => 'Unknown', 'color' => 'default'];
                return '<span class="label label-' . $status['color'] . '">' . $status['text'] . '</span>';
            })
            ->help('Change order status')
            ->sortable();

        $grid->column('payment_gateway', __('Gateway'))
            ->display(function ($payment_gateway) {
                $gateways = [
                    'pesapal' => '<span class="label label-primary">PesaPal</span>',
                    'cash_on_delivery' => '<span class="label label-info">Cash on Delivery</span>',
                    'manual' => '<span class="label label-default">Manual</span>'
                ];
                return $gateways[$payment_gateway] ?? '<span class="text-muted">-</span>';
            })
            ->hide();

        $grid->column('delivery_district', __('Delivery'))
            ->display(function ($delivery_district) {
                return $delivery_district ?: '<span class="text-muted">-</span>';
            })
            ->hide();

        $grid->column('created_at', __('Created'))
            ->display(function ($created_at) {
                return date('d M Y, h:i A', strtotime($created_at));
            })
            ->sortable()
            ->hide();

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
        $show = new Show(Order::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('receipt_number', __('Receipt Number'));
        $show->field('invoice_number', __('Invoice Number'));
        $show->field('order_date', __('Order Date'))->as(function ($order_date) {
            return $order_date ? date('d M Y', strtotime($order_date)) : 'N/A';
        });

        $show->divider();
        $show->field('customer_name', __('Customer Name'));
        $show->field('customer_phone_number_1', __('Customer Phone'));
        $show->field('customer_address', __('Customer Address'));
        $show->field('mail', __('Email'));

        $show->divider();
        $show->field('sub_total', __('Subtotal'))->as(function ($sub_total) {
            return 'UGX ' . number_format((float)$sub_total, 2);
        });
        $show->field('delivery_amount', __('Delivery Fee'))->as(function ($delivery_amount) {
            return 'UGX ' . number_format((float)$delivery_amount, 2);
        });
        $show->field('tax', __('Tax'))->as(function ($tax) {
            return 'UGX ' . number_format((float)$tax, 2);
        });
        $show->field('discount', __('Discount'))->as(function ($discount) {
            return 'UGX ' . number_format((float)$discount, 2);
        });
        $show->field('order_total', __('Order Total'))->as(function ($order_total) {
            return 'UGX ' . number_format((float)$order_total, 2);
        });
        $show->field('payment_status', __('Payment Status'));
        $show->field('payment_gateway', __('Payment Gateway'));

        $show->divider();
        $show->field('order_state', __('Order Status'))->using([
            0 => 'Pending',
            1 => 'Processing',
            2 => 'Completed',
            3 => 'Cancelled',
            4 => 'Failed',
            5 => 'Refunded'
        ]);
        $show->field('delivery_method', __('Delivery Method'));
        $show->field('delivery_district', __('Delivery Location'));
        $show->field('notes', __('Notes'));

        $show->divider();
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        // Show order items in a table
        $show->orderedItems('Order Items', function ($items) {
            $items->disableCreateButton();
            $items->disableFilter();
            $items->disableExport();
            $items->disableActions();

            $items->column('product', __('Product'))->display(function ($product) {
                $pro = Product::find($product);
                return $pro ? $pro->name : 'Product Not Found';
            });
            $items->column('qty', __('Quantity'));
            $items->column('unit_price', __('Unit Price'))->display(function ($unit_price) {
                return 'UGX ' . number_format((float)$unit_price, 2);
            });
            $items->column('subtotal', __('Subtotal'))->display(function ($subtotal) {
                return 'UGX ' . number_format((float)$subtotal, 2);
            });
            $items->column('color', __('Color'))->display(function ($color) {
                return $color ?: '-';
            });
            $items->column('size', __('Size'))->display(function ($size) {
                return $size ?: '-';
            });
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
        $form = new Form(new Order());

        // Eager load relationships for edit mode
        if ($id = request()->route()->parameter('order')) {
            $model = Order::with('orderedItems')->find($id);
            $form->model($model);
        }

        // Order Date
        $form->date('order_date', __('Order Date'))
            ->default(date('Y-m-d'))
            ->rules('required');

        $form->divider('Customer Information');

        // Customer Selection or Manual Entry
        $form->select('user', __('Registered Customer'))
            ->options(User::orderBy('name')->pluck('name', 'id'))
            ->help('Select if customer is registered, or fill manual details below');

        $form->text('customer_name', __('Customer Name'))
            ->rules('required|max:255')
            ->help('Required - Enter customer name if not selecting registered customer');

        $form->text('customer_phone_number_1', __('Customer Phone'))
            ->rules('required|max:255');

        $form->text('customer_phone_number_2', __('Alternate Phone'));

        $form->email('mail', __('Customer Email'));

        $form->textarea('customer_address', __('Customer Address'))
            ->rows(2)
            ->rules('nullable');

        $form->divider('Delivery Information');

        $form->select('delivery_method', __('Delivery Method'))
            ->options([
                'delivery' => 'Home Delivery',
                'pickup' => 'Pickup from Store',
            ])
            ->default('delivery')
            ->rules('required');

        $form->select('delivery_address_id', __('Delivery Location'))
            ->options(DeliveryAddress::orderBy('address')->pluck('address', 'id'))
            ->rules('nullable')
            ->help('Select pre-defined delivery location');

        $form->text('delivery_district', __('Delivery District/Area'))
            ->rules('nullable|max:255');

        $form->textarea('delivery_address_text', __('Delivery Address Details'))
            ->rows(2)
            ->rules('nullable')
            ->help('Specific delivery address or instructions');

        $form->decimal('delivery_amount', __('Delivery Fee (UGX)'))
            ->default(0)
            ->rules('required|numeric|min:0');

        $form->divider('Order Items');

        // Check if we're editing an existing record
        $isEditing = request()->route()->parameter('order');

        if ($isEditing) {
            // In EDIT mode: Show existing items as read-only table
            $order = Order::with('orderedItems.pro')->find($isEditing);

            $form->html(function () use ($order) {
                if (!$order || !$order->id) {
                    return '<div class="alert alert-info">No items in this order yet.</div>';
                }

                if ($order->orderedItems && $order->orderedItems->count() > 0) {
                    $html = '<div class="box box-solid box-info">';
                    $html .= '<div class="box-header with-border"><h3 class="box-title">Current Order Items (Cannot be modified)</h3></div>';
                    $html .= '<div class="box-body table-responsive no-padding">';
                    $html .= '<table class="table table-striped table-hover">';
                    $html .= '<thead><tr>';
                    $html .= '<th>Product</th>';
                    $html .= '<th class="text-right">Quantity</th>';
                    $html .= '<th class="text-right">Unit Price</th>';
                    $html .= '<th class="text-right">Subtotal</th>';
                    $html .= '<th>Variants</th>';
                    $html .= '</tr></thead><tbody>';

                    foreach ($order->orderedItems as $item) {
                        $productName = $item->pro ? $item->pro->name : 'Product Not Found (ID: ' . $item->product . ')';
                        $unitPrice = $item->unit_price ?? $item->amount ?? 0;
                        $subtotal = $item->subtotal ?? ($unitPrice * $item->qty);
                        $variants = [];
                        if ($item->color) $variants[] = 'Color: ' . $item->color;
                        if ($item->size) $variants[] = 'Size: ' . $item->size;
                        $variantsText = !empty($variants) ? implode(', ', $variants) : '-';

                        $html .= '<tr>';
                        $html .= '<td>' . htmlspecialchars($productName) . '</td>';
                        $html .= '<td class="text-right">' . number_format($item->qty, 2) . '</td>';
                        $html .= '<td class="text-right">UGX ' . number_format($unitPrice, 0) . '</td>';
                        $html .= '<td class="text-right"><strong>UGX ' . number_format($subtotal, 0) . '</strong></td>';
                        $html .= '<td>' . htmlspecialchars($variantsText) . '</td>';
                        $html .= '</tr>';
                    }

                    $html .= '</tbody></table></div></div>';
                    $html .= '<div class="alert alert-warning"><i class="fa fa-warning"></i> <strong>Note:</strong> Existing items cannot be modified. To change items, please cancel this order and create a new one.</div>';
                    return $html;
                }
                return '<div class="alert alert-info">No items found in this order.</div>';
            }, 'Existing Items');
        } else {
            // In CREATE mode: Allow adding items normally
            $form->hasMany('orderedItems', 'Items', function (Form\NestedForm $form) {
                // Get products with categories for better organization
                $products = DB::table('products as p')
                    ->leftJoin('product_categories as pc', 'p.category', '=', 'pc.id')
                    ->select(
                        'p.id',
                        'p.name',
                        'p.local_id',
                        'p.price_1',
                        'p.has_colors',
                        'p.colors',
                        'p.has_sizes',
                        'p.sizes',
                        'pc.category as category_name'
                    )
                    ->whereNotNull('p.price_1')
                    ->orderBy('pc.category', 'asc')
                    ->orderBy('p.name', 'asc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $category = $item->category_name ? '[' . $item->category_name . '] ' : '';
                        $sku = $item->local_id ? ' (' . $item->local_id . ')' : '';
                        $price = ' | Price: UGX ' . number_format($item->price_1, 0);
                        return [$item->id => $category . $item->name . $sku . $price];
                    });

                $form->select('product', __('Product'))
                    ->options($products)
                    ->rules('required')
                    ->creationRules('required')
                    ->help('Select a product');

                $form->decimal('qty', __('Quantity'))
                    ->default(1)
                    ->rules('required|numeric|min:0.01')
                    ->help('Enter quantity to order');

                $form->decimal('unit_price', __('Unit Price (UGX)'))
                    ->rules('nullable|numeric|min:0')
                    ->help('Leave empty to use product default price');

                $form->text('color', __('Color'))
                    ->rules('nullable|max:100')
                    ->help('Optional - Enter product color variant');

                $form->text('size', __('Size'))
                    ->rules('nullable|max:100')
                    ->help('Optional - Enter product size variant');
            });
        }

        $form->divider('Payment Information');

        $form->select('payment_gateway', __('Payment Gateway'))
            ->options([
                'pesapal' => 'PesaPal',
                'cash_on_delivery' => 'Cash on Delivery',
                'manual' => 'Manual Payment'
            ])
            ->default('manual')
            ->rules('required');

        $form->select('payment_status', __('Payment Status'))
            ->options([
                'PAID' => 'Paid',
                'PENDING_PAYMENT' => 'Pending Payment',
                'PAY_ON_DELIVERY' => 'Pay on Delivery',
                'FAILED' => 'Failed'
            ])
            ->default('PENDING_PAYMENT')
            ->rules('required')
            ->help('Set to PAID if payment received');

        $form->text('payment_confirmation', __('Payment Confirmation/Reference'))
            ->rules('nullable|max:255')
            ->help('Payment reference number or confirmation code');

        $form->divider('Order Pricing');

        $form->decimal('tax', __('Tax (UGX)'))
            ->default(0)
            ->rules('nullable|numeric|min:0');

        $form->decimal('discount', __('Discount (UGX)'))
            ->default(0)
            ->rules('nullable|numeric|min:0');

        $form->select('order_state', __('Order Status'))
            ->options([
                0 => 'Pending',
                1 => 'Processing',
                2 => 'Completed',
                3 => 'Cancelled',
                4 => 'Failed',
                5 => 'Refunded'
            ])
            ->default(0)
            ->rules('required');

        $form->textarea('notes', __('Admin Notes'))
            ->rows(3)
            ->rules('nullable')
            ->help('Internal notes about this order');

        // Pre-validation before saving
        $form->saving(function (Form $form) {
            // Check if this is an edit (model has id)
            $isEdit = $form->model()->id ? true : false;

            if ($isEdit) {
                // In EDIT mode: Items cannot be changed, skip item validation
                // Only allow updating customer info, payment details, status, notes

                // Recalculate totals if delivery amount, tax, or discount changed
                if (isset($form->delivery_amount) || isset($form->tax) || isset($form->discount)) {
                    $order = Order::find($form->model()->id);
                    $subTotal = $order->sub_total ?? 0;
                    $deliveryAmount = floatval($form->delivery_amount ?? $order->delivery_amount ?? 0);
                    $tax = floatval($form->tax ?? $order->tax ?? 0);
                    $discount = floatval($form->discount ?? $order->discount ?? 0);

                    $form->order_total = $subTotal + $deliveryAmount + $tax - $discount;
                }

                return;
            }

            // In CREATE mode: Validate items
            if (empty($form->orderedItems) || count($form->orderedItems) == 0) {
                throw new \Exception('Please add at least one item to the order.');
            }

            // Collect all product IDs for batch query
            $productIds = array_filter(array_column($form->orderedItems, 'product'));

            if (empty($productIds)) {
                throw new \Exception('Please select at least one valid product.');
            }

            // Fetch all products in a single optimized query for validation
            $products = DB::table('products')
                ->select('id', 'name', 'local_id', 'price_1')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            // Pre-validate items and ensure unit prices are set
            $errors = [];
            
            // Get items as array, modify them, then set them back (Laravel-Admin requirement)
            $items = $form->orderedItems;

            foreach ($items as $index => $item) {
                if (empty($item['product']) || empty($item['qty'])) {
                    continue;
                }

                $product = $products->get($item['product']);

                if (!$product) {
                    $errors[] = "Item #" . ($index + 1) . ": Invalid product selected.";
                    continue;
                }

                $quantity = floatval($item['qty']);
                if ($quantity <= 0) {
                    $errors[] = "{$product->name}: Quantity must be greater than zero.";
                }
                
                // Ensure unit_price is set from product if not provided or is zero
                if (empty($item['unit_price']) || floatval($item['unit_price']) == 0) {
                    $items[$index]['unit_price'] = $product->price_1;
                    Log::info("Pre-filling unit_price for {$product->name}: {$product->price_1}");
                }
                
                // Calculate and set subtotal
                $items[$index]['subtotal'] = floatval($items[$index]['unit_price']) * $quantity;
                $items[$index]['amount'] = $items[$index]['unit_price']; // Backward compatibility
            }
            
            // Set modified items back to form
            $form->orderedItems = $items;

            // Throw all validation errors at once
            if (!empty($errors)) {
                throw new \Exception("Order Validation Failed:\n" . implode("\n", $errors));
            }
        });

        // Post-processing: Compute everything after save
        $form->saved(function (Form $form) {
            $order = $form->model();

            // Only process on CREATE, not on EDIT
            $isEdit = $order->wasRecentlyCreated === false && $order->exists;

            if ($isEdit) {
                // In edit mode, items are not changed so just recalculate if needed
                $order->refresh();
                admin_success('Order Updated', 'Order updated successfully (items unchanged).');
                return;
            }

            // Process NEW order: Generate receipt number, calculate totals, process items
            try {
                DB::beginTransaction();

                // Generate receipt number if not set
                if (empty($order->receipt_number)) {
                    $order->receipt_number = 'ORD-' . date('Ymd') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
                }

                // Generate invoice number if not set
                if (empty($order->invoice_number)) {
                    $order->invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
                }

                // Calculate subtotal from order items
                $subTotal = 0;
                $itemsProcessed = 0;

                foreach ($order->orderedItems as $item) {
                    $product = Product::find($item->product);
                    if (!$product) {
                        Log::warning("Product {$item->product} not found for order item {$item->id}");
                        continue;
                    }

                    // Set unit price from product if not set or is zero
                    if (empty($item->unit_price) || $item->unit_price == 0 || $item->unit_price === null) {
                        $item->unit_price = floatval($product->price_1);
                    } else {
                        $item->unit_price = floatval($item->unit_price);
                    }

                    // Ensure quantity is valid
                    $quantity = floatval($item->qty ?? 1);
                    
                    // Calculate subtotal for this item
                    $item->subtotal = $item->unit_price * $quantity;
                    $item->amount = $item->unit_price; // Keep for backward compatibility
                    
                    // Save with explicit values
                    $item->save();

                    $subTotal += $item->subtotal;
                    $itemsProcessed++;
                    
                    Log::info("Order item {$item->id}: Product {$product->name}, Qty: {$quantity}, Unit Price: {$item->unit_price}, Subtotal: {$item->subtotal}");
                }

                // Calculate order totals
                $order->sub_total = $subTotal;
                $deliveryAmount = floatval($order->delivery_amount ?? 0);
                $tax = floatval($order->tax ?? 0);
                $discount = floatval($order->discount ?? 0);

                $order->order_total = $subTotal + $deliveryAmount + $tax - $discount;
                $order->amount = $order->order_total; // Keep for backward compatibility
                $order->payable_amount = $order->order_total;

                $order->save();

                DB::commit();

                // Show success message with details
                admin_success(
                    'Order Created Successfully',
                    "Receipt: {$order->receipt_number}<br>" .
                    "Customer: {$order->customer_name}<br>" .
                    "Items: {$itemsProcessed}<br>" .
                    "Subtotal: UGX " . number_format($order->sub_total, 0) . "<br>" .
                    "Delivery: UGX " . number_format($deliveryAmount, 0) . "<br>" .
                    "Total: UGX " . number_format($order->order_total, 0)
                );

                // Send email notification in background
                register_shutdown_function(function () use ($order) {
                    try {
                        Order::send_mails($order);
                    } catch (\Throwable $th) {
                        Log::error('Background email error for order ' . $order->id . ': ' . $th->getMessage());
                    }
                });

            } catch (\Throwable $th) {
                DB::rollBack();
                Log::error('Order processing error: ' . $th->getMessage());
                admin_error('Order Processing Error', $th->getMessage());
                throw $th;
            }
        });

        // Disable editing and viewing after save
        $form->tools(function (Form\Tools $tools) {
            // Keep all tools
        });

        return $form;
    }
}
