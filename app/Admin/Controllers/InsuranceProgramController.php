<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceProgram;
use App\Admin\Helpers\RoleBasedDashboard;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class InsuranceProgramController extends AdminController
{
    use RoleBasedDashboard;
    
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Insurance Programs';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new InsuranceProgram());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        
        // Managers have read-only access to insurance programs
        if (!$this->isAdmin()) {
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
            });
            $grid->disableBatchActions();
        }
        
        $grid->quickSearch('name', 'description')->placeholder('Search by program name or description');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Program Name');
            $filter->like('description', 'Description');
            $filter->equal('billing_frequency', 'Billing Frequency')->select([
                'Weekly' => 'Weekly',
                'Monthly' => 'Monthly',
                'Quarterly' => 'Quarterly',
                'Annually' => 'Annually',
            ]);
            $filter->equal('status', 'Status')->select([
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                'Suspended' => 'Suspended',
            ]);
            $filter->between('premium_amount', 'Premium Amount');
            $filter->between('coverage_amount', 'Coverage Amount');
            $filter->between('created_at', 'Created Date')->date();
        });

        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60);
        
        $grid->column('name', __('Program Name'))
            ->sortable()
            ->editable()
            ->width(200);
        
        $grid->column('premium_amount', __('Premium'))
            ->display(function ($amount) {
                return '<span style="color: #05179F; font-weight: 600;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->width(120);
        
        $grid->column('billing_frequency', __('Billing'))
            ->label([
                'Weekly' => 'info',
                'Monthly' => 'primary',
                'Quarterly' => 'warning',
                'Annually' => 'success',
            ])
            ->sortable()
            ->width(100);
        
        $grid->column('coverage_amount', __('Coverage'))
            ->display(function ($amount) {
                return '<span style="color: #28a745; font-weight: 600;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->width(130);
        
        $grid->column('duration_months', __('Duration'))
            ->display(function ($months) {
                return '<span style="color: #6c757d;">' . $months . ' months</span>';
            })
            ->sortable()
            ->width(100);
        
        $grid->column('status', __('Status'))
            ->label([
                'Active' => 'success',
                'Inactive' => 'default',
                'Suspended' => 'danger',
            ])
            ->editable('select', [
                'Active' => 'Active',
                'Inactive' => 'Inactive',
                'Suspended' => 'Suspended',
            ])
            ->sortable()
            ->width(90);
        
        $grid->column('total_subscribers', __('Subscribers'))
            ->display(function ($count) {
                return '<span style="background: #05179F; color: white; padding: 2px 8px; border-radius: 3px;">' . number_format($count) . '</span>';
            })
            ->sortable()
            ->width(100);
        
        $grid->column('total_premiums_collected', __('Collected'))
            ->display(function ($amount) {
                return '<span style="color: #28a745;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->width(130);
        
        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('M d, Y', strtotime($date));
            })
            ->sortable()
            ->width(100);

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
        $show = new Show(InsuranceProgram::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Program Name'));
        $show->field('description', __('Description'));
        
        $show->field('premium_amount', __('Premium'))->as(function ($premium) {
            return 'UGX ' . number_format($premium, 0);
        });
        
        $show->field('billing_frequency', __('Billing Frequency'));
        $show->field('billing_day', __('Billing Day'));
        
        $show->field('coverage_amount', __('Coverage Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('duration_months', __('Duration (months)'));
        $show->field('grace_period_days', __('Grace Period (days)'));
        $show->field('late_payment_penalty', __('Late Payment Penalty'));
        $show->field('penalty_type', __('Penalty Type'));
        $show->field('min_age', __('Minimum Age'));
        $show->field('max_age', __('Maximum Age'));
        $show->field('requirements', __('Requirements'))->unescape();
        $show->field('benefits', __('Benefits'))->unescape();
        $show->field('status', __('Status'));
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
        $form = new Form(new InsuranceProgram());

        // SECTION 1: Basic Program Information
        $form->divider('1. Basic Program Information');
        
        $form->text('name', __('Program Name'))
            ->rules('required|max:255')
            ->required()
            ->help('Enter the name of the insurance program');
        
        $form->textarea('description', __('Program Description'))
            ->rules('required')
            ->rows(4)
            ->required()
            ->help('Provide a detailed description of the insurance program');
        
        // SECTION 2: Financial Details
        $form->divider('2. Financial Details');
        
        $form->decimal('premium_amount', __('Premium Amount (UGX)'))
            ->rules('required|numeric|min:0')
            ->required()
            ->help('Amount the subscriber pays per billing cycle');
        
        $form->decimal('coverage_amount', __('Coverage Amount (UGX)'))
            ->rules('required|numeric|min:0')
            ->required()
            ->help('Maximum amount covered by this insurance program');
        
        // SECTION 3: Billing Configuration
        $form->divider('3. Billing Configuration');
        
        $form->select('billing_frequency', __('Billing Frequency'))
            ->options([
                'Weekly' => 'Weekly',
                'Monthly' => 'Monthly',
                'Quarterly' => 'Quarterly',
                'Annually' => 'Annually',
            ])
            ->default('Monthly')
            ->rules('required')
            ->required()
            ->help('How often subscribers are billed');
        
        $form->number('billing_day', __('Billing Day'))
            ->rules('nullable|integer|min:1|max:31')
            ->default(1)
            ->help('Day of month for billing (1-31). For weekly, use day of week (1-7)');
        
        $form->number('duration_months', __('Program Duration (months)'))
            ->rules('required|integer|min:1')
            ->default(12)
            ->required()
            ->help('Total duration of the insurance coverage in months');
        
        // SECTION 4: Penalties & Grace Period
        $form->divider('4. Penalties & Grace Period');
        
        $form->number('grace_period_days', __('Grace Period (days)'))
            ->rules('nullable|integer|min:0')
            ->default(7)
            ->help('Number of days after missed payment before penalties apply');
        
        $form->decimal('late_payment_penalty', __('Late Payment Penalty'))
            ->rules('nullable|numeric|min:0')
            ->default(0)
            ->help('Amount or percentage charged for late payments');
        
        $form->select('penalty_type', __('Penalty Type'))
            ->options([
                'Fixed' => 'Fixed Amount (UGX)',
                'Percentage' => 'Percentage (%)',
            ])
            ->default('Fixed')
            ->help('Whether penalty is a fixed amount or percentage of premium');
        
        // SECTION 5: Age Requirements
        $form->divider('5. Age Requirements');
        
        $form->number('min_age', __('Minimum Age'))
            ->rules('nullable|integer|min:0')
            ->default(18)
            ->help('Minimum age required to subscribe');
        
        $form->number('max_age', __('Maximum Age'))
            ->rules('nullable|integer|min:0')
            ->default(65)
            ->help('Maximum age allowed to subscribe');
        
        // SECTION 6: Program Requirements & Benefits
        $form->divider('6. Program Requirements & Benefits');
        
        $form->textarea('requirements', __('Program Requirements'))
            ->rows(3)
            ->help('Enter program requirements (one per line)');
        
        $form->textarea('benefits', __('Program Benefits'))
            ->rows(5)
            ->help('Enter program benefits (one per line)');
        
        // SECTION 7: Terms & Conditions
        $form->divider('7. Terms & Conditions');
        
        $form->textarea('terms_and_conditions', __('Terms and Conditions'))
            ->rows(5)
            ->help('Enter the full terms and conditions of this insurance program');
        
        // SECTION 8: Branding & Display
        $form->divider('8. Branding & Display');
        
        $form->image('icon', __('Program Icon'))
            ->move('insurance/icons')
            ->uniqueName()
            ->help('Upload an icon for this program (recommended: 512x512px)');
        
        $form->color('color', __('Brand Color'))
            ->default('#05179F')
            ->help('Choose a color to represent this program in the app');
        
        // SECTION 9: Program Schedule
        $form->divider('9. Program Schedule');
        
        $form->date('start_date', __('Start Date'))
            ->help('Date when the program becomes available');
        
        $form->date('end_date', __('End Date'))
            ->help('Date when the program is no longer available (optional)');
        
        // SECTION 10: Program Status
        $form->divider('10. Program Status');
        
        $form->select('status', __('Status'))
            ->options([
                'Active' => 'Active - Available for enrollment',
                'Inactive' => 'Inactive - Not available for enrollment',
                'Suspended' => 'Suspended - Temporarily disabled',
            ])
            ->default('Active')
            ->rules('required')
            ->required()
            ->help('Current status of the insurance program');
        
        $form->hidden('created_by')->default(auth()->id());
        $form->hidden('updated_by')->default(auth()->id());

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
