<?php

namespace App\Admin\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class InsuranceTransactionController extends AdminController
{
    protected $title = 'Insurance Transactions';

    protected function grid()
    {
        $grid = new Grid(new Transaction());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        $grid->disableCreateButton();
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user_id', 'User')
                ->select(User::pluck('name', 'id'));
            $filter->equal('type', 'Type')->select([
                'DEPOSIT' => 'Deposit',
                'WITHDRAWAL' => 'Withdrawal',
            ]);
            $filter->equal('status', 'Status')->select([
                'PENDING' => 'Pending',
                'COMPLETED' => 'Completed',
                'FAILED' => 'Failed',
                'CANCELLED' => 'Cancelled',
            ]);
        });

        $grid->column('id', __('ID'))->sortable();
        $grid->column('user.name', __('User'))->sortable();
        $grid->column('amount', __('Amount'))->display(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        })->sortable();
        $grid->column('type', __('Type'))->label([
            'DEPOSIT' => 'success',
            'WITHDRAWAL' => 'danger',
        ])->sortable();
        $grid->column('payment_method', __('Method'))->sortable();
        $grid->column('reference_number', __('Reference'));
        $grid->column('status', __('Status'))->label([
            'PENDING' => 'warning',
            'COMPLETED' => 'success',
            'FAILED' => 'danger',
            'CANCELLED' => 'default',
        ])->sortable();
        $grid->column('created_at', __('Date'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit();
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Transaction::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('user.name', __('User'));
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        $show->field('type', __('Type'));
        $show->field('payment_method', __('Payment Method'));
        $show->field('payment_phone_number', __('Payment Phone'));
        $show->field('payment_account_number', __('Account Number'));
        $show->field('reference_number', __('Reference'));
        $show->field('status', __('Status'));
        $show->field('description', __('Description'));
        $show->field('remarks', __('Remarks'));
        $show->field('transaction_date', __('Transaction Date'));
        $show->field('created_at', __('Created At'));
        return $show;
    }

    protected function form()
    {
        $form = new Form(new Transaction());
        $form->display('id', __('ID'));
        $form->display('user.name', __('User'));
        $form->display('amount', __('Amount'));
        $form->display('type', __('Type'));
        $form->select('status', __('Status'))
            ->options([
                'PENDING' => 'Pending',
                'COMPLETED' => 'Completed',
                'FAILED' => 'Failed',
                'CANCELLED' => 'Cancelled',
            ])
            ->rules('required');
        $form->textarea('remarks', __('Admin Remarks'))
            ->rows(3);
        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        return $form;
    }
}
