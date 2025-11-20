<?php

namespace App\Admin\Controllers;

use App\Models\InsuranceSubscriptionPayment;
use App\Models\InsuranceSubscription;
use App\Models\InsuranceProgram;
use App\Models\User;
use App\Models\AccountTransaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class InsuranceSubscriptionPaymentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Insurance Subscription Payments';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new InsuranceSubscriptionPayment());

        // Eager load relationships for performance
        $grid->model()->with(['user', 'insuranceSubscription', 'insuranceProgram'])->orderBy('id', 'desc');

        // ============================
        // GRID COLUMNS
        // ============================
        
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('User'))->display(function () {
            return $this->user ? $this->user->name : 'N/A';
        })->sortable();
        
        $grid->column('insuranceProgram.name', __('Program'))->display(function () {
            return $this->insuranceProgram ? $this->insuranceProgram->name : 'N/A';
        })->sortable();
        
        $grid->column('period_name', __('Period'))->sortable();
        
        $grid->column('due_date', __('Due Date'))->display(function ($due_date) {
            return date('d M Y', strtotime($due_date));
        })->sortable();
        
        $grid->column('amount', __('Amount'))->display(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        })->sortable();
        
        $grid->column('penalty_amount', __('Penalty'))->display(function ($penalty) {
            if ($penalty > 0) {
                return '<span style="color: red;">UGX ' . number_format($penalty, 0) . '</span>';
            }
            return 'UGX 0';
        })->sortable();
        
        $grid->column('total_amount', __('Total'))->display(function ($total) {
            return '<strong>UGX ' . number_format($total, 0) . '</strong>';
        })->sortable();
        
        $grid->column('paid_amount', __('Paid'))->display(function ($paid) {
            if ($paid > 0) {
                return '<span style="color: green;">UGX ' . number_format($paid, 0) . '</span>';
            }
            return 'UGX 0';
        })->sortable();
        
        $grid->column('payment_status', __('Status'))->display(function ($status) {
            $colors = [
                'Paid' => 'success',
                'Pending' => 'warning',
                'Overdue' => 'danger',
                'Partial' => 'info',
                'Waived' => 'primary',
            ];
            $color = $colors[$status] ?? 'default';
            return "<span class='label label-{$color}'>{$status}</span>";
        })->sortable();
        
        $grid->column('payment_date', __('Payment Date'))->display(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : '-';
        })->sortable();
        
        $grid->column('days_overdue', __('Days Overdue'))->display(function ($days) {
            if ($days > 0) {
                return '<span style="color: red; font-weight: bold;">' . $days . ' days</span>';
            }
            return '-';
        });

        // ============================
        // GRID FILTERS
        // ============================
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->like('user.name', 'User Name');
            
            $filter->equal('insurance_program_id', 'Program')->select(
                InsuranceProgram::pluck('name', 'id')
            );
            
            $filter->equal('payment_status', 'Status')->select([
                'Pending' => 'Pending',
                'Paid' => 'Paid',
                'Partial' => 'Partial',
                'Overdue' => 'Overdue',
                'Waived' => 'Waived',
            ]);
            
            $filter->between('due_date', 'Due Date')->date();
            $filter->between('payment_date', 'Payment Date')->date();
            
            $filter->where(function ($query) {
                $query->where('days_overdue', '>', 0);
            }, 'Show Overdue Only')->checkbox('1');
        });

        // ============================
        // GRID ACTIONS
        // ============================
        
        $grid->actions(function ($actions) {
            // Keep default edit and delete actions
            // Add custom quick actions if needed
        });

        // ============================
        // BATCH ACTIONS
        // ============================
        
        $grid->batchActions(function ($batch) {
            $batch->disableDelete(); // Prevent accidental deletion
        });

        // ============================
        // GRID OPTIONS
        // ============================
        
        $grid->disableCreateButton(); // Payments are auto-generated by subscription
        $grid->quickSearch('user.name', 'period_name', 'payment_reference');
        $grid->paginate(50);

        // ============================
        // STATISTICS HEADER
        // ============================
        
        $grid->header(function ($query) {
            $total = InsuranceSubscriptionPayment::sum('amount');
            $paid = InsuranceSubscriptionPayment::where('payment_status', 'Paid')->sum('paid_amount');
            $pending = InsuranceSubscriptionPayment::where('payment_status', 'Pending')->sum('amount');
            $overdue = InsuranceSubscriptionPayment::where('payment_status', 'Overdue')->sum('total_amount');
            $overdueCount = InsuranceSubscriptionPayment::where('payment_status', 'Overdue')->count();
            
            return view('admin.insurance-payment-stats', compact('total', 'paid', 'pending', 'overdue', 'overdueCount'));
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
        $show = new Show(InsuranceSubscriptionPayment::findOrFail($id));

        // ============================
        // PAYMENT INFORMATION
        // ============================
        
        $show->panel()->title('Payment Details');
        
        $show->field('id', __('Payment ID'));
        
        $show->field('user.name', __('User'));
        $show->field('user.phone_number', __('User Phone'));
        
        $show->field('insuranceSubscription.policy_number', __('Policy Number'));
        $show->field('insuranceProgram.name', __('Insurance Program'));
        
        $show->divider();
        
        // ============================
        // PERIOD INFORMATION
        // ============================
        
        $show->panel()->title('Period Information');
        
        $show->field('period_name', __('Period Name'));
        $show->field('billing_frequency', __('Billing Frequency'));
        $show->field('period_start_date', __('Period Start'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });
        $show->field('period_end_date', __('Period End'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });
        
        $show->divider();
        
        // ============================
        // FINANCIAL INFORMATION
        // ============================
        
        $show->panel()->title('Financial Details');
        
        $show->field('amount', __('Base Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 2);
        });
        $show->field('penalty_amount', __('Penalty Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 2);
        });
        $show->field('total_amount', __('Total Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 2);
        });
        $show->field('paid_amount', __('Paid Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 2);
        });
        
        $show->divider();
        
        // ============================
        // STATUS INFORMATION
        // ============================
        
        $show->panel()->title('Status Information');
        
        $show->field('payment_status', __('Payment Status'))->using([
            'Pending' => 'Pending',
            'Paid' => 'Paid',
            'Partial' => 'Partial',
            'Overdue' => 'Overdue',
            'Waived' => 'Waived',
        ])->label([
            'Paid' => 'success',
            'Pending' => 'warning',
            'Overdue' => 'danger',
            'Partial' => 'info',
            'Waived' => 'primary',
        ]);
        
        $show->field('due_date', __('Due Date'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });
        $show->field('payment_date', __('Payment Date'))->as(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : 'Not paid yet';
        });
        $show->field('overdue_date', __('Overdue Date'))->as(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : '-';
        });
        $show->field('days_overdue', __('Days Overdue'));
        
        $show->divider();
        
        // ============================
        // PAYMENT METHOD & REFERENCE
        // ============================
        
        $show->panel()->title('Payment Method');
        
        $show->field('payment_method', __('Payment Method'));
        $show->field('payment_reference', __('Payment Reference'));
        $show->field('transaction_id', __('Transaction ID'));
        
        $show->divider();
        
        // ============================
        // ADDITIONAL INFORMATION
        // ============================
        
        $show->field('description', __('Description'));
        $show->field('notes', __('Notes'));
        
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
        $form = new Form(new InsuranceSubscriptionPayment());

        $form->divider('Update Payment Status');

        // ============================
        // READ-ONLY INFORMATION
        // ============================
        
        $form->display('id', __('Payment ID'));
        
        $form->display('user.name', __('User'))->with(function ($value) {
            return $this->user ? $this->user->name . ' (' . $this->user->phone_number . ')' : 'N/A';
        });
        
        $form->display('insuranceProgram.name', __('Program'));
        $form->display('period_name', __('Period'));
        $form->display('due_date', __('Due Date'));
        
        $form->display('amount', __('Base Amount'))->with(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });
        
        $form->display('penalty_amount', __('Penalty Amount'))->with(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });
        
        $form->display('total_amount', __('Total Amount Due'))->with(function ($value) {
            return 'UGX ' . number_format($value, 2);
        });

        $form->divider('Update Payment');

        // ============================
        // EDITABLE FIELDS
        // ============================
        
        $form->select('payment_status', __('Payment Status'))
            ->options([
                'Pending' => 'Pending - Not yet paid',
                'Partial' => 'Partial - Partially paid',
                'Paid' => 'Paid - Fully paid',
                'Overdue' => 'Overdue - Past due date',
                'Waived' => 'Waived - Payment waived',
            ])
            ->rules('required')
            ->help('Change status to trigger appropriate actions');
        
        $form->decimal('paid_amount', __('Paid Amount (UGX)'))
            ->default(0)
            ->rules('required|numeric|min:0')
            ->help('Enter the amount paid by user');
        
        $form->date('payment_date', __('Payment Date'))
            ->help('Date when payment was received (auto-set if status is Paid)');
        
        $form->select('payment_method', __('Payment Method'))
            ->options([
                'Cash' => 'Cash',
                'Mobile Money' => 'Mobile Money',
                'Bank Transfer' => 'Bank Transfer',
                'Cheque' => 'Cheque',
                'Card' => 'Card',
                'Other' => 'Other',
            ])
            ->help('How was the payment made?');
        
        $form->text('payment_reference', __('Payment Reference'))
            ->help('Transaction reference number or receipt number');
        
        $form->textarea('notes', __('Notes'))
            ->rows(3)
            ->help('Any additional notes about this payment');

        $form->hidden('updated_by')->default(auth('admin')->user()->id ?? 1);

        // ============================
        // SAVING EVENT - TRIGGER ACTIONS
        // ============================
        
        $form->saving(function (Form $form) {
            $oldStatus = $form->model()->payment_status;
            $newStatus = $form->payment_status;
            
            // If marking as Paid
            if ($newStatus == 'Paid' && $oldStatus != 'Paid') {
                // Ensure paid amount is set
                if (empty($form->paid_amount) || $form->paid_amount <= 0) {
                    $form->paid_amount = $form->model()->total_amount;
                }
                
                // Auto-set payment date
                if (empty($form->payment_date)) {
                    $form->payment_date = Carbon::now()->format('Y-m-d');
                }
            }
            
            // If marking as Overdue, calculate penalty
            if ($newStatus == 'Overdue' && $oldStatus != 'Overdue') {
                $payment = $form->model();
                $payment->calculatePenalty();
                $form->penalty_amount = $payment->penalty_amount;
                $form->total_amount = $payment->total_amount;
            }
            
            // If marking as Waived, zero out amounts
            if ($newStatus == 'Waived') {
                $form->paid_amount = 0;
                $form->penalty_amount = 0;
            }
        });

        // ============================
        // SAVED EVENT - POST-SAVE ACTIONS
        // ============================
        
        $form->saved(function (Form $form) {
            $payment = $form->model();
            $oldStatus = $form->model()->getOriginal('payment_status');
            $newStatus = $payment->payment_status;
            
            // ============================
            // ACTION 1: CREATE ACCOUNT TRANSACTION
            // ============================
            if ($newStatus == 'Paid' && $oldStatus != 'Paid') {
                // Create account transaction for this payment
                AccountTransaction::create([
                    'user_id' => $payment->user_id,
                    'amount' => $payment->paid_amount,
                    'transaction_date' => $payment->payment_date ?? now(),
                    'description' => 'Insurance Premium Payment: ' . $payment->period_name . ' (' . $payment->insuranceProgram->name . ')',
                    'source' => 'withdrawal', // Money withdrawn from user account
                    'insurance_subscription_id' => $payment->insurance_subscription_id,
                    'created_by_id' => auth('admin')->user()->id ?? 1,
                ]);
                
                admin_toastr('Payment marked as paid. Account transaction created.', 'success');
            }
            
            // ============================
            // ACTION 2: UPDATE SUBSCRIPTION BALANCES
            // ============================
            $subscription = InsuranceSubscription::find($payment->insurance_subscription_id);
            if ($subscription) {
                $subscription->updateBalances();
                
                // Update payment status
                $totalPaid = $subscription->total_paid;
                $totalExpected = $subscription->total_expected;
                
                if ($totalPaid >= $totalExpected) {
                    $subscription->payment_status = 'Current';
                } elseif ($totalPaid > 0) {
                    $subscription->payment_status = 'Late';
                } else {
                    // Check if any payment is overdue
                    $hasOverdue = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                        ->where('payment_status', 'Overdue')
                        ->exists();
                    
                    if ($hasOverdue) {
                        $subscription->payment_status = 'Late';
                    }
                }
                
                $subscription->save();
            }
            
            // ============================
            // ACTION 3: UPDATE COVERAGE STATUS
            // ============================
            if ($newStatus == 'Paid' && $subscription) {
                // If subscription was suspended due to non-payment, reactivate it
                if ($subscription->coverage_status == 'Suspended') {
                    // Check if all overdue payments are now paid
                    $hasOverdue = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                        ->where('payment_status', 'Overdue')
                        ->exists();
                    
                    if (!$hasOverdue) {
                        $subscription->coverage_status = 'Active';
                        $subscription->save();
                        
                        admin_toastr('Coverage reactivated - all overdue payments cleared!', 'success');
                    }
                }
            }
            
            // ============================
            // ACTION 4: SUSPEND COVERAGE IF OVERDUE TOO LONG
            // ============================
            if ($newStatus == 'Overdue' && $payment->days_overdue > 30 && $subscription) {
                if ($subscription->coverage_status == 'Active') {
                    $subscription->coverage_status = 'Suspended';
                    $subscription->suspended_date = Carbon::now()->format('Y-m-d');
                    $subscription->suspension_reason = 'Payment overdue for more than 30 days';
                    $subscription->save();
                    
                    admin_toastr('Warning: Subscription coverage has been suspended due to overdue payment.', 'warning');
                }
            }
            
            // ============================
            // ACTION 5: UPDATE NEXT BILLING DATE
            // ============================
            if ($newStatus == 'Paid' && $subscription) {
                // Find next pending payment
                $nextPayment = InsuranceSubscriptionPayment::where('insurance_subscription_id', $subscription->id)
                    ->whereIn('payment_status', ['Pending', 'Overdue'])
                    ->orderBy('due_date', 'asc')
                    ->first();
                
                if ($nextPayment) {
                    $subscription->next_billing_date = $nextPayment->due_date;
                    $subscription->save();
                }
            }
        });

        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        $form->disableViewCheck();
        $form->disableReset();

        return $form;
    }
}
