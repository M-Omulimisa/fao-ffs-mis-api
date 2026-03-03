<?php

namespace App\Admin\Controllers;

use App\Models\VslaLoan;
use App\Models\Project;
use App\Models\VslaMeeting;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Traits\IpScopeable;

class VslaLoanController extends AdminController
{
    use IpScopeable;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'VSLA Loans';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VslaLoan());

        // IP scoping - filter by meeting's IP
        $ipId = $this->getAdminIpId();
        if ($ipId !== null) {
            $grid->model()->whereHas('meeting', function ($q) use ($ipId) {
                $q->where('ip_id', $ipId);
            });
        }

        $grid->model()->with(['cycle', 'meeting', 'borrower', 'creator'])->orderBy('id', 'desc');
        
        $grid->disableCreateButton(); // Loans created from meetings
        $grid->disableExport();

        $grid->quickSearch('id')->placeholder('Search by ID');

        $ipId = $this->getAdminIpId();
        $isSuperAdmin = $this->isSuperAdmin();

        $grid->filter(function ($filter) use ($ipId, $isSuperAdmin) {
            $filter->disableIdFilter();
            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(\App\Models\ImplementingPartner::getDropdownOptions());
            }

            // $ipId captured via use()
            $ipGroupIds = $ipId ? FfsGroup::where('ip_id', $ipId)->pluck('id') : null;

            $filter->equal('cycle_id', 'Cycle')
                ->select(Project::where('is_vsla_cycle', 'Yes')
                    ->when($ipGroupIds, fn($q) => $q->whereIn('group_id', $ipGroupIds))
                    ->pluck('title', 'id'));

            $filter->equal('borrower_id', 'Borrower')
                ->select(User::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->orderBy('name')->pluck('name', 'id'));

            $filter->equal('status', 'Status')->select([
                'pending' => 'Pending',
                'active' => 'Active',
                'paid' => 'Paid',
                'defaulted' => 'Defaulted',
            ]);

            $filter->between('disbursement_date', 'Disbursement Date')->date();
            $filter->between('due_date', 'Due Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('cycle.ffs_group.name', __('VSLA Group'))
            ->display(function () {
                if (!$this->cycle || !$this->cycle->ffs_group) {
                    return '<span class="label label-default">No Group</span>';
                }
                $code = $this->cycle->ffs_group->code ?? '';
                $name = \Illuminate\Support\Str::limit($this->cycle->ffs_group->name, 18);
                return "<span class='label label-info'>{$name}" . ($code ? " ({$code})" : '') . "</span>";
            });
        
        $grid->column('cycle.title', __('Cycle'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 20);
            });

        $grid->column('meeting.meeting_number', __('Meeting #'))
            ->display(function ($number) {
                return "<strong>#{$number}</strong>";
            })
            ->sortable();

        $grid->column('borrower.name', __('Borrower'))
            ->display(function ($name) {
                return \Illuminate\Support\Str::limit($name, 20);
            });

        $grid->column('principal_amount', __('Principal'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();

        $grid->column('interest_rate', __('Interest %'))
            ->display(function ($rate) {
                return $rate . '%';
            })
            ->sortable();

        $grid->column('total_amount_due', __('Total Due'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();

        $grid->column('amount_paid', __('Paid'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();

        $grid->column('loan_progress', __('Payment Progress'))
            ->display(function () {
                $total = $this->total_amount_due ?? 1;
                $paid = $this->amount_paid ?? 0;
                $percent = round(($paid / $total) * 100, 1);
                $color = $percent >= 75 ? 'success' : ($percent >= 25 ? 'warning' : 'danger');
                return "<div style='width:100px;'>"
                    . "<div class='progress' style='margin-bottom:2px;height:15px;'>"
                    . "<div class='progress-bar progress-bar-{$color}' style='width:{$percent}%'>{$percent}%</div>"
                    . "</div>"
                    . "<small>" . number_format($paid, 0) . "/" . number_format($total, 0) . "</small>"
                    . "</div>";
            });
        
        $grid->column('balance', __('Balance'))
            ->display(function ($amount) {
                $color = $amount > 0 ? 'danger' : 'success';
                return '<span style="color: ' . $color . '; font-weight: bold;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable();

        $grid->column('status', __('Status'))
            ->label([
                'pending' => 'warning',
                'active' => 'primary',
                'paid' => 'success',
                'defaulted' => 'danger',
            ])
            ->sortable();

        $grid->column('is_overdue', __('Overdue'))
            ->display(function ($isOverdue) {
                if ($isOverdue && $this->status === 'active') {
                    $days = $this->days_overdue;
                    return '<span class="label label-danger">' . $days . ' days</span>';
                }
                return '<span class="label label-success">No</span>';
            });

        $grid->column('due_date', __('Due Date'))
            ->display(function ($date) {
                return date('M d, Y', strtotime($date));
            })
            ->sortable();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('M d, Y', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit(); // Loans are historical data
            $actions->disableDelete();
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
        $record = VslaLoan::findOrFail($id);

        // IP scope guard via meeting -> group
        if (!$this->isSuperAdmin()) {
            $meeting = $record->meeting ?? null;
            if (!$meeting || (int) $meeting->ip_id !== $this->getAdminIpId()) {
                return $this->denyIpAccess();
            }
        }

        $show = new Show($record);

        $show->field('id', __('ID'));
        $show->field('local_id', __('Local ID'));
        
        $show->divider('Loan Details');
        $show->field('cycle.title', __('Cycle'));
        $show->field('meeting.meeting_number', __('Meeting Number'));
        $show->field('meeting.meeting_date', __('Meeting Date'));
        
        $show->divider('Borrower Information');
        $show->field('borrower.name', __('Borrower Name'));
        $show->field('borrower.phone_number', __('Phone'));
        $show->field('borrower.email', __('Email'));
        
        $show->divider('Loan Amounts');
        $show->field('principal_amount', __('Principal Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('interest_rate', __('Interest Rate (%)'))->as(function ($rate) {
            return $rate . '%';
        });
        $show->field('total_amount_due', __('Total Amount Due'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('amount_paid', __('Amount Paid'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('balance', __('Balance'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->divider('Loan Timeline');
        $show->field('disbursement_date', __('Disbursement Date'));
        $show->field('duration_months', __('Duration (Months)'));
        $show->field('due_date', __('Due Date'));
        
        $show->divider('Status');
        $show->field('status', __('Status'));
        $show->field('is_overdue', __('Is Overdue'))->as(function ($val) {
            return $val ? 'Yes' : 'No';
        });
        $show->field('days_overdue', __('Days Overdue'));
        
        $show->field('purpose', __('Purpose'));
        $show->field('guarantor_1_id', __('Guarantor 1 ID'));
        $show->field('guarantor_2_id', __('Guarantor 2 ID'));
        
        $show->field('creator.name', __('Created By'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new VslaLoan());

        // Loans should not be manually created or edited
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->display('id', __('ID'));
        $form->display('borrower.name', __('Borrower'));
        $form->display('principal_amount', __('Principal Amount'));
        $form->display('status', __('Status'));

        return $form;
    }
}
