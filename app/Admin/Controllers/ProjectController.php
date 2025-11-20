<?php

namespace App\Admin\Controllers;

use App\Models\Project;
use App\Models\ProjectTransaction;
use App\Admin\Helpers\RoleBasedDashboard;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class ProjectController extends AdminController
{
    use RoleBasedDashboard;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Investment Projects';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Project());

        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        
        // Managers can only view, not create/edit/delete
        if (!$this->canSeeFinancialDetails()) {
            $grid->disableCreateButton();
            $grid->disableActions();
            $grid->disableBatchActions();
        }

        $grid->quickSearch('title')->placeholder('Search by project title');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('title', 'Project Title');

            $filter->equal('status', 'Status')->select([
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
            ]);
            $filter->between('created_at', 'Created Date')->datetime();
        });

        $grid->column('id', __('ID'))->sortable();

        $grid->column('image', __('Image'))
            ->lightbox(['width' => 50, 'height' => 50]);

        $grid->column('title', __('Title'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 40);
            })
            ->sortable();


        $grid->column('status', __('Status'))
            ->label([
                'pending' => 'warning',
                'ongoing' => 'success',
                'completed' => 'info',
                'cancelled' => 'danger',
            ])
            ->sortable();

        $grid->column('share_price', __('Share Price'))
            ->display(function ($price) {
                return 'UGX ' . number_format($price, 0);
            })
            ->sortable();

        $grid->column('shares_sold', __('Shares Sold'))
            ->display(function () {
                return $this->shares_sold . ' / ' . $this->total_shares;
            })
            ->sortable();

        $grid->column('total_investment', __('Investment'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();

        $grid->column('total_income', __('Income'))
            ->display(function () {
                $income = ProjectTransaction::where('project_id', $this->id)
                    ->where('type', 'income')
                    ->sum('amount');
                return 'UGX ' . number_format($income, 0);
            })
            ->sortable();

        $grid->column('total_expenses', __('Expenses'))
            ->display(function () {
                $expenses = ProjectTransaction::where('project_id', $this->id)
                    ->where('type', 'expense')
                    ->sum('amount');
                return 'UGX ' . number_format($expenses, 0);
            })
            ->sortable();

        $grid->column('balance', __('Balance'))
            ->display(function () {
                $income = ProjectTransaction::where('project_id', $this->id)
                    ->where('type', 'income')
                    ->sum('amount');
                $expenses = ProjectTransaction::where('project_id', $this->id)
                    ->where('type', 'expense')
                    ->sum('amount');
                $balance = $income - $expenses;
                $color = $balance >= 0 ? 'green' : 'red';
                return '<span style="color: ' . $color . '; font-weight: bold;">UGX ' . number_format($balance, 0) . '</span>';
            })
            ->sortable();

        $grid->column('start_date', __('Start Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->hide();

        $grid->column('end_date', __('End Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->hide();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->hide();

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
        $show = new Show(Project::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('image', __('Image'))->image();
        $show->field('title', __('Title'));
        $show->field('description', __('Description'))->unescape();
        $show->field('status', __('Status'));

        $show->field('share_price', __('Share Price'))->as(function ($price) {
            return 'UGX ' . number_format($price, 0);
        });

        $show->field('total_shares', __('Total Shares'));
        $show->field('shares_sold', __('Shares Sold'));

        $show->field('available_shares', __('Available Shares'))->as(function () {
            return $this->total_shares - $this->shares_sold;
        });

        $show->field('total_investment', __('Total Investment'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });

        $show->field('total_income', __('Total Income'))->as(function () {
            $income = ProjectTransaction::where('project_id', $this->id)
                ->where('type', 'income')
                ->sum('amount');
            return 'UGX ' . number_format($income, 0);
        });

        $show->field('total_expenses', __('Total Expenses'))->as(function () {
            $expenses = ProjectTransaction::where('project_id', $this->id)
                ->where('type', 'expense')
                ->sum('amount');
            return 'UGX ' . number_format($expenses, 0);
        });

        $show->field('balance', __('Balance'))->as(function () {
            $income = ProjectTransaction::where('project_id', $this->id)
                ->where('type', 'income')
                ->sum('amount');
            $expenses = ProjectTransaction::where('project_id', $this->id)
                ->where('type', 'expense')
                ->sum('amount');
            $balance = $income - $expenses;
            return 'UGX ' . number_format($balance, 0);
        });

        $show->field('start_date', __('Start Date'));
        $show->field('end_date', __('End Date'));
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
        $form = new Form(new Project());

        $form->text('title', __('Title'))
            ->rules('required|max:255');

        $form->textarea('description', __('Description'))
            ->rules('required')
            ->rows(5);

        $form->select('status', __('Status'))
            ->options([
                'ongoing' => 'Ongoing',
                'completed' => 'Completed',
                'on_hold' => 'On Hold',
            ])
            ->default('ongoing')
            ->rules('required');

        $form->decimal('share_price', __('Price per Share (UGX)'))
            ->rules('required|numeric|min:0')
            ->help('Amount in UGX');

        $form->decimal('total_shares', __('Total Shares'))
            ->rules('required|integer|min:1')
            ->help('Total number of shares available');

        $form->date('start_date', __('Start Date'))
            ->rules('required');

        $form->date('end_date', __('End Date'))
            ->rules('required');

        $form->image('image', __('Project Image'))
            ->move('projects')
            ->uniqueName();

        // Display readonly calculated fields on edit
        if ($form->isEditing()) {
            $project = Project::find(request()->route()->parameter('project'));
            
            $form->display('shares_sold', __('Shares Sold'))->with(function ($value) {
                return $value . ' / ' . $this->total_shares;
            });

            $form->display('total_investment', __('Total Investment (UGX)'))->with(function ($value) {
                return 'UGX ' . number_format($value, 0);
            });

            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Total Income (UGX)</label>
                <div class="col-sm-8">
                    <div class="box box-solid">
                        <div class="box-body">
                            <strong>UGX ' . number_format(ProjectTransaction::where('project_id', $project->id)->where('type', 'income')->sum('amount'), 0) . '</strong>
                        </div>
                    </div>
                </div>
            </div>');

            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Total Expenses (UGX)</label>
                <div class="col-sm-8">
                    <div class="box box-solid">
                        <div class="box-body">
                            <strong>UGX ' . number_format(ProjectTransaction::where('project_id', $project->id)->where('type', 'expense')->sum('amount'), 0) . '</strong>
                        </div>
                    </div>
                </div>
            </div>');

            $income = ProjectTransaction::where('project_id', $project->id)->where('type', 'income')->sum('amount');
            $expenses = ProjectTransaction::where('project_id', $project->id)->where('type', 'expense')->sum('amount');
            $balance = $income - $expenses;
            $balanceColor = $balance >= 0 ? 'green' : 'red';

            $form->html('<div class="form-group">
                <label class="col-sm-2 control-label">Balance (UGX)</label>
                <div class="col-sm-8">
                    <div class="box box-solid">
                        <div class="box-body">
                            <strong style="color: ' . $balanceColor . '; font-size: 16px;">UGX ' . number_format($balance, 0) . '</strong>
                        </div>
                    </div>
                </div>
            </div>');
        }

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
