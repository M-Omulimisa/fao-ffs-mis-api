<?php

namespace App\Admin\Controllers;

use App\Models\MembershipPayment;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MembershipPaymentController extends AdminController
{
    protected $title = 'Membership Payments';

    protected function grid()
    {
        $grid = new Grid(new MembershipPayment());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        $grid->disableBatchActions();
        
        $grid->quickSearch('payment_reference')->placeholder('Search by payment reference');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            // User filter - load all users
            $users = User::orderBy('name', 'asc')->pluck('name', 'id')->toArray();
            $filter->equal('user_id', 'User')->select($users);
            
            $filter->equal('status', 'Status')->select([
                'PENDING' => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED' => 'Failed',
                'REFUNDED' => 'Refunded',
            ]);
            
            $filter->equal('payment_method', 'Payment Method')->select([
                'CASH' => 'Cash',
                'MOBILE_MONEY' => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL' => 'Pesapal',
            ]);
            
            $filter->equal('membership_type', 'Membership Type')->select([
                'LIFE' => 'Life',
                'ANNUAL' => 'Annual',
                'MONTHLY' => 'Monthly',
            ]);
            
            $filter->between('payment_date', 'Payment Date')->date();
            $filter->between('created_at', 'Created Date')->date();
        });

        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60);
        
        $grid->column('payment_reference', __('Reference'))
            ->display(function ($ref) {
                return '<span style="color: #05179F; font-weight: 600;">' . $ref . '</span>';
            })
            ->sortable()
            ->width(180);
        
        $grid->column('user.name', __('User'))
            ->sortable()
            ->width(150);
        
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return '<span style="color: #05179F; font-weight: 600;">UGX ' . number_format($amount, 0) . '</span>';
            })
            ->sortable()
            ->width(120);
        
        $grid->column('membership_type', __('Type'))
            ->label([
                'LIFE' => 'success',
                'ANNUAL' => 'warning',
                'MONTHLY' => 'info',
            ])
            ->sortable()
            ->width(90);
        
        $grid->column('payment_method', __('Method'))
            ->label([
                'CASH' => 'default',
                'MOBILE_MONEY' => 'primary',
                'BANK_TRANSFER' => 'info',
                'PESAPAL' => 'success',
            ])
            ->sortable()
            ->width(120);
        
        $grid->column('status', __('Status'))
            ->label([
                'PENDING' => 'warning',
                'CONFIRMED' => 'success',
                'FAILED' => 'danger',
                'REFUNDED' => 'default',
            ]) 
            ->sortable()
            ->width(100);
        
        $grid->column('registeredBy.name', __('Registered By'))
            ->display(function () {
                if ($this->registered_by_id) {
                    $user = \App\Models\User::find($this->registered_by_id);
                    return $user ? $user->name : '-';
                }
                return '-';
            })
            ->sortable()
            ->width(130);
        
        $grid->column('payment_date', __('Payment Date'))
            ->display(function ($date) {
                return $date ? date('M d, Y', strtotime($date)) : '-';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('confirmed_at', __('Confirmed'))
            ->display(function ($date) {
                if (!$date) return '<span style="color: #6c757d;">-</span>';
                return '<span style="color: #28a745;">' . date('M d, Y', strtotime($date)) . '</span>';
            })
            ->sortable()
            ->width(110);
        
        $grid->column('expiry_date', __('Expiry'))
            ->display(function ($date) {
                if (!$date) return '<span style="color: #28a745;">NEVER</span>';
                $timestamp = strtotime($date);
                $today = strtotime('today');
                $color = $timestamp < $today ? '#dc3545' : '#28a745';
                return '<span style="color: ' . $color . ';">' . date('M d, Y', $timestamp) . '</span>';
            })
            ->sortable()
            ->width(110);

        // Disable delete action
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            
            // Add custom confirm button if pending
            $row = $actions->row;
            if ($row->status == 'PENDING') {
                $actions->prepend('<a href="' . route('admin.membership-payments.confirm', $row->id) . '" class="btn btn-xs btn-success" onclick="return confirm(\'Confirm this membership payment?\')"><i class="fa fa-check"></i> Confirm</a>');
            }
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(MembershipPayment::findOrFail($id));
        
        $show->field('id', __('ID'));
        $show->field('payment_reference', __('Payment Reference'));
        $show->field('user.name', __('User'));
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('membership_type', __('Membership Type'));
        $show->field('status', __('Status'));
        $show->field('payment_method', __('Payment Method'));
        $show->field('payment_phone_number', __('Payment Phone'));
        $show->field('payment_account_number', __('Payment Account'));
        $show->field('payment_date', __('Payment Date'));
        $show->field('confirmed_at', __('Confirmed At'));
        $show->field('expiry_date', __('Expiry Date'));
        $show->field('description', __('Description'));
        $show->field('notes', __('Notes'));
        $show->field('receipt_photo', __('Receipt Photo'))->image();
        $show->field('confirmation_code', __('Confirmation Code'));
        $show->field('pesapal_order_tracking_id', __('Pesapal Order ID'));
        $show->field('pesapal_merchant_reference', __('Pesapal Merchant Ref'));
        $show->field('universal_payment_id', __('Universal Payment ID'));
        $show->field('created_by', __('Created By'))->as(function ($id) {
            $user = User::find($id);
            return $user ? $user->name : '-';
        });
        $show->field('confirmed_by', __('Confirmed By'))->as(function ($id) {
            $user = User::find($id);
            return $user ? $user->name : '-';
        });
        $show->field('registered_by_id', __('Registered By'))->as(function ($id) {
            $user = User::find($id);
            return $user ? $user->name : '-';
        });
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        
        return $show;
    }

    protected function form()
    {
        $form = new Form(new MembershipPayment());
        
        // Simple form with defaults
        $form->divider('Membership Payment Details');
        
        // User selection - fetch all users and display name, phone, address
        $users = User::orderBy('name', 'asc')
            ->get()
            ->mapWithKeys(function ($user) {
                $phone = $user->phone_number ?? $user->phone_number_2 ?? 'No phone';
                $address = $user->address ?? 'No address';
                $label = $user->name . ' | ' . $phone . ' | ' . $address;
                return [$user->id => $label];
            })
            ->toArray();
        
        $form->select('user_id', __('User *'))
            ->options($users)
            ->rules('required')
            ->required()
            ->help('Select the user for this membership payment');
        
        // Membership Type with LIFE as default
        $form->select('membership_type', __('Membership Type *'))
            ->options([
                'LIFE' => 'Life (Never Expires)',
                'ANNUAL' => 'Annual (1 Year)',
                'MONTHLY' => 'Monthly (1 Month)',
            ])
            ->default('LIFE')
            ->rules('required')
            ->required()
            ->help('Default is LIFE membership');
        
        // Amount with default
        $form->currency('amount', __('Amount (UGX) *'))
            ->symbol('UGX')
            ->default(MembershipPayment::DEFAULT_AMOUNT)
            ->rules('required|numeric|min:0')
            ->required();
        
        // Payment Method with CASH as default
        $form->select('payment_method', __('Payment Method *'))
            ->options([
                'CASH' => 'Cash',
                'MOBILE_MONEY' => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL' => 'Pesapal (Online)',
            ])
            ->default('CASH')
            ->rules('required')
            ->required();
        
        // Status with CONFIRMED as default for quick setup
        $form->select('status', __('Status *'))
            ->options([
                'PENDING' => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED' => 'Failed',
                'REFUNDED' => 'Refunded',
            ])
            ->default('CONFIRMED')
            ->rules('required')
            ->required()
            ->help('Default is CONFIRMED - payment will be activated immediately');
        
        // Payment Date with today as default
        $form->date('payment_date', __('Payment Date *'))
            ->default(date('Y-m-d'))
            ->rules('required')
            ->required();
        
        // Optional fields
        $form->divider('Optional Information');
        
        $form->text('payment_phone_number', __('Payment Phone'))
            ->help('Mobile money number (optional)');
        
        $form->text('payment_account_number', __('Account/Transaction Ref'))
            ->help('Bank account or transaction reference (optional)');
        
        $form->text('confirmation_code', __('Confirmation Code'))
            ->help('Payment confirmation code (optional)');
        
        $form->textarea('notes', __('Notes'))
            ->rows(2)
            ->help('Any additional notes (optional)');
        
        $form->image('receipt_photo', __('Receipt Photo'))
            ->move('membership/receipts')
            ->uniqueName()
            ->help('Upload payment receipt (optional)');
        
        // Hidden fields
        $form->hidden('payment_reference');
        $form->hidden('expiry_date');
        $form->hidden('confirmed_at');
        $form->hidden('created_by')->default(auth()->id());
        $form->hidden('updated_by')->default(auth()->id());
        $form->hidden('confirmed_by');
        $form->hidden('registered_by_id')->default(auth()->id());

        // Disable unnecessary buttons
        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        
        // Auto-generate reference and handle confirmation
        $form->saving(function (Form $form) {
            // Generate payment reference if creating new
            if ($form->isCreating()) {
                $userId = $form->user_id ?? 0;
                $form->payment_reference = 'MEM-' . strtoupper(uniqid()) . '-' . $userId;
            }
            
            $form->updated_by = auth()->id();
            
            // If status is CONFIRMED, set confirmation details
            if ($form->status == 'CONFIRMED') {
                if (!$form->confirmed_at) {
                    $form->confirmed_at = now();
                }
                if (!$form->confirmed_by) {
                    $form->confirmed_by = auth()->id();
                }
                
                // Calculate expiry date based on membership type
                if ($form->membership_type == 'ANNUAL') {
                    $form->expiry_date = now()->addYear()->format('Y-m-d');
                } elseif ($form->membership_type == 'MONTHLY') {
                    $form->expiry_date = now()->addMonth()->format('Y-m-d');
                } else {
                    // LIFE membership - no expiry
                    $form->expiry_date = null;
                }
            }
        });
        
        // After save, confirm the payment to update user record
        $form->saved(function (Form $form) {
            $payment = $form->model();
            
            // If confirmed, update user record
            if ($payment->status == 'CONFIRMED' && $payment->user_id) {
                try {
                    $user = User::find($payment->user_id);
                    if ($user) {
                        $user->is_membership_paid = true;
                        $user->membership_paid_at = $payment->confirmed_at;
                        $user->membership_amount = $payment->amount;
                        $user->membership_payment_id = $payment->id;
                        $user->membership_type = $payment->membership_type;
                        $user->membership_expiry_date = $payment->expiry_date;
                        $user->save();
                    }
                } catch (\Exception $e) {
                    admin_toastr('Payment created but user update failed: ' . $e->getMessage(), 'warning');
                }
            }
        });
        
        return $form;
    }
    
    /**
     * Confirm membership payment
     */
    public function confirm($id)
    {
        try {
            $payment = MembershipPayment::findOrFail($id);
            
            if ($payment->status == MembershipPayment::STATUS_CONFIRMED) {
                admin_toastr('Payment already confirmed', 'warning');
                return back();
            }
            
            $payment->confirm(auth()->id());
            
            admin_toastr('Membership payment confirmed successfully', 'success');
            return back();
            
        } catch (\Exception $e) {
            admin_toastr('Error: ' . $e->getMessage(), 'error');
            return back();
        }
    }
}
