<?php

namespace App\Admin\Controllers;

use App\Models\ProjectShare;
use App\Models\Project;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ProjectShareController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Project Shares (Investments)';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ProjectShare());

        $grid->model()->orderBy('id', 'desc');
        $grid->disableExport();
        // Enable create button for admin to add shares
        // $grid->disableCreateButton();

        $grid->quickSearch('id')->placeholder('Search by ID');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->equal('project_id', 'Project')
                ->select(Project::pluck('title', 'id'));

            $filter->equal('investor_id', 'Investor')
                ->select(User::pluck('name', 'id'));

            $filter->between('purchase_date', 'Purchase Date')->date();
        });

        $grid->column('id', __('ID'))->sortable();

        $grid->column('investor.name', __('Investor'))
            ->sortable();

        $grid->column('investor.phone_number', __('Phone'));

        $grid->column('project.title', __('Project'))
            ->display(function ($title) {
                return \Illuminate\Support\Str::limit($title, 30);
            })
            ->sortable();

        $grid->column('number_of_shares', __('Shares'))
            ->sortable();

        $grid->column('share_price_at_purchase', __('Price/Share'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            });

        $grid->column('total_amount_paid', __('Total Paid'))
            ->display(function ($amount) {
                return 'UGX ' . number_format($amount, 0);
            })
            ->sortable();

        $grid->column('purchase_date', __('Date'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })
            ->sortable();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M Y, H:i', strtotime($date));
            })
            ->sortable();

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
        $show = new Show(ProjectShare::findOrFail($id));

        $show->field('id', __('ID'));

        $show->field('investor.name', __('Investor'));
        $show->field('investor.phone_number', __('Phone'));
        $show->field('investor.email', __('Email'));

        $show->field('project.title', __('Project'));
        $show->field('project.status', __('Project Status'));

        $show->field('number_of_shares', __('Number of Shares'));

        $show->field('amount_per_share', __('Amount per Share'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });

        $show->field('total_amount_paid', __('Total Amount Paid'))->as(function ($amount) {
            return 'UGX ' . number_format($amount, 0);
        });

        $show->field('payment_status', __('Payment Status'));
        $show->field('purchase_date', __('Purchase Date'));
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
        $form = new Form(new ProjectShare());

        // Only show ID on edit form
        if ($form->isEditing()) {
            $form->display('id', __('ID'));
        }

        // Project selection with financial summary
        $form->select('project_id', __('Project'))
            ->options(function () {
                return Project::where('status', 'ongoing')
                    ->get()
                    ->mapWithKeys(function ($project) {
                        $availableShares = $project->total_shares - $project->shares_sold;
                        return [
                            $project->id => "{$project->title} (Available: {$availableShares} shares @ UGX " . number_format($project->share_price, 0) . "/share)"
                        ];
                    });
            })
            ->rules('required')
            ->help('Select an active project with available shares');

        // Investor selection
        $form->select('investor_id', __('Investor'))
            ->options(function () {
                return User::orderBy('name')
                    ->get()
                    ->mapWithKeys(function ($user) {
                        return [$user->id => "{$user->name} ({$user->phone_number})"];
                    });
            })
            ->rules('required')
            ->help('Select the investor purchasing shares');

        // Number of shares
        $form->decimal('number_of_shares', __('Number of Shares'))
            ->rules('required|integer|min:1')
            ->help('Number of shares to purchase')
            ->default(1);

        // Hidden fields that will be auto-calculated
        $form->hidden('purchase_date')->default(date('Y-m-d'));
        $form->hidden('share_price_at_purchase');
        $form->hidden('total_amount_paid');

        // Display project info dynamically (read-only info box)
        $form->html('<div id="project-info" style="display:none; margin: 10px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; border-radius: 4px;">
            <h4 style="margin-top: 0;">Investment Summary</h4>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong>Share Price:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;" id="share-price">-</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong>Shares Available:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;" id="shares-available">-</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong>Total Shares:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;" id="total-shares">-</td>
                </tr>
                <tr style="background: #e8f4f8;">
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6;"><strong>Investment Amount:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6; font-size: 18px; color: #007bff; font-weight: bold;" id="total-amount">UGX 0</td>
                </tr>
            </table>
        </div>');

        // JavaScript to handle dynamic calculations
        $form->html('<script>
            $(document).ready(function() {
                var projects = ' . json_encode(Project::where('status', 'ongoing')->get(['id', 'share_price', 'total_shares', 'shares_sold'])->keyBy('id')) . ';
                
                function updateProjectInfo() {
                    var projectId = $("select[name=project_id]").val();
                    var numberOfShares = parseInt($("input[name=number_of_shares]").val()) || 0;
                    
                    if (projectId && projects[projectId]) {
                        var project = projects[projectId];
                        var sharePrice = parseFloat(project.share_price);
                        var availableShares = project.total_shares - project.shares_sold;
                        var totalAmount = numberOfShares * sharePrice;
                        
                        $("#share-price").text("UGX " + sharePrice.toLocaleString());
                        $("#shares-available").text(availableShares.toLocaleString());
                        $("#total-shares").text(project.total_shares.toLocaleString());
                        $("#total-amount").text("UGX " + totalAmount.toLocaleString());
                        
                        // Set hidden fields
                        $("input[name=share_price_at_purchase]").val(sharePrice);
                        $("input[name=total_amount_paid]").val(totalAmount);
                        
                        $("#project-info").show();
                    } else {
                        $("#project-info").hide();
                    }
                }
                
                $("select[name=project_id]").on("change", updateProjectInfo);
                $("input[name=number_of_shares]").on("input change", updateProjectInfo);
                
                // Initial update if editing
                updateProjectInfo();
            });
        </script>');

        // Validation before saving
        $form->saving(function (Form $form) {
            $projectId = $form->project_id;
            $numberOfShares = $form->number_of_shares;

            // Validate project exists and is ongoing
            $project = Project::find($projectId);
            if (!$project) {
                admin_error('Error', 'Selected project does not exist.');
                return back();
            }

            if ($project->status !== 'ongoing') {
                admin_error('Error', 'Cannot purchase shares for a project that is not ongoing.');
                return back();
            }

            // Validate shares available
            $availableShares = $project->total_shares - $project->shares_sold;
            
            // If editing, account for current share count
            if ($form->model()->id) {
                $currentShare = ProjectShare::find($form->model()->id);
                $availableShares += $currentShare->number_of_shares;
            }

            if ($numberOfShares > $availableShares) {
                admin_error('Error', "Only {$availableShares} shares are available for this project.");
                return back();
            }

            // Validate number of shares is positive
            if ($numberOfShares <= 0) {
                admin_error('Error', 'Number of shares must be greater than zero.');
                return back();
            }

            // Validate investor exists
            if (!User::find($form->investor_id)) {
                admin_error('Error', 'Selected investor does not exist.');
                return back();
            }

            // Auto-calculate fields
            $form->share_price_at_purchase = $project->share_price;
            $form->total_amount_paid = $numberOfShares * $project->share_price;
            $form->purchase_date = $form->purchase_date ?: date('Y-m-d');
        });

        // After saving, create ProjectTransaction
        $form->saved(function (Form $form) {
            $share = $form->model();
            
            // Check if ProjectTransaction already exists for this share
            $existingTransaction = \App\Models\ProjectTransaction::where('related_share_id', $share->id)->first();
            
            if (!$existingTransaction) {
                // Create the transaction
                \App\Models\ProjectTransaction::create([
                    'project_id' => $share->project_id,
                    'type' => 'income',
                    'source' => 'share_purchase',
                    'amount' => $share->total_amount_paid,
                    'transaction_date' => $share->purchase_date,
                    'details' => "Share purchase by " . $share->investor->name . " - " . $share->number_of_shares . " shares @ UGX " . number_format($share->share_price_at_purchase, 0),
                    'related_share_id' => $share->id,
                    'created_by_id' => \Encore\Admin\Facades\Admin::user()->id,
                    'created_at' => $share->purchase_date,
                    'updated_at' => now(),
                ]);
                
                admin_success('Success', 'Share purchase recorded successfully. Transaction created for UGX ' . number_format($share->total_amount_paid, 0));
            }
        });

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();
        $form->disableEditingCheck();

        return $form;
    }

    /**
     * Format number for display
     */
    private function numberFormat($number)
    {
        return number_format($number, 0);
    }
}
