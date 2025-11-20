<?php

namespace App\Admin\Controllers;

use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
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
        $grid->model()->orderBy('id', 'desc');
        
        // Enable pagination and set per page options
        $grid->perPages([10, 20, 30, 50, 100]);
        $grid->paginate(20);
        
        // Add quick search functionality
        $grid->quickSearch('customer_name', 'customer_phone_number_1', 'mail', 'id')
             ->placeholder('Search by name, phone, email, or ID');

        $grid->column('id', __('ID'))->sortable();

        $grid->column('created_at', __('Created'))
            ->display(function ($created_at) {
                if (!$created_at) return 'N/A';
                try {
                    return Utils::my_date_time($created_at);
                } catch (\Exception $e) {
                    return date('M d, Y H:i', strtotime($created_at));
                }
            })->sortable();

        $grid->column('user', __('User'))
            ->display(function ($user) {
                if (!$user) return 'Guest';
                try {
                    $u = User::find($user);
                    return $u ? $u->name : "Unknown User";
                } catch (\Exception $e) {
                    return 'Error loading user';
                }
            })->sortable()->hide();

        $grid->column('order_state', __('Order State'))
            ->sortable()
            ->display(function ($order_state) {
                // Ensure we have a valid state value
                $state = intval($order_state ?? 0);
                
                $statuses = [
                    0 => ['text' => 'Pending', 'color' => 'warning'],
                    1 => ['text' => 'Processing', 'color' => 'info'],
                    2 => ['text' => 'Completed', 'color' => 'success'],
                    3 => ['text' => 'Cancelled', 'color' => 'danger'],
                    4 => ['text' => 'Failed', 'color' => 'danger'],
                    5 => ['text' => 'Refunded', 'color' => 'secondary']
                ];
                
                $status = $statuses[$state] ?? ['text' => 'Unknown', 'color' => 'secondary'];
                return "<span class='badge bg-{$status['color']}'>{$status['text']}</span>";
            });

      
        $grid->column('order_total', __('Payable Total'))
            ->display(function ($order_total) {
                $amount = floatval($order_total ?? 0);
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable()
            ->editable();

        $grid->column('payment_confirmation', __('Payment'))
            ->display(function ($payment_confirmation) {
                if (empty($payment_confirmation) || $payment_confirmation === null) {
                    return "<span class='badge bg-warning'>Not Paid</span>";
                }
                return "<span class='badge bg-success'>Paid</span>";
            })->sortable();

        $grid->column('mail', __('Mail'))->sortable()->hide();

        $grid->column('delivery_district', __('Delivery'))
            ->display(function ($delivery_district_id) {
                if (!$delivery_district_id || $delivery_district_id === '') {
                    return '<span class="text-muted">No address</span>';
                }
                try {
                    $delivery_address = DeliveryAddress::find($delivery_district_id);
                    return $delivery_address ? $delivery_address->address : "Address not found";
                } catch (\Exception $e) {
                    return 'Error loading address';
                }
            })->sortable();

        $grid->column('description', __('Description'))->hide();
        $grid->column('customer_name', __('Customer'))->sortable();
        $grid->column('customer_phone_number_1', __('Customer Contact'))->sortable();
        $grid->column('customer_phone_number_2', __('Alternate Contact'))->sortable();
        $grid->column('customer_address', __('Customer Address'));

       

        // Remove custom action columns - Laravel Admin provides these automatically
        // If you need custom actions, they should be added through the actions() method
        
        // Configure actions
        $grid->actions(function ($actions) {
            $actions->disableEdit(false);
            $actions->disableView(false); // Keep default view available
            $actions->disableDelete(true); // Disable delete for orders for safety
            
            // Add custom enhanced view action
            $actions->append('<a href="' . admin_url('orders/' . $actions->getKey() . '/detail') . '" class="btn btn-sm btn-success" title="View Enhanced Order Details"><i class="fa fa-star"></i> Enhanced View</a>');
        });

        // Add filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter(); // Disable default id filter
            
            // Add order state filter
            $filter->equal('order_state', 'Order Status')->select([
                0 => 'Pending',
                1 => 'Processing', 
                2 => 'Completed',
                3 => 'Cancelled',
                4 => 'Failed',
                5 => 'Refunded'
            ]);
            
            // Add date range filter
            $filter->between('created_at', 'Order Date')->date();
            
            // Add payment status filter
            $filter->where(function ($query) {
                $query->where('payment_confirmation', '!=', '')->whereNotNull('payment_confirmation');
            }, 'Payment Status', 'paid_status')->select([
                1 => 'Paid',
                0 => 'Not Paid'
            ]);
            
            // Add total amount range filter
            $filter->between('order_total', 'Order Total (UGX)')->integer();
        });

        return $grid;
    }



    /**
     * Display enhanced order details view.
     *
     * @param mixed $id
     * @return \Illuminate\Contracts\View\View
     */
    public function detail($id)
    {
        $order = Order::findOrFail($id);
        
        // Get additional related data for comprehensive order details
        $orderItems = $order->get_items();
        $deliveryAddress = null;
        $customer = null;
        
        // Get delivery address if exists
        if ($order->delivery_address_id) {
            $deliveryAddress = DeliveryAddress::find($order->delivery_address_id);
        }
        
        // Get customer details if exists
        if ($order->user) {
            $customer = User::find($order->user);
        }
        
        // Calculate order statistics
        $totalItems = count($orderItems);
        $totalQuantity = array_sum(array_column($orderItems, 'qty'));
        
        // Order status mapping with colors and icons - comprehensive mapping
        $orderStatuses = [
            0 => ['text' => 'Pending', 'class' => 'warning', 'icon' => 'clock'],
            1 => ['text' => 'Processing', 'class' => 'info', 'icon' => 'refresh'],
            2 => ['text' => 'Completed', 'class' => 'success', 'icon' => 'check-circle'],
            3 => ['text' => 'Cancelled', 'class' => 'danger', 'icon' => 'x-circle'],
            4 => ['text' => 'Failed', 'class' => 'danger', 'icon' => 'alert-triangle'],
            5 => ['text' => 'Refunded', 'class' => 'secondary', 'icon' => 'arrow-left-circle'],
            // Add fallback for any other values
            'default' => ['text' => 'Unknown', 'class' => 'secondary', 'icon' => 'question-circle']
        ];
        
        // Ensure we have a valid order state, default to 0 (Pending) if invalid
        $currentOrderState = $order->order_state ?? 0;
        
        // Validate order state is numeric and within expected range
        if (!is_numeric($currentOrderState) || !array_key_exists(intval($currentOrderState), $orderStatuses)) {
            Log::warning("Order {$order->id} has invalid order_state: {$order->order_state}, defaulting to 0");
            $currentOrderState = 0;
            // Optionally update the order to fix the invalid state
            $order->order_state = 0;
        }
        
        // Payment gateway mapping
        $paymentGateways = [
            'stripe' => 'Stripe',
            'pesapal' => 'PesaPal',
            'cash' => 'Cash on Delivery',
            'bank_transfer' => 'Bank Transfer'
        ];
        
        // Display custom view for order details.
        return view('order', compact(
            'order', 
            'orderItems', 
            'deliveryAddress', 
            'customer', 
            'totalItems', 
            'totalQuantity', 
            'orderStatuses', 
            'paymentGateways',
            'currentOrderState'
        ));

        // If using the built-in Show, uncomment below:
        /*
        $show = new Show($order);
        $show->field('id', __('ID'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        $show->field('user', __('User'));
        $show->field('order_state', __('Order State'));
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount);
        });
        $show->field('payment_confirmation', __('Payment Confirmation'));
        $show->field('mail', __('Mail'));
        $show->field('delivery_district', __('Delivery District'));
        $show->field('description', __('Description'));
        $show->field('customer_name', __('Customer Name'));
        $show->field('customer_phone_number_1', __('Customer Phone Number 1'));
        $show->field('customer_phone_number_2', __('Customer Phone Number 2'));
        $show->field('customer_address', __('Customer Address'));
        $show->field('order_total', __('Order Total'))->as(function ($order_total) {
            return 'UGX ' . number_format($order_total);
        });
        $show->field('order_details', __('Order Details'));
        $show->field('stripe_id', __('Stripe ID'));
        $show->field('stripe_url', __('Stripe URL'));
        $show->field('stripe_paid', __('Stripe Paid'));
        $show->field('delivery_method', __('Delivery Method'));
        $show->field('delivery_address_id', __('Delivery Address ID'));
        $show->field('delivery_address_details', __('Delivery Address Details'));
        $show->field('delivery_amount', __('Delivery Amount'))->as(function ($delivery_amount) {
            return 'UGX ' . number_format($delivery_amount);
        });
        $show->field('payable_amount', __('Payable Amount'))->as(function ($payable_amount) {
            return 'UGX ' . number_format($payable_amount);
        });
        return $show;
        */
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Order());

        $form->display('id', __('ID'));

        $form->radio('order_state', __('Order State'))
            ->options([
                0 => 'Pending',
                1 => 'Processing',
                2 => 'Completed',
                3 => 'Canceled',
                4 => 'Failed',
            ])
            ->default(0)
            ->required();

        // Add custom saving hook to debug and ensure proper saving
        $form->saving(function (Form $form) {
            Log::info('Laravel Admin form saving order ' . $form->model()->id . ' with order_state: ' . request('order_state'));

            // Ensure order_state is properly set
            if (request()->has('order_state')) {
                $form->model()->order_state = (int) request('order_state');
                Log::info('Manually setting order_state to: ' . $form->model()->order_state);
            }
        });

        // Add saved hook to verify the save worked
        $form->saved(function (Form $form) {
            $order = $form->model();
            Log::info('Laravel Admin form saved order ' . $order->id . ' - final order_state: ' . $order->order_state);

            // Force refresh from database to make sure it was saved
            $order->refresh();
            Log::info('After refresh, order_state is: ' . $order->order_state);
        });
        $form->decimal('order_total', __('order_total')); 
        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
