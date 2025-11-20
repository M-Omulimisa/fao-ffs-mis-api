<?php

namespace App\Admin\Controllers;

use App\Models\Disbursement;
use App\Models\Project;
use App\Models\AccountTransaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DisbursementController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Disbursements';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Disbursement());
        
        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        
        $grid->quickSearch('description')->placeholder('Search by description');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->equal('project_id', 'Project')
                ->select(Project::pluck('title', 'id'));
            
            $filter->between('disbursement_date', 'Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('project.title', __('Project'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 35);
            })
            ->sortable();
        
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();
        
        $grid->column('disbursement_date', __('Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();
        
        $grid->column('description', __('Description'))
            ->display(function ($desc) {
                return \Illuminate\Support\Str::limit($desc, 50);
            });
        
        $grid->column('creator.name', __('Created By'));
        
        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();
        
        $grid->column('investors', __('Investors'))
            ->display(function () {
                $count = $this->accountTransactions()->count();
                return $count . ' investor' . ($count != 1 ? 's' : '');
            });

        $grid->actions(function ($actions) {
            $actions->disableEdit();
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
        $show = new Show(Disbursement::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('project.title', __('Project'));
        
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });
        
        $show->field('disbursement_date', __('Disbursement Date'));
        $show->field('description', __('Description'));
        $show->field('creator.name', __('Created By'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));
        
        // Show account transactions created from this disbursement
        $show->accountTransactions('Investor Distributions', function ($accountTransactions) {
            $accountTransactions->resource('/admin/account-transactions');
            
            $accountTransactions->column('user.name', __('Investor'));
            $accountTransactions->column('amount', __('Amount Received'))->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            });
            $accountTransactions->column('transaction_date', __('Date'));
            
            $accountTransactions->disableCreateButton();
            $accountTransactions->disableActions();
            $accountTransactions->disableFilter();
            $accountTransactions->disablePagination();
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Disbursement());

        $form->divider('Project Selection');

        $form->select('project_id', __('Project'))
            ->options(function () {
                return Project::whereIn('status', ['ongoing', 'completed'])
                    ->get()
                    ->mapWithKeys(function ($project) {
                        // Calculate available funds using ProjectTransaction (income - expenses)
                        $income = \App\Models\ProjectTransaction::where('project_id', $project->id)
                            ->where('type', 'income')
                            ->sum('amount');
                        $expenses = \App\Models\ProjectTransaction::where('project_id', $project->id)
                            ->where('type', 'expense')
                            ->sum('amount');
                        $availableFunds = $income - $expenses;
                        $label = $project->title . ' (Available: UGX ' . number_format($availableFunds, 0) . ')';
                        return [$project->id => $label];
                    });
            })
            ->rules('required')
            ->help('Select project to disburse profits from. Available funds shown in brackets.');
        
        // Display project financial summary when project is selected
        $form->html(function ($form) {
            if ($form->model()->project_id) {
                $project = Project::find($form->model()->project_id);
                if ($project) {
                    // Calculate available funds using ProjectTransaction (income - expenses)
                    $income = \App\Models\ProjectTransaction::where('project_id', $project->id)
                        ->where('type', 'income')
                        ->sum('amount');
                    $expenses = \App\Models\ProjectTransaction::where('project_id', $project->id)
                        ->where('type', 'expense')
                        ->sum('amount');
                    $availableFunds = $income - $expenses;
                    $totalInvestors = $project->shares()->distinct('investor_id')->count('investor_id');
                    $totalShares = $project->shares()->sum('number_of_shares');
                    
                    return <<<HTML
                    <div class="alert alert-info">
                        <h4><i class="icon fa fa-info-circle"></i> Project Financial Summary</h4>
                        <table class="table table-bordered" style="background: white; margin-top: 10px;">
                            <tr>
                                <td><strong>Total Income:</strong></td>
                                <td>UGX {$this->numberFormat($income, 0)}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Expenses:</strong></td>
                                <td>UGX {$this->numberFormat($expenses, 0)}</td>
                            </tr>
                            <tr>
                                <td><strong>Already Disbursed:</strong></td>
                                <td>UGX {$project->formatted_total_returns}</td>
                            </tr>
                            <tr style="background: #d4edda;">
                                <td><strong>Available for Disbursement:</strong></td>
                                <td><strong>UGX {$this->numberFormat($availableFunds, 0)}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Total Investors:</strong></td>
                                <td>{$totalInvestors}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Shares:</strong></td>
                                <td>{$totalShares}</td>
                            </tr>
                        </table>
                        <p class="text-muted"><small>The amount you enter will be distributed proportionally to all investors based on their share percentage.</small></p>
                    </div>
HTML;
                }
            }
            return '';
        });

        $form->divider('Disbursement Details');
        
        $form->decimal('amount', __('Amount to Disburse (UGX)'))
            ->rules('required|numeric|min:1')
            ->help('Total amount to distribute proportionally to investors. Must not exceed available funds.');
        
        $form->date('disbursement_date', __('Disbursement Date'))
            ->default(date('Y-m-d'))
            ->rules('required');
        
        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(3)
            ->placeholder('e.g., Q1 2025 profit distribution, Year-end returns, Project completion bonus, etc.');
        
        $form->hidden('created_by_id')->default(auth('admin')->user()->id ?? auth()->id());

        $form->saving(function (Form $form) {
            // Additional validation before saving
            if ($form->project_id && $form->amount) {
                $project = Project::find($form->project_id);
                if ($project) {
                    // Calculate available funds using ProjectTransaction (income - expenses)
                    $income = \App\Models\ProjectTransaction::where('project_id', $project->id)
                        ->where('type', 'income')
                        ->sum('amount');
                    $expenses = \App\Models\ProjectTransaction::where('project_id', $project->id)
                        ->where('type', 'expense')
                        ->sum('amount');
                    $availableFunds = $income - $expenses;
                    
                    if ($form->amount > $availableFunds) {
                        admin_error('Error', 'Insufficient funds! Available: UGX ' . number_format($availableFunds, 0));
                        return back()->withInput();
                    }
                }
            }
        });

        $form->saved(function (Form $form) {
            $disbursement = $form->model();
            $investorCount = AccountTransaction::where('related_disbursement_id', $disbursement->id)->count();
            
            admin_success(
                'Success', 
                'Disbursement created successfully! Amount distributed to ' . $investorCount . ' investor(s).'
            );
        });

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        $form->disableEditingCheck();

        return $form;
    }

    /**
     * Helper to format numbers
     */
    private function numberFormat($number, $decimals = 2)
    {
        return number_format($number, $decimals);
    }
}
