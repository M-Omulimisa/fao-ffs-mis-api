<?php

namespace App\Admin\Controllers;

use App\Models\LoanTransaction;
use App\Models\VslaLoan;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LoanTransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Loan Transactions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LoanTransaction());

        // Order by most recent first
        $grid->model()->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');

        // Quick search
        $grid->quickSearch('description');

        // Disable batch actions
        $grid->disableBatchActions();

        // Columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('transaction_date', __('Date'))
            ->display(function ($date) {
                return date('d-M-Y', strtotime($date));
            })
            ->sortable();

        $grid->column('loan_id', __('Loan'))
            ->display(function ($loanId) {
                $loan = VslaLoan::with(['borrower', 'cycle'])->find($loanId);
                if (!$loan) return "Loan #$loanId";
                
                $borrower = $loan->borrower;
                $amount = number_format($loan->loan_amount, 0);
                return "<a href='/admin/vsla-loans/{$loanId}' target='_blank'>" . 
                       ($borrower ? $borrower->name : 'Unknown') . 
                       " (UGX {$amount})</a>";
            });

        $grid->column('borrower', __('Borrower'))
            ->display(function () {
                $loan = VslaLoan::with('borrower')->find($this->loan_id);
                if (!$loan || !$loan->borrower) return 'Unknown';
                return $loan->borrower->name;
            })
            ->sortable();

        $grid->column('group', __('Group'))
            ->display(function () {
                $loan = VslaLoan::with(['cycle.group'])->find($this->loan_id);
                if (!$loan || !$loan->cycle || !$loan->cycle->group) return 'N/A';
                return $loan->cycle->group->name;
            });

        $grid->column('type', __('Type'))
            ->label([
                'principal' => 'danger',
                'interest' => 'warning',
                'payment' => 'success',
                'penalty' => 'danger',
                'waiver' => 'info',
                'adjustment' => 'default',
            ])
            ->display(function ($type) {
                return ucfirst($type);
            })
            ->filter([
                'principal' => 'Principal',
                'interest' => 'Interest',
                'payment' => 'Payment',
                'penalty' => 'Penalty',
                'waiver' => 'Waiver',
                'adjustment' => 'Adjustment',
            ]);

        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                $formatted = number_format(abs($amount), 2);
                $sign = $amount < 0 ? '-' : '+';
                $color = $amount < 0 ? 'red' : 'green';
                return "<span style='color: {$color}; font-weight: bold;'>{$sign} UGX {$formatted}</span>";
            })
            ->sortable();

        $grid->column('description', __('Description'))
            ->limit(50);

        $grid->column('created_by_id', __('Created By'))
            ->display(function ($userId) {
                $user = User::find($userId);
                return $user ? $user->name : 'System';
            })
            ->hide();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d-M-Y H:i', strtotime($date));
            })
            ->hide();

        // Filters
        $grid->filter(function ($filter) {
            // Remove default ID filter
            $filter->disableIdFilter();

            // Add loan filter with dropdown
            $filter->equal('loan_id', 'Loan')->select(function () {
                return VslaLoan::with('borrower')
                    ->get()
                    ->mapWithKeys(function ($loan) {
                        $borrower = $loan->borrower ? $loan->borrower->name : 'Unknown';
                        $amount = number_format($loan->loan_amount, 0);
                        return [$loan->id => "#{$loan->id} - {$borrower} (UGX {$amount})"];
                    });
            });

            // Add date range filter
            $filter->between('transaction_date', 'Transaction Date')->date();

            // Add amount range filter
            $filter->between('amount', 'Amount');
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
        $show = new Show(LoanTransaction::findOrFail($id));

        $show->field('id', __('ID'));
        
        $show->field('loan_id', __('Loan'))->as(function ($loanId) {
            $loan = VslaLoan::find($loanId);
            if (!$loan) return "Loan #$loanId";
            
            $borrower = $loan->borrower;
            $amount = number_format($loan->loan_amount, 2);
            return ($borrower ? $borrower->name : 'Unknown') . " (UGX {$amount})";
        });

        $show->field('type', __('Transaction Type'))->as(function ($type) {
            return ucfirst($type);
        })->label([
            'principal' => 'danger',
            'interest' => 'warning',
            'payment' => 'success',
            'penalty' => 'danger',
            'waiver' => 'info',
            'adjustment' => 'default',
        ]);

        $show->field('amount', __('Amount'))->as(function ($amount) {
            $formatted = number_format(abs($amount), 2);
            $sign = $amount < 0 ? 'Negative (Debt)' : 'Positive (Payment)';
            return "{$sign}: UGX {$formatted}";
        });

        $show->field('transaction_date', __('Transaction Date'));
        $show->field('description', __('Description'));

        $show->field('created_by_id', __('Created By'))->as(function ($userId) {
            $user = User::find($userId);
            return $user ? $user->name : 'System';
        });

        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        // Loan Balance Panel
        $show->divider();
        $show->field('Loan Balance')->as(function () use ($id) {
            $transaction = LoanTransaction::find($id);
            $balance = LoanTransaction::calculateLoanBalance($transaction->loan_id);
            $formatted = number_format(abs($balance), 2);
            
            if ($balance < 0) {
                return "<span style='color: red; font-weight: bold; font-size: 18px;'>Outstanding: UGX {$formatted}</span>";
            } elseif ($balance == 0) {
                return "<span style='color: green; font-weight: bold; font-size: 18px;'>✓ FULLY PAID</span>";
            } else {
                return "<span style='color: blue; font-weight: bold; font-size: 18px;'>Overpaid: UGX {$formatted}</span>";
            }
        })->unescape();

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LoanTransaction());

        // Loan selection
        $form->select('loan_id', __('Loan'))
            ->options(function ($id) {
                if ($id) {
                    $loan = VslaLoan::find($id);
                    if ($loan) {
                        $borrower = $loan->borrower;
                        $amount = number_format($loan->loan_amount, 2);
                        return [$id => ($borrower ? $borrower->name : 'Unknown') . " (UGX {$amount})"];
                    }
                }
                
                // Load active loans
                return VslaLoan::where('status', 'active')
                    ->with('borrower')
                    ->get()
                    ->mapWithKeys(function ($loan) {
                        $borrower = $loan->borrower;
                        $amount = number_format($loan->loan_amount, 2);
                        return [$loan->id => ($borrower ? $borrower->name : 'Unknown') . " (UGX {$amount})"];
                    });
            })
            ->rules('required')
            ->ajax('/admin/api/vsla-loans');

        // Transaction type
        $form->select('type', __('Transaction Type'))
            ->options([
                LoanTransaction::TYPE_PAYMENT => 'Payment (reduces debt)',
                LoanTransaction::TYPE_PENALTY => 'Penalty (increases debt)',
                LoanTransaction::TYPE_WAIVER => 'Waiver (reduces debt)',
                LoanTransaction::TYPE_ADJUSTMENT => 'Adjustment',
            ])
            ->default(LoanTransaction::TYPE_PAYMENT)
            ->rules('required')
            ->help('Principal and Interest are created automatically during disbursement');

        // Amount
        $form->decimal('amount', __('Amount'))
            ->rules('required|numeric')
            ->help('Positive for payments/waivers, Negative for penalties')
            ->attribute(['step' => '0.01']);

        // Transaction date
        $form->date('transaction_date', __('Transaction Date'))
            ->default(date('Y-m-d'))
            ->rules('required');

        // Description
        $form->textarea('description', __('Description'))
            ->rows(3)
            ->rules('required');

        // Created by (hidden, auto-set)
        $form->hidden('created_by_id')->default(auth()->id());

        // Disable certain buttons
        $form->disableCreatingCheck();
        $form->disableViewCheck();

        // Saving hook to update loan balance
        $form->saved(function (Form $form) {
            $loan = VslaLoan::find($form->model()->loan_id);
            if ($loan) {
                $balance = LoanTransaction::calculateLoanBalance($loan->id);
                $loan->balance = abs($balance);
                
                // Update status if fully paid
                if (abs($balance) < 0.01) {
                    $loan->status = 'paid';
                }
                
                $loan->save();
            }
        });

        return $form;
    }
}
