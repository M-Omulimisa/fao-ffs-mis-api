<?php

namespace App\Admin\Controllers;

use App\Models\Project;
use App\Models\ProjectTransaction;
use App\Admin\Helpers\RoleBasedDashboard;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class ProjectController extends AdminController
{
    use RoleBasedDashboard;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Cycles';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Project());

        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        
        // Managers can only view, not create/edit/delete
        if (!$this->canSeeFinancialDetails()) {
            $grid->disableCreateButton();
            $grid->disableActions();
            $grid->disableBatchActions();
        }

        $grid->quickSearch('title', 'cycle_name')->placeholder('Search by title or cycle name');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('title', 'Title');
            $filter->like('cycle_name', 'Cycle Name');
            
            $filter->equal('is_vsla_cycle', 'Type')->select([
                'Yes' => 'VSLA Cycle',
                'No' => 'Regular Project',
            ]);

            $filter->equal('status', 'Status')->select([
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
            ]);
            
            $filter->equal('group_id', 'VSLA Group')->select(\App\Models\FfsGroup::where('type', 'VSLA')->pluck('name', 'id'));
            
            $filter->between('start_date', 'Start Date')->date();
            $filter->between('end_date', 'End Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();
        
    

        $grid->column('cycle_name', __('Cycle Name'))
            ->display(function ($name) {
                return $name ?: '-';
            })
            ->sortable();
        
        $grid->column('group_id', __('VSLA Group'))
            ->display(function ($groupId) {
                if (!$groupId) return '-';
                $group = \App\Models\FfsGroup::find($groupId);
                return $group ? $group->name : '-';
            })
            ->sortable();

        $grid->column('title', __('Title'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 40);
            })
            ->sortable();

        $grid->column('status', __('Status'))
            ->label([
                'pending' => 'warning',
                'ongoing' => 'success',
                'completed' => 'info',
                'cancelled' => 'danger',
            ])
            ->sortable();
        
        // VSLA-specific columns
        $grid->column('share_value', __('Share Value'))
            ->display(function ($value) {
                if (!$this->is_vsla_cycle || !$value) return '-';
                return 'UGX ' . number_format($value, 0);
            })
            ->sortable();
        
        $grid->column('meeting_frequency', __('Meeting'))
            ->display(function ($freq) {
                return $freq ?: '-';
            })
            ->sortable();
        
        $grid->column('loan_interest_rate', __('Interest Rate'))
            ->display(function ($rate) {
                if (!$this->is_vsla_cycle || !$rate) return '-';
                return $rate . '% ' . ($this->interest_frequency ?: '');
            })
            ->sortable();

        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return $date ? date('d M Y', strtotime($date)) : '-';
            })
            ->sortable();

        $grid->column('end_date', __('End Date'))
            ->display(function ($date) {
                return $date ? date('d M Y', strtotime($date)) : '-';
            })
            ->sortable();
        
        // Keep financial columns for regular projects
        $grid->column('share_price', __('Share Price'))
            ->display(function ($price) {
                if ($this->is_vsla_cycle === 'Yes') return '-';
                return 'UGX ' . number_format($price, 0);
            })
            ->hide();

        $grid->column('total_investment', __('Investment'))
            ->display(function ($amount) {
                if ($this->is_vsla_cycle === 'Yes') return '-';
                return 'UGX ' . number_format($amount, 0);
            })
            ->hide();

        

        $grid->column('total_expenses', __('Expenses'))
            ->display(function () {
                $expenses = ProjectTransaction::where('project_id', $this->id)
                    ->where('type', 'expense')
                    ->sum('amount');
                return 'UGX ' . number_format($expenses, 0);
            })
            ->sortable();

        $grid->column('balance', __('Balance'))
            ->display(function () {
                $income = ProjectTransaction::where('project_id', $this->id)
                    ->where('type', 'income')
                    ->sum('amount');
                $expenses = ProjectTransaction::where('project_id', $this->id)
                    ->where('type', 'expense')
                    ->sum('amount');
                $balance = $income - $expenses;
                $color = $balance >= 0 ? 'green' : 'red';
                return '<span style="color: ' . $color . '; font-weight: bold;">UGX ' . number_format($balance, 0) . '</span>';
            })
            ->sortable();

        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->hide();

        $grid->column('end_date', __('End Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->hide();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
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
        $show = new Show(Project::findOrFail($id));

        $show->field('id', __('ID'));
        
        // Type and Basic Info
        $show->field('is_vsla_cycle', __('Type'))->using([
            'Yes' => 'VSLA Savings Cycle',
            'No' => 'Regular Project',
        ])->label([
            'Yes' => 'success',
            'No' => 'default',
        ]);
        
        $show->field('title', __('Title'));
        $show->field('description', __('Description'))->unescape();
        $show->field('status', __('Status'))->label([
            'ongoing' => 'success',
            'completed' => 'info',
            'on_hold' => 'warning',
        ]);
        
        // VSLA Group Information
        $show->divider('VSLA Group Information');
        
        $show->field('group_id', __('VSLA Group'))->as(function ($groupId) {
            if (!$groupId) return 'N/A';
            $group = \App\Models\FfsGroup::find($groupId);
            return $group ? $group->name . ' (' . $group->code . ')' : 'N/A';
        });
        
        $show->field('cycle_name', __('Cycle Name'))->as(function ($name) {
            return $name ?: 'N/A';
        });
        
        // VSLA Cycle Settings
        $show->divider('Cycle Settings');
        
        $show->field('share_value', __('Share Value (Per Meeting)'))->as(function ($value) {
            return $value ? 'UGX ' . number_format($value, 0) : 'N/A';
        });
        
        $show->field('meeting_frequency', __('Meeting Frequency'))->as(function ($freq) {
            return $freq ?: 'N/A';
        });
        
        $show->field('start_date', __('Cycle Start Date'))->as(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        
        $show->field('end_date', __('Cycle End Date'))->as(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : 'N/A';
        });
        
        $show->field('duration', __('Cycle Duration'))->as(function () {
            if (!$this->start_date || !$this->end_date) return 'N/A';
            $start = \Carbon\Carbon::parse($this->start_date);
            $end = \Carbon\Carbon::parse($this->end_date);
            $months = $start->diffInMonths($end);
            $weeks = $start->diffInWeeks($end);
            return "{$months} months ({$weeks} weeks)";
        });
        
        // Loan Configuration
        $show->divider('Loan Configuration');
        
        $show->field('loan_interest_rate', __('Primary Interest Rate'))->as(function ($rate) {
            return $rate ? $rate . '%' : 'N/A';
        });
        
        $show->field('interest_frequency', __('Interest Frequency'))->as(function ($freq) {
            return $freq ?: 'N/A';
        });
        
        $show->field('weekly_loan_interest_rate', __('Weekly Interest Rate'))->as(function ($rate) {
            return $rate ? $rate . '%' : 'N/A';
        });
        
        $show->field('monthly_loan_interest_rate', __('Monthly Interest Rate'))->as(function ($rate) {
            return $rate ? $rate . '%' : 'N/A';
        });
        
        $show->field('minimum_loan_amount', __('Minimum Loan Amount'))->as(function ($amount) {
            return $amount ? 'UGX ' . number_format($amount, 0) : 'N/A';
        });
        
        $show->field('maximum_loan_multiple', __('Maximum Loan Multiple'))->as(function ($multiple) {
            return $multiple ? $multiple . 'x of share value' : 'N/A';
        });
        
        $show->field('late_payment_penalty', __('Late Payment Penalty'))->as(function ($penalty) {
            return $penalty ? $penalty . '%' : 'N/A';
        });
        
        // Cycle Statistics
        $show->divider('Cycle Statistics');
        
        $show->field('total_members', __('Total Members'))->as(function () {
            if (!$this->group_id) return 'N/A';
            return \App\Models\User::where('group_id', $this->group_id)
                ->where('status', 'Active')
                ->count();
        });
        
        $show->field('total_meetings', __('Total Meetings'))->as(function () {
            return \App\Models\VslaMeeting::where('cycle_id', $this->id)->count();
        });
        
        $show->field('total_savings', __('Total Savings'))->as(function () {
            $savings = \App\Models\VslaMeeting::where('cycle_id', $this->id)
                ->sum('total_savings');
            return 'UGX ' . number_format($savings, 0);
        });
        
        $show->field('active_loans', __('Active Loans'))->as(function () {
            return \App\Models\VslaLoan::where('cycle_id', $this->id)
                ->where('status', 'active')
                ->count();
        });
        
        $show->field('total_loans_disbursed', __('Total Loans Disbursed'))->as(function () {
            $total = \App\Models\VslaLoan::where('cycle_id', $this->id)
                ->sum('amount');
            return 'UGX ' . number_format($total, 0);
        });
        
        $show->field('total_loan_repayments', __('Total Loan Repayments'))->as(function () {
            $repayments = \App\Models\VslaLoan::where('cycle_id', $this->id)
                ->sum('amount_paid');
            return 'UGX ' . number_format($repayments, 0);
        });

        // Regular Project Fields (only show if not VSLA)
        $show->panel()->tools(function ($tools) {
            // Add custom tools if needed
        });
        
        $show->field('image', __('Image'))->image();
        
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
        $form = new Form(new Project());
        
        // Type Selection
        $form->select('is_vsla_cycle', __('Type'))
            ->options([
                'Yes' => 'VSLA Savings Cycle',
                'No' => 'Regular Project',
            ])
            ->default('Yes')
            ->rules('required')
            ->help('Select whether this is a VSLA savings cycle or regular project');
        
        // VSLA Group Selection (shown only for VSLA cycles)
        $form->select('group_id', __('VSLA Group'))
            ->options(\App\Models\FfsGroup::where('type', 'VSLA')->pluck('name', 'id'))
            ->rules('required_if:is_vsla_cycle,Yes')
            ->help('Select the VSLA group for this cycle');
        
        // Basic Information
        $form->divider('Basic Information');
        
        $form->text('cycle_name', __('Cycle Name'))
            ->rules('required_if:is_vsla_cycle,Yes|max:200')
            ->placeholder('e.g., January - December 2025 Cycle')
            ->help('Name of the savings cycle');

        $form->text('title', __('Title'))
            ->rules('required|max:255')
            ->placeholder('Project/Cycle title');

        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(4)
            ->placeholder('Describe the purpose and goals of this cycle/project');

        $form->select('status', __('Status'))
            ->options([
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
            ])
            ->default('ongoing')
            ->rules('required');
        
        // Cycle Dates
        $form->divider('Cycle Period');

        $form->date('start_date', __('Start Date'))
            ->rules('required')
            ->help('When does the cycle begin?');

        $form->date('end_date', __('End Date'))
            ->rules('required')
            ->help('When does the cycle end?');
        
        // VSLA Cycle Settings
        $form->divider('VSLA Cycle Settings');
        
        $form->currency('share_value', __('Share Value (Per Meeting)'))
            ->symbol('UGX')
            ->rules('required_if:is_vsla_cycle,Yes|numeric|min:1000')
            ->default(5000)
            ->help('Amount each member contributes per meeting');
        
        $form->select('meeting_frequency', __('Meeting Frequency'))
            ->options([
                'Weekly' => 'Weekly',
                'Bi-weekly' => 'Bi-weekly (Every 2 weeks)',
                'Monthly' => 'Monthly',
            ])
            ->rules('required_if:is_vsla_cycle,Yes')
            ->default('Weekly')
            ->help('How often does the group meet?');
        
        // Loan Configuration
        $form->divider('Loan Configuration');
        
        $form->decimal('loan_interest_rate', __('Primary Interest Rate (%)'))
            ->rules('nullable|numeric|min:0|max:100')
            ->default(10)
            ->help('Main interest rate for loans');
        
        $form->select('interest_frequency', __('Interest Calculation'))
            ->options([
                'Weekly' => 'Weekly',
                'Monthly' => 'Monthly',
            ])
            ->default('Monthly')
            ->help('How often is interest calculated?');
        
        $form->decimal('weekly_loan_interest_rate', __('Weekly Interest Rate (%)'))
            ->rules('nullable|numeric|min:0|max:100')
            ->help('Interest rate if calculated weekly');
        
        $form->decimal('monthly_loan_interest_rate', __('Monthly Interest Rate (%)'))
            ->rules('nullable|numeric|min:0|max:100')
            ->help('Interest rate if calculated monthly');
        
        $form->currency('minimum_loan_amount', __('Minimum Loan Amount'))
            ->symbol('UGX')
            ->rules('nullable|numeric|min:1000')
            ->default(10000)
            ->help('Minimum amount that can be borrowed');
        
        $form->number('maximum_loan_multiple', __('Maximum Loan Multiple'))
            ->rules('nullable|integer|min:3|max:30')
            ->default(10)
            ->help('Maximum loan as multiple of member\'s share value (e.g., 10x means if member has 50,000 in shares, max loan is 500,000)');
        
        $form->decimal('late_payment_penalty', __('Late Payment Penalty (%)'))
            ->rules('nullable|numeric|min:0|max:50')
            ->default(5)
            ->help('Penalty percentage for late loan repayments');
        
        // Image
        $form->divider('Media');
        
        $form->image('image', __('Cycle/Project Image'))
            ->move('projects')
            ->uniqueName()
            ->help('Optional image for the cycle/project');

        // Display statistics on edit
        if ($form->isEditing()) {
            $project = Project::find(request()->route()->parameter('project'));
            
            $form->divider('Cycle Statistics');
            
            // Total Members
            $totalMembers = 0;
            if ($project->group_id) {
                $totalMembers = \App\Models\User::where('group_id', $project->group_id)
                    ->where('status', 'Active')
                    ->count();
            }
            
            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Total Active Members</label>
                <div class="col-sm-8">
                    <div class="box box-solid box-success">
                        <div class="box-body">
                            <h3 style="margin: 0;">' . $totalMembers . '</h3>
                        </div>
                    </div>
                </div>
            </div>');
            
            // Total Meetings
            $totalMeetings = \App\Models\VslaMeeting::where('cycle_id', $project->id)->count();
            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Total Meetings Held</label>
                <div class="col-sm-8">
                    <div class="box box-solid box-info">
                        <div class="box-body">
                            <h3 style="margin: 0;">' . $totalMeetings . '</h3>
                        </div>
                    </div>
                </div>
            </div>');
            
            // Total Savings
            $totalSavings = \App\Models\VslaMeeting::where('cycle_id', $project->id)->sum('total_savings');
            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Total Savings</label>
                <div class="col-sm-8">
                    <div class="box box-solid box-success">
                        <div class="box-body">
                            <h3 style="margin: 0; color: green;">UGX ' . number_format($totalSavings, 0) . '</h3>
                        </div>
                    </div>
                </div>
            </div>');
            
            // Active Loans
            $activeLoans = \App\Models\VslaLoan::where('cycle_id', $project->id)
                ->where('status', 'active')
                ->count();
            $totalLoansAmount = \App\Models\VslaLoan::where('cycle_id', $project->id)->sum('amount');
            
            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Active Loans</label>
                <div class="col-sm-8">
                    <div class="box box-solid box-warning">
                        <div class="box-body">
                            <h3 style="margin: 0;">' . $activeLoans . ' loans</h3>
                            <p style="margin: 5px 0 0 0;">Total: UGX ' . number_format($totalLoansAmount, 0) . '</p>
                        </div>
                    </div>
                </div>
            </div>');
            
            // Loan Repayments
            $totalRepayments = \App\Models\VslaLoan::where('cycle_id', $project->id)->sum('amount_paid');
            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Total Loan Repayments</label>
                <div class="col-sm-8">
                    <div class="box box-solid box-success">
                        <div class="box-body">
                            <h3 style="margin: 0; color: green;">UGX ' . number_format($totalRepayments, 0) . '</h3>
                        </div>
                    </div>
                </div>
            </div>');
        }

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
