<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceProgram;
use App\Models\User;
use App\Models\AccountTransaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class InsuranceSubscriptionController extends AdminController
{
    protected $title = 'Insurance Subscriptions';

    protected function grid()
    {
        $grid = new Grid(new InsuranceSubscription());
        
        $grid->model()->with(['user', 'insuranceProgram'])->orderBy('id', 'desc');
        $grid->disableExport();
        
        $grid->quickSearch('policy_number')->placeholder('Search by policy number');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_id', 'User')
                ->select(User::where('user_type', 'insurance_user')->pluck('name', 'id'));
            $filter->equal('insurance_program_id', 'Program')
                ->select(InsuranceProgram::pluck('name', 'id'));
            $filter->equal('payment_status', 'Payment Status')->select([
                'paid' => 'Paid',
                'pending' => 'Pending',
                'overdue' => 'Overdue',
            ]);
            $filter->equal('coverage_status', 'Coverage Status')->select([
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
            ]);
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
                'expired' => 'Expired',
            ]);
            $filter->between('start_date', 'Start Date')->date();
            $filter->between('next_billing_date', 'Next Billing Date')->date();
        });

        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60);
        
        $grid->column('policy_number', __('Policy #'))
            ->display(function ($policy) {
                return '<span style="color: #05179F; font-weight: 600;">' . $policy . '</span>';
            })
            ->sortable()
            ->width(140);
        
        $grid->column('user.name', __('User'))
            ->sortable()
            ->width(150);
        
        $grid->column('insuranceProgram.name', __('Program'))
            ->sortable()
            ->width(180);
        
        $grid->column('premium_amount', __('Premium'))
            ->display(function ($amount) {
                return '<span style="color: #05179F;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('total_balance', __('Balance'))
            ->display(function ($balance) {
                if ($balance > 0) {
                    return '<span style="color: #dc3545; font-weight: 600;">UGX ' . number_format($balance, 0) . '</span>';
                } else {
                    return '<span style="color: #28a745;">UGX 0</span>';
                }
            })
            ->sortable()
            ->width(120);
        
        $grid->column('payment_status', __('Payment'))
            ->label([
                'paid' => 'success',
                'pending' => 'warning',
                'overdue' => 'danger',
            ])
            ->sortable()
            ->width(90);
        
        $grid->column('coverage_status', __('Coverage'))
            ->label([
                'active' => 'success',
                'suspended' => 'warning',
                'cancelled' => 'danger',
            ])
            ->sortable()
            ->width(95);
        
        $grid->column('status', __('Status'))
            ->label([
                'active' => 'success',
                'suspended' => 'warning',
                'cancelled' => 'danger',
                'expired' => 'default',
            ])
            ->editable('select', [
                'active' => 'Active',
                'suspended' => 'Suspended',
                'cancelled' => 'Cancelled',
                'expired' => 'Expired',
            ])
            ->sortable()
            ->width(90);
        
        $grid->column('next_billing_date', __('Next Billing'))
            ->display(function ($date) {
                if (!$date) return '<span style="color: #6c757d;">-</span>';
                $timestamp = strtotime($date);
                $today = strtotime('today');
                $color = $timestamp < $today ? '#dc3545' : '#28a745';
                return '<span style="color: ' . $color . ';">' . date('M d, Y', $timestamp) . '</span>';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return $date ? date('M d, Y', strtotime($date)) : '-';
            })
            ->sortable()
            ->width(100);

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(InsuranceSubscription::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('policy_number', __('Policy Number'));
        $show->field('user.name', __('User'));
        $show->field('insuranceProgram.name', __('Program'));
        $show->field('premium_amount', __('Premium'))->as(function ($premium) {
            return 'UGX ' . number_format($premium, 0);
        });
        $show->field('total_expected', __('Total Expected'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('total_paid', __('Total Paid'))->as(function ($paid) {
            return 'UGX ' . number_format($paid, 0);
        });
        $show->field('total_balance', __('Balance'))->as(function ($balance) {
            return 'UGX ' . number_format($balance, 0);
        });
        $show->field('payment_status', __('Payment Status'));
        $show->field('coverage_status', __('Coverage Status'));
        $show->field('status', __('Status'));
        $show->field('next_billing_date', __('Next Billing Date'));
        $show->field('start_date', __('Start Date'));
        $show->field('end_date', __('End Date'));
        $show->field('beneficiaries', __('Beneficiaries'));
        $show->field('notes', __('Notes'));
        $show->field('created_at', __('Created At'));
        return $show;
    }

    protected function form()
    {
        $form = new Form(new InsuranceSubscription());
        
        $form->divider('Create Insurance Subscription');
        
        // User Selection - Show all users (Customers, Vendors, etc.)
        $form->select('user_id', __('Select User'))
            ->options(function () {
                return User::whereNotNull('name')
                    ->where('name', '!=', '')
                    ->orderBy('name', 'asc')
                    ->pluck('name', 'id');
            })
            ->rules('required')
            ->required();
        
        // Insurance Program Selection
        $form->select('insurance_program_id', __('Insurance Program'))
            ->options(function () {
                return InsuranceProgram::where('status', 'Active')
                    ->orderBy('name', 'asc')
                    ->get()
                    ->mapWithKeys(function ($program) {
                        return [$program->id => $program->name . ' (UGX ' . number_format($program->premium_amount, 0) . '/' . $program->billing_frequency . ')'];
                    });
            })
            ->rules('required')
            ->required();
        
        // Start Date
        $form->date('start_date', __('Start Date'))
            ->default(date('Y-m-d'))
            ->rules('required')
            ->required();
        
        // Beneficiaries (Optional)
        $form->textarea('beneficiaries', __('Beneficiaries (Optional)'))
            ->rows(3)
            ->placeholder('Enter beneficiary details: Name, Relationship, Contact');
        
        // Notes (Optional)
        $form->textarea('notes', __('Notes (Optional)'))
            ->rows(2)
            ->placeholder('Any additional notes');
        
        // Hidden fields with calculated defaults
        $form->hidden('end_date')->default(function ($form) {
            $programId = request()->input('insurance_program_id');
            $startDate = request()->input('start_date');
            if ($programId && $startDate) {
                $program = InsuranceProgram::find($programId);
                if ($program) {
                    return Carbon::parse($startDate)->addMonths($program->duration_months)->format('Y-m-d');
                }
            }
            return Carbon::now()->addMonths(12)->format('Y-m-d');
        });
        
        $form->hidden('coverage_start_date')->default(function ($form) {
            return request()->input('start_date') ?: date('Y-m-d');
        });
        
        $form->hidden('coverage_end_date')->default(function ($form) {
            $programId = request()->input('insurance_program_id');
            $startDate = request()->input('start_date');
            if ($programId && $startDate) {
                $program = InsuranceProgram::find($programId);
                if ($program) {
                    return Carbon::parse($startDate)->addMonths($program->duration_months)->format('Y-m-d');
                }
            }
            return Carbon::now()->addMonths(12)->format('Y-m-d');
        });
        
        $form->hidden('premium_amount')->default(function ($form) {
            $programId = request()->input('insurance_program_id');
            if ($programId) {
                $program = InsuranceProgram::find($programId);
                if ($program) {
                    return $program->premium_amount;
                }
            }
            return 0;
        });
        
        $form->hidden('next_billing_date')->default(function ($form) {
            return request()->input('start_date') ?: date('Y-m-d');
        });
        
        $form->hidden('status')->default('Active');
        $form->hidden('payment_status')->default('Current');
        $form->hidden('coverage_status')->default('Active');
        $form->hidden('total_expected')->default(0);
        $form->hidden('total_paid')->default(0);
        $form->hidden('total_balance')->default(0);
        $form->hidden('payments_completed')->default(0);
        $form->hidden('payments_pending')->default(0);
        $form->hidden('prepared')->default('No');
        $form->hidden('created_by')->default(auth('admin')->user()->id ?? 1);
        $form->hidden('updated_by')->default(auth('admin')->user()->id ?? 1);

        // Saving event - update calculated totals
        $form->saving(function (Form $form) {
            if ($form->insurance_program_id && $form->start_date) {
                $program = InsuranceProgram::find($form->insurance_program_id);
                if ($program) {
                    // Calculate total expected based on billing frequency
                    $billingCycles = 0;
                    switch ($program->billing_frequency) {
                        case 'Weekly':
                            $billingCycles = ceil($program->duration_months * 4.33);
                            break;
                        case 'Monthly':
                            $billingCycles = $program->duration_months;
                            break;
                        case 'Quarterly':
                            $billingCycles = ceil($program->duration_months / 3);
                            break;
                        case 'Annually':
                            $billingCycles = ceil($program->duration_months / 12);
                            break;
                        default:
                            $billingCycles = $program->duration_months;
                    }
                    $form->total_expected = $program->premium_amount * $billingCycles;
                    $form->payments_pending = $billingCycles;
                    $form->total_balance = $form->total_expected;
                }
            }
        });
        
        // After save - create initial transaction (only on creation, not update)
        $form->saved(function (Form $form) {
            $subscriptionId = $form->model()->id;
            $subscription = InsuranceSubscription::with(['user', 'insuranceProgram'])->find($subscriptionId);
            
            // Only create transaction if this is a new subscription (not an update)
            if ($subscription && $subscription->insuranceProgram && $form->isCreating()) {
                // Check if transaction already exists by description pattern
                $existingTransaction = AccountTransaction::where('user_id', $subscription->user_id)
                    ->where('description', 'LIKE', '%Policy: ' . $subscription->policy_number . '%')
                    ->first();
                
                if (!$existingTransaction) {
                    AccountTransaction::create([
                        'user_id' => $subscription->user_id,
                        'amount' => 0, // Initial subscription record, no money yet
                        'transaction_date' => now(),
                        'description' => 'Insurance Subscription Created: ' . $subscription->insuranceProgram->name . ' (Policy: ' . $subscription->policy_number . ')',
                        'source' => 'deposit', // Valid ENUM: 'disbursement', 'withdrawal', 'deposit'
                        'created_by_id' => auth('admin')->user()->id ?? 1,
                    ]);
                }
                
                admin_success('Success', 'Subscription created successfully with policy number: ' . $subscription->policy_number);
            }
        });

        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        $form->disableViewCheck();
        $form->disableReset();
        
        return $form;
    }
}
