<?php

namespace App\Admin\Controllers;

use App\Models\UniversalPayment;
use App\Models\User;
use App\Models\Project;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UniversalPaymentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Universal Payments';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UniversalPayment());

        // Order by latest first
        $grid->model()->orderBy('created_at', 'desc');

        // Disable actions (read-only)
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        // Quick search
        $grid->quickSearch('payment_reference', 'customer_name', 'customer_phone', 'customer_email')
            ->placeholder('Search by reference, name, phone, or email');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            // Payment Status
            $filter->equal('status', 'Payment Status')->select([
                'PENDING' => 'Pending',
                'PROCESSING' => 'Processing',
                'COMPLETED' => 'Completed',
                'FAILED' => 'Failed',
                'CANCELLED' => 'Cancelled',
                'REFUNDED' => 'Refunded',
            ]);

            // Payment Type
            $filter->equal('payment_type', 'Payment Type')->select([
                'INSURANCE_SUBSCRIPTION' => 'Insurance Subscription',
                'MEMBERSHIP' => 'Membership',
                'PROJECT_INVESTMENT' => 'Project Investment',
                'OTHER' => 'Other',
            ]);

            // Payment Category
            $filter->equal('payment_category', 'Category')->select([
                'insurance' => 'Insurance',
                'membership' => 'Membership',
                'investment' => 'Investment',
                'service' => 'Service',
                'other' => 'Other',
            ]);

            // Payment Gateway
            $filter->equal('payment_gateway', 'Payment Gateway')->select([
                'pesapal' => 'PesaPal',
                'cash' => 'Cash',
                'mobile_money' => 'Mobile Money',
                'bank_transfer' => 'Bank Transfer',
                'other' => 'Other',
            ]);

            // Payment Method
            $filter->equal('payment_method', 'Payment Method');

            // User
            $filter->equal('user_id', 'User')->select(User::pluck('name', 'id'));

            // Project (for investments)
            $filter->equal('project_id', 'Project')->select(Project::pluck('title', 'id'));

            // Items Processed
            $filter->equal('items_processed', 'Items Processed')->select([
                1 => 'Yes',
                0 => 'No',
            ]);

            // Amount Range
            $filter->between('amount', 'Amount (UGX)');

            // Date Ranges
            $filter->between('payment_date', 'Payment Date')->datetime();
            $filter->between('confirmed_at', 'Confirmed At')->datetime();
            $filter->between('created_at', 'Created At')->datetime();
        });

        // Columns
        $grid->column('id', __('ID'))->sortable();

        $grid->column('payment_reference', __('Reference'))
            ->copyable()
            ->sortable()
            ->width(180);

        $grid->column('status', __('Status'))
            ->label([
                'PENDING' => 'warning',
                'PROCESSING' => 'info',
                'COMPLETED' => 'success',
                'FAILED' => 'danger',
                'CANCELLED' => 'default',
                'REFUNDED' => 'primary',
            ])
            ->sortable()
            ->width(100);

        $grid->column('payment_type', __('Type'))
            ->display(function ($type) {
                $badges = [
                    'INSURANCE_SUBSCRIPTION' => '<span class="label label-info">Insurance</span>',
                    'MEMBERSHIP' => '<span class="label label-primary">Membership</span>',
                    'PROJECT_INVESTMENT' => '<span class="label label-success">Investment</span>',
                    'OTHER' => '<span class="label label-default">Other</span>',
                ];
                return $badges[$type] ?? '<span class="label label-default">' . $type . '</span>';
            })
            ->sortable()
            ->width(120);

        $grid->column('customer_name', __('Customer'))
            ->display(function () {
                $name = $this->customer_name ?? 'N/A';
                $phone = $this->customer_phone ?? '';
                return $name . ($phone ? '<br><small>' . $phone . '</small>' : '');
            })
            ->sortable();

        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                $color = $this->status === 'COMPLETED' ? 'green' : ($this->status === 'FAILED' ? 'red' : 'orange');
                return '<span style="color: ' . $color . '; font-weight: bold;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->totalRow(function ($amount) {
                return '<strong style="color: #007bff;">Total: UGX ' . number_format($amount, 0) . '</strong>';
            })
            ->width(120);

        $grid->column('items_count', __('Items'))
            ->display(function ($count) {
                $processed = $this->items_processed ? '✓' : '✗';
                $color = $this->items_processed ? 'green' : 'orange';
                return '<span style="color: ' . $color . ';">' . $count . ' ' . $processed . '</span>';
            })
            ->sortable()
            ->help('✓ = Processed, ✗ = Not Processed')
            ->width(80);

        $grid->column('payment_gateway', __('Gateway'))
            ->display(function ($gateway) {
                $icons = [
                    'pesapal' => '💳',
                    'cash' => '💵',
                    'mobile_money' => '📱',
                    'bank_transfer' => '🏦',
                ];
                $icon = $icons[$gateway] ?? '💰';
                return $icon . ' ' . ucfirst($gateway ?? 'N/A');
            })
            ->sortable()
            ->width(120);

        $grid->column('payment_date', __('Payment Date'))
            ->display(function ($date) {
                return $date ? date('d M Y, H:i', strtotime($date)) : '<span style="color: gray;">Pending</span>';
            })
            ->sortable()
            ->width(140);

        $grid->column('confirmed_at', __('Confirmed'))
            ->display(function ($date) {
                if (!$date) {
                    return '<span style="color: gray;">Not confirmed</span>';
                }
                return '<span style="color: green;">✓ ' . date('d M Y, H:i', strtotime($date)) . '</span>';
            })
            ->sortable()
            ->hide();

        $grid->column('items_processed', __('Processed'))
            ->display(function ($processed) {
                if ($processed) {
                    $date = $this->items_processed_at ? date('d M Y', strtotime($this->items_processed_at)) : '';
                    return '<span style="color: green; font-weight: bold;">✓ Yes</span><br><small>' . $date . '</small>';
                }
                return '<span style="color: orange;">✗ No</span>';
            })
            ->sortable()
            ->width(100);

        $grid->column('user.name', __('System User'))
            ->sortable()
            ->hide();

        $grid->column('project.title', __('Project'))
            ->display(function ($title) {
                return $title ? \Illuminate\Support\Str::limit($title, 30) : '<span style="color: gray;">-</span>';
            })
            ->sortable()
            ->hide();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable()
            ->hide();

        // Summary row
        $grid->footer(function ($query) {
            $data = [
                'total' => $query->count(),
                'completed' => $query->where('status', 'COMPLETED')->count(),
                'pending' => $query->where('status', 'PENDING')->count(),
                'failed' => $query->where('status', 'FAILED')->count(),
                'total_amount' => $query->sum('amount'),
                'completed_amount' => $query->where('status', 'COMPLETED')->sum('amount'),
            ];

            return view('admin.universal-payments-summary', $data);
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
        $show = new Show(UniversalPayment::findOrFail($id));

        // Disable edit and delete buttons
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableDelete();
            });

        // Payment Information Section
        $show->divider('Payment Information');

        $show->field('id', __('Payment ID'));
        
        $show->field('payment_reference', __('Payment Reference'))
            ->copyable();

        $show->field('status', __('Status'))->as(function ($status) {
            $colors = [
                'PENDING' => 'orange',
                'PROCESSING' => 'blue',
                'COMPLETED' => 'green',
                'FAILED' => 'red',
                'CANCELLED' => 'gray',
                'REFUNDED' => 'purple',
            ];
            $color = $colors[$status] ?? 'gray';
            return '<span style="color: ' . $color . '; font-weight: bold; font-size: 16px;">' . $status . '</span>';
        });

        $show->field('payment_type', __('Payment Type'));
        $show->field('payment_category', __('Payment Category'));
        $show->field('description', __('Description'));

        // Customer Information Section
        $show->divider('Customer Information');

        $show->field('customer_name', __('Customer Name'));
        $show->field('customer_email', __('Customer Email'));
        $show->field('customer_phone', __('Customer Phone'));
        $show->field('customer_address', __('Customer Address'));
        $show->field('user.name', __('System User'));

        // Financial Information Section
        $show->divider('Financial Information');

        $show->field('amount', __('Amount'))->as(function ($amount) {
            return '<strong style="color: #007bff; font-size: 20px;">UGX ' . number_format($amount, 2) . '</strong>';
        });

        $show->field('currency', __('Currency'));

        $show->field('refund_amount', __('Refund Amount'))->as(function ($amount) {
            return $amount ? 'UGX ' . number_format($amount, 2) : 'N/A';
        });

        $show->field('refunded_at', __('Refunded At'));
        $show->field('refund_reason', __('Refund Reason'));

        // Payment Items Section
        $show->divider('Payment Items');

        $show->field('items_count', __('Number of Items'));

        $show->field('payment_items', __('Items Details'))->as(function ($items) {
            if (!$items || empty($items)) {
                return '<span style="color: gray;">No items</span>';
            }

            $html = '<table class="table table-bordered" style="margin-top: 10px;">';
            $html .= '<thead><tr>';
            $html .= '<th>#</th>';
            $html .= '<th>Type</th>';
            $html .= '<th>ID</th>';
            $html .= '<th>Description</th>';
            $html .= '<th>Amount (UGX)</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($items as $index => $item) {
                $html .= '<tr>';
                $html .= '<td>' . ($index + 1) . '</td>';
                $html .= '<td>' . ($item['item_type'] ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item['item_id'] ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item['description'] ?? 'N/A') . '</td>';
                $html .= '<td><strong>UGX ' . number_format($item['amount'] ?? 0, 0) . '</strong></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            return $html;
        });

        $show->field('items_processed', __('Items Processed'))->as(function ($processed) {
            return $processed 
                ? '<span style="color: green; font-weight: bold;">✓ Yes</span>' 
                : '<span style="color: orange;">✗ No</span>';
        });

        $show->field('items_processed_at', __('Items Processed At'));
        $show->field('processing_notes', __('Processing Notes'));
        $show->field('processed_by', __('Processed By'));

        // Payment Gateway Information Section
        $show->divider('Payment Gateway Information');

        $show->field('payment_gateway', __('Payment Gateway'));
        $show->field('payment_method', __('Payment Method'));
        $show->field('payment_account', __('Payment Account'));

        // PesaPal Information Section
        $show->divider('PesaPal Information');

        $show->field('pesapal_order_tracking_id', __('Order Tracking ID'))->as(function ($value) {
            return $value ? '<code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">' . $value . '</code>' : '<span style="color: gray;">Not set</span>';
        });
        
        $show->field('pesapal_merchant_reference', __('Merchant Reference'))->as(function ($value) {
            return $value ? '<code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">' . $value . '</code>' : '<span style="color: gray;">Not set</span>';
        });
        
        $show->field('pesapal_notification_id', __('Notification ID'));
        $show->field('pesapal_status_code', __('Status Code'));
        
        $show->field('pesapal_redirect_url', __('Redirect URL'))->as(function ($url) {
            return $url ? '<a href="' . $url . '" target="_blank">' . $url . '</a>' : '<span style="color: gray;">Not set</span>';
        });
        
        $show->field('pesapal_callback_url', __('Callback URL'))->as(function ($url) {
            return $url ? '<a href="' . $url . '" target="_blank">' . $url . '</a>' : '<span style="color: gray;">Not set</span>';
        });
        
        $show->field('pesapal_response', __('PesaPal Response'))->as(function ($response) {
            if (!$response) {
                return '<span style="color: gray;">No response data</span>';
            }
            return '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;">' 
                . json_encode($response, JSON_PRETTY_PRINT) 
                . '</pre>';
        });

        $show->field('ipn_count', __('IPN Count'));
        $show->field('last_ipn_at', __('Last IPN At'));
        $show->field('last_status_check', __('Last Status Check'));

        // Investment Information Section
        $show->divider('Investment Information');

        $show->field('project.title', __('Project'))->as(function ($title) {
            return $title ?: '<span style="color: gray;">Not applicable</span>';
        });
        
        $show->field('number_of_shares', __('Number of Shares'));
        
        $show->field('project.share_price', __('Price per Share'))->as(function ($price) {
            return $price ? 'UGX ' . number_format($price, 0) : '<span style="color: gray;">Not applicable</span>';
        });

        // Status & Confirmation Section
        $show->divider('Status & Confirmation');

        $show->field('payment_status_code', __('Payment Status Code'));
        $show->field('status_message', __('Status Message'));
        $show->field('confirmation_code', __('Confirmation Code'))->copyable();
        $show->field('payment_date', __('Payment Date'));
        $show->field('confirmed_at', __('Confirmed At'));

        // Technical Information Section
        $show->divider('Technical Information');

        $show->field('ip_address', __('IP Address'));
        $show->field('user_agent', __('User Agent'))->as(function ($agent) {
            return $agent ? '<code>' . $agent . '</code>' : 'N/A';
        });

        $show->field('error_message', __('Error Message'))->as(function ($error) {
            return $error ? '<span style="color: red;">' . $error . '</span>' : '<span style="color: green;">No errors</span>';
        });

        $show->field('retry_count', __('Retry Count'));
        $show->field('last_retry_at', __('Last Retry At'));

        // Metadata Section
        $show->field('metadata', __('Metadata'))->as(function ($metadata) {
            if (!$metadata || empty($metadata)) {
                return '<span style="color: gray;">No metadata</span>';
            }
            return '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;">' 
                . json_encode($metadata, JSON_PRETTY_PRINT) 
                . '</pre>';
        });

        // Audit Information Section
        $show->divider('Audit Information');

        $show->field('created_by', __('Created By'));
        $show->field('updated_by', __('Updated By'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        $show->field('deleted_at', __('Deleted At'));

        return $show;
    }

    /**
     * Make a form builder - DISABLED (Read-only controller)
     *
     * @return void
     */
    protected function form()
    {
        // Form disabled - This is a read-only controller
        // Payments should only be created through the system, not manually
        abort(403, 'Manual creation/editing of payments is not allowed. Payments are created automatically by the system.');
    }
}
