<?php

namespace App\Admin\Controllers;

use App\Models\VslaMeeting;
use App\Models\Project;
use App\Models\FfsGroup;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VslaMeetingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'VSLA Meetings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VslaMeeting());

        $grid->model()->with(['cycle', 'group', 'creator'])->orderBy('id', 'desc');

        $grid->disableCreateButton(); // Meetings created from mobile app
        $grid->disableExport();

        $grid->quickSearch('meeting_number')->placeholder('Search by meeting number');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('cycle_id', 'Cycle')
                ->select(Project::where('is_vsla_cycle', 'Yes')->pluck('title', 'id'));

            $filter->equal('group_id', 'Group')
                ->select(FfsGroup::where('type', 'VSLA')->pluck('name', 'id'));

            $filter->equal('processing_status', 'Status')->select([
                'pending' => 'Pending',
                'processing' => 'Processing',
                'completed' => 'Completed',
                'failed' => 'Failed',
                'needs_review' => 'Needs Review',
            ]);

            $filter->between('meeting_date', 'Meeting Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();

        $grid->column('group_id', __('VSLA Group'))
            ->display(function ($groupId) {
                if (!$groupId) return '-';
                $group = \App\Models\FfsGroup::find($groupId);
                return $group ? $group->name : '-';
            })
            ->sortable();


        $grid->column('meeting_number', __('Meeting #'))
            ->display(function ($number) {
                return "<strong>#{$number}</strong>";
            })
            ->sortable();

        $grid->column('meeting_date', __('Date'))
            ->display(function ($date) {
                return date('M d, Y', strtotime($date));
            })
            ->sortable();

        $grid->column('cycle.title', __('Cycle'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 25);
            });

        $grid->column('attendance_summary', __('Attendance'))
            ->display(function () {
                $total = $this->members_present + $this->members_absent;
                $rate = $total > 0 ? round(($this->members_present / $total) * 100, 1) : 0;
                $color = $rate >= 75 ? 'success' : ($rate >= 50 ? 'warning' : 'danger');
                return "<span class='label label-{$color}'>{$this->members_present}/{$total} ({$rate}%)</span>";
            })
            ->sortable();

        $grid->column('financial_summary', __('Financial Summary'))
            ->display(function () {
                $collected = $this->total_cash_collected ?? 0;
                $disbursed = $this->total_loans_disbursed ?? 0;
                $net = $collected - $disbursed;
                $color = $net >= 0 ? 'success' : 'danger';
                return "<div style='font-size:11px;'>"
                    . "<div>In: <strong>" . number_format($collected, 0) . "</strong></div>"
                    . "<div>Out: <strong>" . number_format($disbursed, 0) . "</strong></div>"
                    . "<div class='text-{$color}'>Net: <strong>" . number_format($net, 0) . "</strong></div>"
                    . "</div>";
            });
        
        $grid->column('transactions_info', __('Transactions'))
            ->display(function () {
                $txnCount = \App\Models\AccountTransaction::where('meeting_id', $this->id)->count();
                $url = "/admin/account-transactions?&meeting_id={$this->id}";
                
                if ($txnCount > 0) {
                    $badge = "<span class='label label-primary'>{$txnCount} transactions</span>";
                    return "<a href='{$url}' target='_blank'>{$badge}</a>";
                }
                return "<span class='label label-default'>0 transactions</span>";
            });

        $grid->column('processing_status', __('Status'))
            ->label([
                'pending' => 'warning',
                'processing' => 'info',
                'completed' => 'success',
                'failed' => 'danger',
                'needs_review' => 'warning',
            ])
            ->sortable();

        $grid->column('has_errors', __('Errors'))
            ->bool(['✗', '✓'])
            ->sortable();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('M d, Y H:i', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit(); // Meetings are historical data
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
        $show = new Show(VslaMeeting::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('local_id', __('Local ID'));
        $show->field('meeting_number', __('Meeting Number'));
        $show->field('meeting_date', __('Meeting Date'));

        $show->divider('Group & Cycle');
        $show->field('cycle.title', __('Cycle'));
        $show->field('group.name', __('Group'));
        $show->field('group.code', __('Group Code'));

        $show->divider('Attendance');
        $show->field('members_present', __('Members Present'));
        $show->field('attendance_rate', __('Attendance Rate'))->as(function ($rate) {
            return number_format($rate, 1) . '%';
        });

        $show->divider('Financial Summary');
        $show->field('total_cash_collected', __('Total Cash Collected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('net_cash_flow', __('Net Cash Flow'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        // Add transaction counts
        $show->field('total_savings_collected', __('Savings Collected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount ?? 0, 0);
        });
        $show->field('total_fines_collected', __('Fines Collected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount ?? 0, 0);
        });
        $show->field('total_welfare_collected', __('Welfare Collected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount ?? 0, 0);
        });
        $show->field('total_social_fund_collected', __('Social Fund Collected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount ?? 0, 0);
        });
        $show->field('total_loans_disbursed', __('Loans Disbursed'))->as(function ($amount) {
            return 'UGX ' . number_format($amount ?? 0, 0);
        });
        $show->field('total_shares_sold', __('Shares Sold'));
        $show->field('total_share_value', __('Share Value'))->as(function ($amount) {
            return 'UGX ' . number_format($amount ?? 0, 0);
        });
        
        // Link to view related transactions
        $show->divider('Related Records');
        $show->field('id', __('Account Transactions'))->unescape()->as(function ($id) {
            $count = \App\Models\AccountTransaction::where('meeting_id', $id)->count();
            $url = "/admin/account-transactions?&meeting_id={$id}";
            if ($count > 0) {
                return "<a href='{$url}' class='btn btn-primary btn-sm' target='_blank'><i class='fa fa-list'></i> View {$count} Transactions</a>";
            }
            return "<span class='text-muted'>No transactions recorded</span>";
        });

        $show->divider('Meeting Data');
        $show->field('savings_data', __('Savings Data'))->json();
        $show->field('loans_data', __('Loans Data'))->json();
        $show->field('fines_data', __('Fines Data'))->json();
        $show->field('welfare_data', __('Welfare Data'))->json();
        $show->field('social_fund_data', __('Social Fund Data'))->json();
        $show->field('attendance_data', __('Attendance Data'))->json();
        $show->field('action_plans_data', __('Action Plans Data'))->json();

        $show->divider('Processing Status');
        $show->field('processing_status', __('Status'));
        $show->field('has_errors', __('Has Errors'))->as(function ($val) {
            return $val ? 'Yes' : 'No';
        });
        $show->field('has_warnings', __('Has Warnings'))->as(function ($val) {
            return $val ? 'Yes' : 'No';
        });
        $show->field('error_messages', __('Errors'))->json();
        $show->field('warning_messages', __('Warnings'))->json();

        $show->field('processed_at', __('Processed At'));
        $show->field('creator.name', __('Created By'));
        $show->field('processor.name', __('Processed By'));
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
        $form = new Form(new VslaMeeting());

        // Meetings should not be manually created or edited
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        $form->display('id', __('ID'));
        $form->display('meeting_number', __('Meeting Number'));
        $form->display('meeting_date', __('Meeting Date'));
        $form->display('processing_status', __('Status'));

        return $form;
    }
}
