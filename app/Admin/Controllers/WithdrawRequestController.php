<?php

namespace App\Admin\Controllers;

use App\Models\WithdrawRequest;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use PDF;

class WithdrawRequestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Withdraw Requests';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new WithdrawRequest());
        $grid->model()->orderBy('created_at', 'desc');

        // ID Column
        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60)
            ->style('font-weight: bold;');

        // User Column
        $grid->column('user.name', __('User'))
            ->display(function () {
                return '<a href="/admin/users/' . $this->user_id . '">' . $this->user->name . '</a><br>' .
                    '<small class="text-muted">' . $this->user->phone_number . '</small>';
            })
            ->width(180);

        // DIP ID Column
        $grid->column('user.business_name', __('DIP ID'))
            ->display(function () {
                if (empty($this->user->business_name)) {
                    return '-';
                }
                return '<span class="label label-primary">' . $this->user->business_name . '</span>';
            })
            ->width(90);

        // Amount Column
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return '<strong style="color: #d9534f;">UGX ' . number_format($amount, 2) . '</strong>';
            })
            ->sortable()
            ->width(130);

        // Balance Before Column
        $grid->column('account_balance_before', __('Balance Before'))
            ->display(function ($balance) {
                return 'UGX ' . number_format($balance, 2);
            })
            ->width(130);

        // Current Balance Column
        $grid->column('current_balance', __('Current Balance'))
            ->display(function () {
                $currentBalance = $this->user->calculateAccountBalance();
                $color = $currentBalance >= $this->amount ? 'success' : 'danger';
                return '<span class="text-' . $color . '">UGX ' . number_format($currentBalance, 2) . '</span>';
            })
            ->width(130);

        // Status Column
        $grid->column('status', __('Status'))
            ->label([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ])
            ->sortable()
            ->filter([
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ])
            ->width(100);

        // Payment Method Column
        $grid->column('payment_method', __('Payment Method'))
            ->display(function ($method) {
                return ucfirst(str_replace('_', ' ', $method));
            })
            ->width(120);

        // Payment Phone Column
        $grid->column('payment_phone_number', __('Payment Phone'))
            ->width(120);

        // Description Column
        $grid->column('description', __('Description'))
            ->limit(50)
            ->width(200);

        // Processed By Column
        $grid->column('processedBy.name', __('Processed By'))
            ->display(function () {
                if (!$this->processed_by_id) {
                    return '<span class="text-muted">-</span>';
                }
                return $this->processedBy->name ?? '-';
            })
            ->width(120);

        // Processed At Column
        $grid->column('processed_at', __('Processed At'))
            ->display(function ($date) {
                if (!$date) {
                    return '<span class="text-muted">-</span>';
                }
                return date('d M Y H:i', strtotime($date));
            })
            ->sortable()
            ->width(130);

        // Created At Column
        $grid->column('created_at', __('Requested At'))
            ->display(function ($date) {
                return date('d M Y H:i', strtotime($date));
            })
            ->sortable()
            ->width(130);

        // Quick Search
        $grid->quickSearch('id', 'user.name', 'user.phone_number', 'user.business_name')
            ->placeholder('Search by ID, name, phone, or DIP ID');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('status', 'Status')->radio([
                '' => 'All',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ]);

            $filter->like('user.name', 'User Name');
            $filter->like('user.phone_number', 'Phone Number');
            $filter->like('user.business_name', 'DIP ID');
            $filter->between('amount', 'Amount')->decimal();
            $filter->between('created_at', 'Requested Date')->date();
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            // $actions->disableView(); 

            // Only show approve/reject buttons for pending requests without linked transactions
            if ($actions->row->status === 'pending' && is_null($actions->row->account_transaction_id)) {
                $id = $actions->row->id;

                // Approve button
                $actions->append('
                    <a href="/admin/withdraw-requests/' . $id . '/approve" 
                       class="btn btn-xs btn-success" 
                       style="margin-right: 5px;"
                       onclick="return confirm(\'Are you sure you want to APPROVE this withdrawal request?\\n\\nAmount: UGX ' . number_format($actions->row->amount, 2) . '\\nUser: ' . $actions->row->user->name . '\\n\\nThis will create a withdrawal transaction and deduct the amount from user balance.\')">
                        <i class="fa fa-check"></i> Approve
                    </a>
                ');

                // Reject button with prompt for reason
                $actions->append('
                    <a href="javascript:void(0);" 
                       class="btn btn-xs btn-danger" 
                       onclick="
                           var reason = prompt(\'Enter rejection reason:\', \'Insufficient documentation provided\');
                           if (reason !== null && reason.trim() !== \'\') {
                               window.location.href = \'/admin/withdraw-requests/' . $id . '/reject?reason=\' + encodeURIComponent(reason);
                           }
                           return false;
                       ">
                        <i class="fa fa-times"></i> Reject
                    </a>
                ');
            }
        });

        // Disable create button
        $grid->disableCreateButton();

        // Add statistics row with PDF button
        $grid->header(function ($query) {
            $totalPending = WithdrawRequest::where('status', 'pending')->count();
            $totalPendingAmount = WithdrawRequest::where('status', 'pending')->sum('amount');
            $totalApproved = WithdrawRequest::where('status', 'approved')->count();
            $totalApprovedAmount = WithdrawRequest::where('status', 'approved')->sum('amount');

            $pdfUrl = admin_url('withdraw-requests/pdf-pending');

            return '<div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>Statistics:</strong> 
                    Pending: ' . $totalPending . ' requests (UGX ' . number_format($totalPendingAmount, 2) . ') | 
                    Approved: ' . $totalApproved . ' requests (UGX ' . number_format($totalApprovedAmount, 2) . ')
                </div>
                <a href="' . $pdfUrl . '" target="_blank" class="btn btn-primary btn-sm" style="margin-left: 10px;">
                    <i class="fa fa-file-pdf-o"></i> Generate PDF (Pending)
                </a>
            </div>';
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
        $show = new Show(WithdrawRequest::findOrFail($id));

        $withdrawRequest = WithdrawRequest::findOrFail($id);

        $show->field('id', __('ID'));

        $show->field('user.name', __('User Name'));
        $show->field('user.email', __('User Email'));
        $show->field('user.phone_number', __('User Phone'));
        $show->field('user.business_name', __('User DIP ID'));

        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 2);
        });

        $show->field('account_balance_before', __('Balance Before'))->as(function ($balance) {
            return 'UGX ' . number_format($balance, 2);
        });

        $show->field('current_balance', __('Current Balance'))->as(function () {
            $currentBalance = $this->user->calculateAccountBalance();
            return 'UGX ' . number_format($currentBalance, 2);
        });

        $show->field('status', __('Status'))->unescape()->as(function ($status) {
            $colors = [
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ];
            return '<span class="label label-' . $colors[$status] . '">' . ucfirst($status) . '</span>';
        });

        $show->field('description', __('Description'));
        $show->field('payment_method', __('Payment Method'))->as(function ($method) {
            return ucfirst(str_replace('_', ' ', $method));
        });
        $show->field('payment_phone_number', __('Payment Phone Number'));

        $show->field('admin_note', __('Admin Note'));
        $show->field('processedBy.name', __('Processed By'));
        $show->field('processed_at', __('Processed At'));

        $show->field('accountTransaction.id', __('Transaction ID'));

        $show->field('created_at', __('Requested At'));
        $show->field('updated_at', __('Updated At'));

        // Add action buttons for pending requests
        if ($withdrawRequest->status === 'pending' && is_null($withdrawRequest->account_transaction_id)) {
            $show->panel()
                ->tools(function ($tools) use ($id) {
                    $tools->append('
                        <a href="' . url('withdraw-requests/'. $id)  . '/approve" 
                           class="btn btn-sm btn-success" 
                           onclick="return confirm(\'Are you sure you want to APPROVE this withdrawal request?\')">
                            <i class="fa fa-check"></i> Approve Request
                        </a>
                        <a href="javascript:void(0);" 
                           class="btn btn-sm btn-danger" 
                           onclick="
                               var reason = prompt(\'Enter rejection reason:\', \'Insufficient documentation provided\');
                               if (reason !== null && reason.trim() !== \'\') {
                                   window.location.href = \'' . url('withdraw-requests/') . $id . '/reject?reason=\' + encodeURIComponent(reason);
                               }
                           ">
                            <i class="fa fa-times"></i> Reject Request
                        </a>
                    ');
                });
        }

        return $show;
    }

    /**
     * Approve a withdraw request
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        try {
            $withdrawRequest = WithdrawRequest::findOrFail($id);
            $admin = auth('admin')->user();

            $result = $withdrawRequest->approve($admin);

            if ($result['success']) {
                admin_success('Success', $result['message']);
            } else {
                admin_error('Error', $result['message']);
            }

            return back();
        } catch (\Exception $e) {
            admin_error('Error', 'Failed to approve withdraw request: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Reject a withdraw request
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject($id)
    {
        try {
            $withdrawRequest = WithdrawRequest::findOrFail($id);
            $admin = auth('admin')->user();

            $reason = request()->input('reason', 'Rejected by admin');
            $result = $withdrawRequest->reject($admin, $reason);

            if ($result['success']) {
                admin_success('Success', $result['message']);
            } else {
                admin_error('Error', $result['message']);
            }

            return back();
        } catch (\Exception $e) {
            admin_error('Error', 'Failed to reject withdraw request: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Generate PDF for pending withdraw requests
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePendingPDF()
    {
        try {
            // Get all pending withdraw requests
            $requests = WithdrawRequest::where('status', 'pending')
                ->with(['user'])
                ->orderBy('created_at', 'asc')
                ->get();

            // Calculate totals
            $totalAmount = $requests->sum('amount');
            $totalCount = $requests->count();

            // Prepare data for view
            $data = [
                'requests' => $requests,
                'totalAmount' => $totalAmount,
                'totalCount' => $totalCount,
                'generatedAt' => now()->format('F d, Y h:i A'),
                'generatedBy' => auth('admin')->user()->name ?? 'Admin',
            ];

            // Load view and generate PDF
            $pdf = \PDF::loadView('admin.withdraw-requests-pdf', $data);
            
            // Set paper size and orientation (portrait for better A4 layout)
            $pdf->setPaper('A4', 'portrait');
            
            // Stream PDF to browser (opens in new tab)
            return $pdf->stream('pending-withdraw-requests-' . date('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            admin_error('Error', 'Failed to generate PDF: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Make a form builder (disabled).
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new WithdrawRequest());

        // Disable all form actions since we handle approvals/rejections separately
        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        $form->disableViewCheck();

        return $form;
    }
}
