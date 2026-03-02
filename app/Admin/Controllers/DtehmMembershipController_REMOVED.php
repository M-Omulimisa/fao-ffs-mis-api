<?php

namespace App\Admin\Controllers;

use App\Models\DtehmMembership;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DtehmMembershipController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'DTEHM Memberships (76,000 UGX)';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DtehmMembership());

        // Order by most recent first
        $grid->model()->orderBy('id', 'desc');

        // Core columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('Member Name'))->display(function () {
            if ($this->user) {
                return "<a href='/admin/users/{$this->user->id}'><strong>{$this->user->name}</strong></a><br>" .
                       "<small>{$this->user->phone_number}</small>";
            }
            return '-';
        });
        
        $grid->column('payment_reference', __('Reference'))->copyable()->label('info');
        
        $grid->column('amount', __('Amount'))->display(function ($amount) {
            return "<strong style='color: #28a745;'>UGX " . number_format($amount, 0) . "</strong>";
        });
        
        $grid->column('status', __('Status'))->display(function ($status) {
            $colors = [
                'PENDING' => 'warning',
                'CONFIRMED' => 'success',
                'FAILED' => 'danger',
                'REFUNDED' => 'secondary',
            ];
            $color = $colors[$status] ?? 'default';
            return "<span class='label label-{$color}'>{$status}</span>";
        })->filter([
            'PENDING' => 'Pending',
            'CONFIRMED' => 'Confirmed',
            'FAILED' => 'Failed',
            'REFUNDED' => 'Refunded',
        ]);
        
        $grid->column('payment_method', __('Payment Method'))->filter([
            'CASH' => 'Cash',
            'MOBILE_MONEY' => 'Mobile Money',
            'BANK_TRANSFER' => 'Bank Transfer',
            'PESAPAL' => 'Pesapal',
        ]);
        
        $grid->column('payment_date', __('Payment Date'))->display(function ($date) {
            return $date ? date('M d, Y', strtotime($date)) : '-';
        })->sortable();
        
        $grid->column('confirmed_at', __('Confirmed'))->display(function ($date) {
            return $date ? date('M d, Y H:i', strtotime($date)) : '-';
        })->sortable();
        
        $grid->column('registeredBy.name', __('Registered By'))->display(function () {
            if ($this->registeredBy) {
                return $this->registeredBy->name;
            }
            return '-';
        });

        // Filters
        $grid->filter(function ($filter) {
            // Remove default ID filter
            $filter->disableIdFilter();
            
            // Add user name search
            $filter->like('user.name', 'Member Name');
            $filter->like('user.phone_number', 'Phone Number');
            
            // Add payment reference search
            $filter->like('payment_reference', 'Payment Reference');
            
            // Add date range filter
            $filter->between('payment_date', 'Payment Date')->date();
            $filter->between('confirmed_at', 'Confirmed Date')->datetime();
            
            // Add status filter
            $filter->equal('status', 'Status')->select([
                'PENDING' => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED' => 'Failed',
                'REFUNDED' => 'Refunded',
            ]);
            
            // Add payment method filter
            $filter->equal('payment_method', 'Payment Method')->select([
                'CASH' => 'Cash',
                'MOBILE_MONEY' => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL' => 'Pesapal',
            ]);
        });

        // Batch actions
        $grid->batchActions(function ($batch) {
            $batch->add(new \App\Admin\Actions\BatchConfirmDtehmMembership());
        });

        // Actions
        $grid->actions(function ($actions) {
            $actions->add(new \App\Admin\Actions\ConfirmDtehmMembership());
        });

        // Export
        $grid->export(function ($export) {
            $export->filename('DTEHM_Memberships_' . date('Y-m-d'));
            $export->except(['deleted_at']);
        });

        // Disable create button (memberships auto-created via user registration)
        $grid->disableCreateButton();
        
        // Quick search
        $grid->quickSearch('user.name', 'payment_reference', 'user.phone_number');

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
        $show = new Show(DtehmMembership::findOrFail($id));

        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });

        // Member Information
        $show->divider('Member Information');
        $show->field('user.name', __('Member Name'));
        $show->field('user.phone_number', __('Phone Number'));
        $show->field('user.email', __('Email'));
        $show->field('user.address', __('Address'));

        // Payment Details
        $show->divider('Payment Details');
        $show->field('id', __('Membership ID'));
        $show->field('payment_reference', __('Payment Reference'))->copyable();
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('status', __('Status'))->using([
            'PENDING' => 'Pending',
            'CONFIRMED' => 'Confirmed',
            'FAILED' => 'Failed',
            'REFUNDED' => 'Refunded',
        ])->dot([
            'PENDING' => 'warning',
            'CONFIRMED' => 'success',
            'FAILED' => 'danger',
            'REFUNDED' => 'secondary',
        ]);
        $show->field('payment_method', __('Payment Method'))->using([
            'CASH' => 'Cash',
            'MOBILE_MONEY' => 'Mobile Money',
            'BANK_TRANSFER' => 'Bank Transfer',
            'PESAPAL' => 'Pesapal',
        ]);
        $show->field('payment_phone_number', __('Payment Phone'));
        $show->field('payment_account_number', __('Account Number'));
        $show->field('payment_date', __('Payment Date'));
        $show->field('confirmed_at', __('Confirmed At'));

        // Membership Information
        $show->divider('Membership Information');
        $show->field('membership_type', __('Type'))->badge();
        $show->field('expiry_date', __('Expiry Date'))->as(function ($date) {
            return $date ? $date : 'Lifetime (No Expiry)';
        });
        $show->field('receipt_photo', __('Receipt Photo'))->image();
        $show->field('description', __('Description'));
        $show->field('notes', __('Notes'));

        // Pesapal Details (if applicable)
        if ($show->model()->payment_method === 'PESAPAL') {
            $show->divider('Pesapal Payment Details');
            $show->field('pesapal_merchant_reference', __('Merchant Reference'));
            $show->field('pesapal_tracking_id', __('Tracking ID'));
            $show->field('pesapal_payment_status_code', __('Status Code'));
            $show->field('pesapal_payment_status_description', __('Status Description'));
        }

        // Audit Trail
        $show->divider('Audit Information');
        $show->field('registeredBy.name', __('Registered By'));
        $show->field('creator.name', __('Created By'));
        $show->field('confirmer.name', __('Confirmed By'));
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
        $form = new Form(new DtehmMembership());

        $form->html('<div class="alert alert-info">
            <strong>Note:</strong> DTEHM memberships are automatically created when admins register new users. 
            Use this form only to manually create or edit memberships when necessary.
        </div>');

        // Member Information
        $form->divider('Member Information');
        
        $form->select('user_id', __('Select Member'))
            ->options(\App\Models\User::all()->pluck('name', 'id'))
            ->rules('required')
            ->ajax('/admin/api/users')
            ->help('Select the user for this DTEHM membership');

        // Payment Details
        $form->divider('Payment Details');
        
        if ($form->isEditing()) {
            $form->display('payment_reference', __('Payment Reference'));
        }
        
        $form->currency('amount', __('Amount (UGX)'))
            ->default(76000)
            ->rules('required')
            ->symbol('UGX')
            ->help('Default: 76,000 UGX');
        
        $form->select('status', __('Payment Status'))
            ->options([
                'PENDING' => 'Pending',
                'CONFIRMED' => 'Confirmed',
                'FAILED' => 'Failed',
                'REFUNDED' => 'Refunded',
            ])
            ->default('PENDING')
            ->rules('required');
        
        $form->select('payment_method', __('Payment Method'))
            ->options([
                'CASH' => 'Cash',
                'MOBILE_MONEY' => 'Mobile Money',
                'BANK_TRANSFER' => 'Bank Transfer',
                'PESAPAL' => 'Pesapal',
            ])
            ->default('CASH')
            ->rules('required');

        $form->row(function ($row) {
            $row->width(6)->text('payment_phone_number', __('Payment Phone Number'))
                ->help('Phone number used for payment');
            $row->width(6)->text('payment_account_number', __('Account Number'))
                ->help('Bank account or mobile money account');
        });

        $form->row(function ($row) {
            $row->width(6)->datetime('payment_date', __('Payment Date'))
                ->default(date('Y-m-d H:i:s'))
                ->rules('required');
            $row->width(6)->datetime('confirmed_at', __('Confirmed At'))
                ->help('Leave empty for pending payments');
        });

        // Membership Details
        $form->divider('Membership Details');
        
        $form->display('membership_type', __('Membership Type'))
            ->default('DTEHM')
            ->help('Always DTEHM for this membership type');
        
        $form->date('expiry_date', __('Expiry Date'))
            ->help('Leave empty for lifetime membership (recommended for DTEHM)');
        
        $form->image('receipt_photo', __('Receipt Photo'))
            ->uniqueName()
            ->move('receipts/dtehm')
            ->help('Upload payment receipt/proof');

        $form->row(function ($row) {
            $row->width(6)->textarea('description', __('Description'))
                ->rows(3)
                ->help('Brief description of this membership payment');
            $row->width(6)->textarea('notes', __('Internal Notes'))
                ->rows(3)
                ->help('Internal notes (not visible to member)');
        });

        // Pesapal Integration (optional)
        $form->divider('Pesapal Details (Optional)');
        
        $form->row(function ($row) {
            $row->width(6)->text('pesapal_merchant_reference', __('Pesapal Merchant Reference'));
            $row->width(6)->text('pesapal_tracking_id', __('Pesapal Tracking ID'));
        });
        
        $form->row(function ($row) {
            $row->width(6)->text('pesapal_payment_status_code', __('Pesapal Status Code'));
            $row->width(6)->text('confirmation_code', __('Confirmation Code'));
        });
        
        $form->textarea('pesapal_payment_status_description', __('Pesapal Status Description'))
            ->rows(2);

        // Auto-set audit fields
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->created_by = \Admin::user()->id;
                $form->registered_by_id = \Admin::user()->id;
            }
            $form->updated_by = \Admin::user()->id;
            
            // If status is being set to CONFIRMED and confirmer not set
            if ($form->status === 'CONFIRMED' && !$form->confirmed_by) {
                $form->confirmed_by = \Admin::user()->id;
            }
        });

        // Success message
        $form->saved(function (Form $form) {
            admin_toastr('DTEHM Membership saved successfully', 'success');
        });

        // Hide unnecessary fields from form submission
        $form->ignore(['created_at', 'updated_at', 'deleted_at']);

        // Form configuration
        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->disableCreatingCheck();

        // Tools configuration
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
