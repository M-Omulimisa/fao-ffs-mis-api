<?php

namespace App\Admin\Controllers;

use App\Models\AccountTransaction;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AccountTransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Account Transactions';

    /**
     * Get dynamic title based on URL
     */
    protected function title()
    {
        $url = request()->url();
        
        if (strpos($url, 'account-transactions-deposit') !== false) {
            return 'Deposits';
        } elseif (strpos($url, 'account-transactions-withdraw') !== false) {
            return 'Withdrawals';
        }
        
        return 'All Account Transactions';
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AccountTransaction());
        
        // Load relationships for better display
        $grid->model()->with(['user', 'group', 'contraEntry', 'meeting', 'cycle', 'creator']);
        
        // Detect transaction type from URL and filter accordingly
        $url = request()->url();
        
        if (strpos($url, 'account-transactions-deposit') !== false) {
            // Show only deposits (positive amounts)
            $grid->model()->where('amount', '>', 0)->orderBy('transaction_date', 'desc');
        } elseif (strpos($url, 'account-transactions-withdraw') !== false) {
            // Show only withdrawals (negative amounts)
            $grid->model()->where('amount', '<', 0)->orderBy('transaction_date', 'desc');
        } else {
            // Show all transactions
            $grid->model()->orderBy('transaction_date', 'desc');
        }
        $grid->disableExport();
        
        $grid->quickSearch('description')->placeholder('Search by description');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            // Filter by owner type (Member vs Group)
            $filter->scope('member', 'Member Transactions')->where('owner_type', 'member');
            $filter->scope('group', 'Group Transactions')->where('owner_type', 'group');
            $filter->scope('all', 'All Transactions');
            
            // VSLA Group filter
            $filter->equal('group_id', 'VSLA Group')
                ->select(\App\Models\FfsGroup::where('type', 'VSLA')->pluck('name', 'id'));
            
            // Individual Member filter
            $filter->equal('user_id', 'Individual Member')
                ->select(User::where('user_type', 'Customer')->pluck('name', 'id'));
            
            // Meeting filter
            $filter->equal('meeting_id', 'Meeting')
                ->select(function () {
                    return \App\Models\VslaMeeting::orderBy('meeting_date', 'desc')
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(function ($meeting) {
                            return [$meeting->id => "Meeting #{$meeting->meeting_number} - " . date('d M Y', strtotime($meeting->meeting_date))];
                        });
                });
            
            // Cycle filter
            $filter->equal('cycle_id', 'Cycle')
                ->select(\App\Models\Project::where('is_active_cycle', 'Yes')->pluck('title', 'id'));
            
            // Account Type filter
            $filter->equal('account_type', 'Account Type')
                ->select([
                    'savings' => 'Savings',
                    'fine' => 'Fine',
                    'loan' => 'Loan',
                    'share' => 'Share',
                    'welfare' => 'Welfare',
                    'social_fund' => 'Social Fund',
                ]);
            
            // Source filter
            $filter->equal('source', 'Source')
                ->select([
                    'meeting_savings' => 'Meeting Savings',
                    'meeting_fine' => 'Meeting Fine',
                    'meeting_welfare' => 'Meeting Welfare',
                    'meeting_social_fund' => 'Meeting Social Fund',
                    'share_purchase' => 'Share Purchase',
                    'loan_disbursement' => 'Loan Disbursement',
                    'loan_repayment' => 'Loan Repayment',
                    'welfare_contribution' => 'Welfare Contribution',
                    'fine_payment' => 'Fine Payment',
                    'share_dividend' => 'Share Dividend',
                    'welfare_distribution' => 'Welfare Distribution',
                    'administrative_expense' => 'Administrative Expense',
                    'external_income' => 'External Income',
                    'bank_charges' => 'Bank Charges',
                    'manual_adjustment' => 'Manual Adjustment',
                    'disbursement' => 'Disbursement (Legacy)',
                    'withdrawal' => 'Withdrawal (Legacy)',
                    'deposit' => 'Deposit (Legacy)',
                ]);
            
            // Contra entry filter
            $filter->scope('with_contra', 'With Contra Entries')->whereNotNull('contra_entry_id');
            $filter->scope('is_contra', 'Is Contra Entry')->where('is_contra_entry', true);
            
            $filter->between('transaction_date', 'Date')->date();
            
            $filter->where(function ($query) {
                $query->where('amount', '>=', $this->input);
            }, 'Min Amount', 'min_amount');
            
            $filter->where(function ($query) {
                $query->where('amount', '<=', $this->input);
            }, 'Max Amount', 'max_amount');
        });

        $grid->column('id', __('ID'))->sortable();
        
        // Owner Type column
        $grid->column('owner_type', __('Owner Type'))
            ->display(function ($ownerType) {
                if ($ownerType === 'group') {
                    return "<span class='label label-primary'><i class='fa fa-users'></i> GROUP</span>";
                } elseif ($ownerType === 'member') {
                    return "<span class='label label-success'><i class='fa fa-user'></i> MEMBER</span>";
                } else {
                    return "<span class='label label-default'><i class='fa fa-question'></i> N/A</span>";
                }
            })
            ->sortable();
        
        // Transaction Owner column (Group or Member details)
        $grid->column('transaction_owner', __('Owner Details'))
            ->display(function () {
                if ($this->owner_type === 'group' && $this->group_id) {
                    $group = \App\Models\FfsGroup::find($this->group_id);
                    if ($group) {
                        $name = "<div style='font-weight:600; font-size:13px;'>{$group->name}</div>";
                        $code = "<div style='color:#999; font-size:11px;'>{$group->code}</div>";
                        return $name . $code;
                    }
                    return "<small class='text-muted'>Unknown Group</small>";
                } elseif ($this->owner_type === 'member' && $this->user_id) {
                    $user = \App\Models\User::find($this->user_id);
                    if ($user) {
                        $name = "<div style='font-weight:600; font-size:13px;'>{$user->name}</div>";
                        $phone = $user->phone_number ? "<div style='color:#999; font-size:11px;'>{$user->phone_number}</div>" : '';
                        return $name . $phone;
                    }
                    return "<small class='text-muted'>Unknown Member</small>";
                }
                return "<span class='text-muted'>-</span>";
            });
        
        // Group column (for reference)
        $grid->column('group.name', __('VSLA Group'))
            ->display(function ($groupName) {
                return $groupName ?? '<span class="text-muted">-</span>';
            });
        
 
        // Account Type column
        $grid->column('account_type', __('Account Type'))
            ->display(function ($accountType) {
                if (!$accountType) return '<span class="text-muted">-</span>';
                
                $colors = [
                    'savings' => 'success',
                    'fine' => 'warning',
                    'loan' => 'danger',
                    'share' => 'primary',
                    'welfare' => 'info',
                    'social_fund' => 'default',
                ];
                
                $color = $colors[$accountType] ?? 'default';
                $label = ucfirst(str_replace('_', ' ', $accountType));
                return "<span class='label label-{$color}'>{$label}</span>";
            })
            ->sortable();
        
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                $isCredit = $amount >= 0;
                $color = $isCredit ? 'green' : 'red';
                $icon = $isCredit ? '↑' : '↓';
                $type = $isCredit ? 'CREDIT' : 'DEBIT';
                $badge = "<span class='label label-" . ($isCredit ? 'success' : 'danger') . "' style='font-size:10px;'>{$type}</span>";
                $amountText = "<strong style='color:{$color}; font-size:14px;'>{$icon} UGX " . number_format(abs($amount), 0) . "</strong>";
                
                return $badge . "<br>" . $amountText;
            })
            ->sortable();
        
        // Contra Entry column
        $grid->column('contra_info', __('Contra Entry'))
            ->display(function () {
                if ($this->contra_entry_id) {
                    $contra = \App\Models\AccountTransaction::find($this->contra_entry_id);
                    if ($contra) {
                        $link = "<a href='/admin/account-transactions/{$this->contra_entry_id}' target='_blank'>";
                        $link .= "<span class='label label-info'><i class='fa fa-link'></i> #{$this->contra_entry_id}</span>";
                        $link .= "</a>";
                        $amount = "<div style='font-size:11px; color:#999;'>" . ($contra->amount >= 0 ? '+' : '') . number_format($contra->amount, 0) . "</div>";
                        return $link . $amount;
                    }
                    return "<span class='label label-default'>#{$this->contra_entry_id}</span>";
                } elseif ($this->is_contra_entry) {
                    $count = \App\Models\AccountTransaction::where('contra_entry_id', $this->id)->count();
                    if ($count > 0) {
                        return "<span class='label label-warning'><i class='fa fa-link'></i> {$count} linked</span>";
                    }
                }
                return "<span class='text-muted'>-</span>";
            });
        
        // Meeting column
        $grid->column('meeting.meeting_number', __('Meeting'))
            ->display(function ($meetingNumber) {
                if ($this->meeting_id) {
                    $meeting = \App\Models\VslaMeeting::find($this->meeting_id);
                    if ($meeting) {
                        $badge = "<span class='label label-primary'>#" . $meetingNumber . "</span>";
                        $date = "<div style='font-size:11px; color:#999;'>" . date('d M Y', strtotime($meeting->meeting_date)) . "</div>";
                        return $badge . $date;
                    }
                }
                return "<span class='text-muted'>-</span>";
            });
        
        // Cycle column
        $grid->column('cycle.title', __('Cycle'))
            ->display(function ($cycleTitle) {
                return $cycleTitle ?? '<span class="text-muted">-</span>';
            });
        
        $grid->column('source', __('Source'))
            ->display(function ($source) {
                $colors = [
                    'meeting_savings' => 'success',
                    'meeting_fine' => 'warning',
                    'meeting_welfare' => 'info',
                    'meeting_social_fund' => 'default',
                    'share_purchase' => 'primary',
                    'loan_disbursement' => 'danger',
                    'loan_repayment' => 'success',
                ];
                
                $color = $colors[$source] ?? 'default';
                $label = ucfirst(str_replace('_', ' ', $source));
                return "<span class='label label-{$color}' style='font-size:10px;'>{$label}</span>";
            })
            ->sortable();
        
        $grid->column('description', __('Description'))
            ->display(function ($desc) {
                return \Illuminate\Support\Str::limit($desc, 50);
            });
        
        $grid->column('transaction_date', __('Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();
        
        $grid->column('creator.name', __('Created By'));
        
        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();

        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableView();
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
        $show = new Show(AccountTransaction::findOrFail($id));

        $show->field('id', __('ID'));
        
        // Owner information
        $show->divider('Transaction Owner');
        $show->field('owner_type', __('Owner Type'))->as(function ($type) {
            return ucfirst($type ?? 'N/A');
        });
        $show->field('group.name', __('VSLA Group'));
        $show->field('group.code', __('Group Code'));
        $show->field('user.name', __('Member'));
        $show->field('user.phone_number', __('Phone'));
        $show->field('user.email', __('Email'));
        
        // Transaction details
        $show->divider('Transaction Details');
        $show->field('account_type', __('Account Type'))->as(function ($type) {
            return ucfirst(str_replace('_', ' ', $type ?? 'N/A'));
        });
        $show->field('source', __('Source'))->as(function ($source) {
            return ucfirst(str_replace('_', ' ', $source));
        });
        $show->field('amount', __('Amount'))->as(function ($amount) {
            $prefix = $amount >= 0 ? '+' : '';
            $type = $amount >= 0 ? 'CREDIT' : 'DEBIT';
            return "[{$type}] {$prefix}UGX " . number_format(abs($amount), 2);
        });
        $show->field('description', __('Description'));
        $show->field('transaction_date', __('Transaction Date'));
        
        // Meeting & Cycle information
        $show->divider('Meeting & Cycle');
        $show->field('meeting_id', __('Meeting ID'));
        $show->field('meeting.meeting_number', __('Meeting Number'));
        $show->field('meeting.meeting_date', __('Meeting Date'));
        $show->field('cycle_id', __('Cycle ID'));
        $show->field('cycle.title', __('Cycle Title'));
        
        // Double-entry information
        $show->divider('Double-Entry Accounting');
        $show->field('is_contra_entry', __('Is Contra Entry'))->as(function ($value) {
            return $value ? 'Yes' : 'No';
        });
        $show->field('contra_entry_id', __('Contra Entry ID'));
        $show->field('contraEntry.amount', __('Contra Amount'))->as(function ($amount) {
            if (!$amount) return 'N/A';
            $prefix = $amount >= 0 ? '+' : '';
            return $prefix . 'UGX ' . number_format(abs($amount), 2);
        });
        
        // Related records
        $show->divider('Related Records');
        $show->field('related_disbursement_id', __('Related Disbursement ID'));
        $show->field('relatedDisbursement.loan_amount', __('Loan Amount'))->as(function ($amount) {
            return $amount ? 'UGX ' . number_format($amount, 2) : 'N/A';
        });
        
        // Audit information
        $show->divider('Audit Information');
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
        $form = new Form(new AccountTransaction());

        $form->select('user_id', __('User'))
            ->options(User::pluck('name', 'id'))
            ->rules('required');
        
        $form->decimal('amount', __('Amount (UGX)'))
            ->rules('required|numeric')
            ->help('Positive for deposit/disbursement, negative for withdrawal');
        
        $form->select('source', __('Source'))
            ->options([
                'deposit' => 'Deposit',
                'withdrawal' => 'Withdrawal',
                'disbursement' => 'Disbursement',
            ])
            ->rules('required');
        
        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(3);
        
        $form->date('transaction_date', __('Transaction Date'))
            ->default(date('Y-m-d'))
            ->rules('required');
        
        $form->hidden('created_by_id')->default(auth()->id());

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
