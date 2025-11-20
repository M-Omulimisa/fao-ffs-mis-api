<?php

namespace App\Admin\Controllers;

use App\Models\ProjectTransaction;
use App\Models\Project;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProjectTransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Project Transactions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ProjectTransaction());
        
        $grid->model()->orderBy('transaction_date', 'desc');
        $grid->disableExport();
        
        $grid->quickSearch('description')->placeholder('Search by description');
        
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->equal('project_id', 'Project')
                ->select(Project::pluck('title', 'id'));
            
            $filter->equal('type', 'Type')
                ->select([
                    'income' => 'Income',
                    'expense' => 'Expense',
                ]);
            
            $filter->equal('source', 'Source')
                ->select([
                    'share_purchase' => 'Share Purchase',
                    'project_profit' => 'Project Profit',
                    'project_expense' => 'Project Expense',
                    'returns_distribution' => 'Returns Distribution',
                ]);
            
            $filter->between('transaction_date', 'Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('project.title', __('Project'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 30);
            })
            ->sortable();
        
        $grid->column('amount', __('Amount'))
            ->display(function ($amount) {
                return 'UGX ' . number_format(abs($amount), 0);
            })
            ->sortable();
        
        $grid->column('type', __('Type'))
            ->label([
                'income' => 'success',
                'expense' => 'danger',
            ])
            ->sortable();
        
        $grid->column('source', __('Source'))
            ->label([
                'share_purchase' => 'primary',
                'project_profit' => 'success',
                'project_expense' => 'warning',
                'returns_distribution' => 'info',
            ])
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
            $source = $actions->row->source;
            
            // Only allow editing/deleting manual transactions
            if ($source != 'manual') {
                $actions->disableEdit();
                $actions->disableDelete();
            }
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
        $show = new Show(ProjectTransaction::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('project.title', __('Project'));
        
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return 'UGX ' . number_format(abs($amount), 0);
        });
        
        $show->field('type', __('Type'));
        $show->field('source', __('Source'));
        $show->field('description', __('Description'));
        $show->field('transaction_date', __('Transaction Date'));
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
        $form = new Form(new ProjectTransaction());

        $form->select('project_id', __('Project'))
            ->options(Project::where('status', 'ongoing')->pluck('title', 'id'))
            ->rules('required');
        
        $form->decimal('amount', __('Amount (UGX)'))
            ->rules('required|numeric|min:0')
            ->help('Enter positive amount (system will handle sign based on type)');
        
        $form->radio('type', __('Type'))
            ->options([
                'income' => 'Income',
                'expense' => 'Expense',
            ])
            ->rules('required');
        
        $form->radio('source', __('Source'))
            ->options([
                'project_profit' => 'Project Profit',
                'project_expense' => 'Project Expense',
            ])
            ->rules('required')
            ->help('Share purchases and returns distribution are automated');
        
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
        
        // Prevent editing automated transactions
        $form->editing(function (Form $form) {
            if (in_array($form->model()->source, ['share_purchase', 'returns_distribution'])) {
                admin_toastr('Cannot edit automated transactions', 'error');
                return redirect(admin_url('project-transactions'));
            }
        });
        
        // Update project net profit after save
        $form->saved(function (Form $form) {
            $transaction = $form->model();
            $project = $transaction->project;
            
            if ($project) {
                // Recalculate net profit
                $totalIncome = ProjectTransaction::where('project_id', $project->id)
                    ->where('type', 'income')
                    ->sum('amount');
                
                $totalExpense = ProjectTransaction::where('project_id', $project->id)
                    ->where('type', 'expense')
                    ->sum('amount');
                
                $project->net_profit = $totalIncome - abs($totalExpense);
                $project->save();
            }
        });

        return $form;
    }
}
