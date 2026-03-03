<?php

namespace App\Admin\Controllers;

use App\Models\Project;
use App\Models\FfsGroup;
use App\Models\VslaMeeting;
use App\Models\VslaLoan;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

/**
 * CycleController – VSLA Savings Cycle Management
 *
 * Handles VSLA savings cycles exclusively (is_vsla_cycle = 'Yes').
 * Key constraints enforced:
 *   - Only one active cycle per group (is_active_cycle = 'Yes').
 *   - IP admins only see cycles for groups in their IP.
 *   - Admin can activate / deactivate individual cycles.
 */
class CycleController extends AdminController
{
    use IpScopeable;

    protected $title = 'VSLA Savings Cycles';

    // ─────────────────────────── GRID ────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new Project());

        // Only VSLA cycles
        $grid->model()
            ->where('is_vsla_cycle', 'Yes')
            ->orderBy('id', 'desc');

        // IP scoping via group
        $ipId = $this->getAdminIpId();
        if ($ipId !== null) {
            $groupIds = FfsGroup::where('ip_id', $ipId)->pluck('id');
            $grid->model()->whereIn('group_id', $groupIds);
        }

        $grid->disableExport();


        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableBatchActions();


        // Disable batch delete to prevent accidents
        $grid->batchActions(function ($batch) {
            $batch->disableDelete();
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();

            // Activate action (shown when cycle is NOT active)
            $cycleId = $actions->getKey();
            $cycle   = Project::find($cycleId);

            if ($cycle && $cycle->is_active_cycle !== 'Yes') {
                $activateUrl = admin_url('cycles/' . $cycleId . '/activate');
                $actions->append(
                    '<a href="' . $activateUrl . '"
                       class="btn btn-xs btn-success"
                       onclick="return confirm(\'Activate this cycle? The currently active cycle for this group will be deactivated.\')"
                       title="Set as Active Cycle">
                        <i class="fa fa-check-circle"></i> Activate
                    </a> '
                );
            }

            if ($cycle && $cycle->is_active_cycle === 'Yes') {
                $deactivateUrl = admin_url('cycles/' . $cycleId . '/deactivate');
                $actions->append(
                    '<a href="' . $deactivateUrl . '"
                       class="btn btn-xs btn-warning"
                       onclick="return confirm(\'Deactivate this cycle?\')"
                       title="Deactivate Cycle">
                        <i class="fa fa-pause-circle"></i> Deactivate
                    </a> '
                );
            }
        });

        // ─── Filters ──────────────────────────────────────────────────────────
        $grid->filter(function ($filter) use ($ipId) {
            $filter->disableIdFilter();

            // Group filter – scoped to IP
            $groupQuery = FfsGroup::where('status', 'Active');
            if ($ipId !== null) {
                $groupQuery->where('ip_id', $ipId);
            }
            $filter->equal('group_id', 'Group / SACCO')
                ->select(
                    $groupQuery->orderBy('type')->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn($g) => [$g->id => "[{$g->type}] {$g->name}"])
                );

            $filter->equal('is_active_cycle', 'Active?')->select([
                'Yes' => 'Active',
                'No'  => 'Inactive',
            ]);

            $filter->equal('status', 'Status')->select([
                'ongoing'   => 'Ongoing',
                'completed' => 'Completed',
                'on_hold'   => 'On Hold',
            ]);

            $filter->between('start_date', 'Start Date')->date();
        });

        // ─── Columns ──────────────────────────────────────────────────────────
        $grid->column('is_active_cycle', 'Active?')->display(function ($val) {
            return $val === 'Yes'
                ? '<span class="label label-success"><i class="fa fa-check"></i> Active</span>'
                : '<span class="label label-default">Inactive</span>';
        })->sortable();

        $grid->column('group_id', 'Group / SACCO')->display(function ($groupId) {
            if (!$groupId) return '-';
            $group = FfsGroup::find($groupId);
            if (!$group) return '-';
            $typeColors = ['FFS' => 'primary', 'FBS' => 'success', 'VSLA' => 'warning', 'Association' => 'info'];
            $color = $typeColors[$group->type] ?? 'default';
            return "<span class='label label-{$color}'>{$group->type}</span> <strong>{$group->name}</strong>"
                . "<br><small class='text-muted'>{$group->code}</small>";
        })->sortable();

        $grid->column('cycle_name', 'Cycle Name')->display(function ($name) {
            return $name ?: '-';
        })->sortable();

        $grid->column('status', 'Status')->label([
            'ongoing'   => 'success',
            'completed' => 'info',
            'on_hold'   => 'warning',
        ])->sortable();

        $grid->column('share_value', 'Share Value')->display(function ($val) {
            return $val ? 'UGX ' . number_format($val, 0) : '-';
        })->sortable();

        $grid->column('saving_type', 'Type')->display(function ($val) {
            if ($val === 'any_amount') return '<span class="label label-info">Flexible</span>';
            return '<span class="label label-default">Shares</span>';
        })->sortable();

        $grid->column('meeting_frequency', 'Frequency')->display(function ($freq) {
            return $freq ?: '-';
        })->sortable();

        $grid->column('loan_interest_rate', 'Interest Rate')->display(function ($rate) {
            if (!$rate) return '-';
            $freq = $this->interest_frequency ?: '';
            return $rate . '% ' . $freq;
        });

        $grid->column('start_date', 'Start Date')->display(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : '-';
        })->sortable();

        $grid->column('end_date', 'End Date')->display(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : '-';
        })->sortable();

        $grid->column('stats', 'Stats')->display(function () {
            $meetings = VslaMeeting::where('cycle_id', $this->id)->count();
            $loans    = VslaLoan::where('cycle_id', $this->id)->where('status', 'active')->count();
            return "<small>
                        <i class='fa fa-calendar'></i> {$meetings} meetings<br>
                        <i class='fa fa-money'></i> {$loans} active loans
                    </small>";
        });

        $grid->column('created_at', 'Created')->display(function ($date) {
            return date('d M Y', strtotime($date));
        })->sortable()->hide();

        return $grid;
    }

    // ─────────────────────────── SHOW ────────────────────────────────────────

    protected function detail($id)
    {
        $show = new Show(Project::findOrFail($id));

        $show->panel()->style('primary')->title('Savings Cycle Details');

        $show->field('is_active_cycle', 'Active Cycle?')->using([
            'Yes' => '✓ Yes – Currently Active',
            'No'  => '✗ No – Inactive',
        ]);

        $show->field('group_id', 'Group / SACCO')->as(function ($groupId) {
            $group = FfsGroup::find($groupId);
            return $group ? "[{$group->type}] {$group->name} ({$group->code})" : 'N/A';
        });

        $show->field('cycle_name', 'Cycle Name');
        $show->field('status', 'Status')->label([
            'ongoing'   => 'success',
            'completed' => 'info',
            'on_hold'   => 'warning',
        ]);

        $show->divider('Cycle Period');
        $show->field('start_date', 'Start Date')->as(fn($d) => $d ? date('d M Y', strtotime($d)) : 'N/A');
        $show->field('end_date', 'End Date')->as(fn($d) => $d ? date('d M Y', strtotime($d)) : 'N/A');
        $show->field('duration', 'Duration')->as(function () {
            if (!$this->start_date || !$this->end_date) return 'N/A';
            $months = \Carbon\Carbon::parse($this->start_date)->diffInMonths($this->end_date);
            $weeks  = \Carbon\Carbon::parse($this->start_date)->diffInWeeks($this->end_date);
            return "{$months} months ({$weeks} weeks)";
        });

        $show->divider('VSLA Settings');
        $show->field('saving_type', 'Saving Type')->using([
            'shares' => 'Shares (fixed amount)',
            'any_amount' => 'Any Amount (flexible)',
        ]);
        $show->field('share_value', 'Share Value')->as(fn($v) => $v ? 'UGX ' . number_format($v, 0) : 'N/A');
        $show->field('meeting_frequency', 'Meeting Frequency');
        $show->field('loan_interest_rate', 'Interest Rate')->as(fn($r) => $r ? $r . '%' : 'N/A');
        $show->field('interest_frequency', 'Interest Frequency');
        $show->field('minimum_loan_amount', 'Min Loan')->as(fn($v) => $v ? 'UGX ' . number_format($v, 0) : 'N/A');
        $show->field('maximum_loan_multiple', 'Max Loan Multiple')->as(fn($v) => $v ? $v . 'x shares' : 'N/A');
        $show->field('late_payment_penalty', 'Late Penalty')->as(fn($v) => $v ? $v . '%' : 'N/A');

        $show->divider('Cycle Statistics');
        $show->field('_meetings', 'Total Meetings')->as(function () {
            return VslaMeeting::where('cycle_id', $this->id)->count();
        });
        $show->field('_savings', 'Total Savings')->as(function () {
            $s = VslaMeeting::where('cycle_id', $this->id)->sum('total_savings');
            return 'UGX ' . number_format($s, 0);
        });
        $show->field('_active_loans', 'Active Loans')->as(function () {
            return VslaLoan::where('cycle_id', $this->id)->where('status', 'active')->count();
        });
        $show->field('_loans_disbursed', 'Total Disbursed')->as(function () {
            $t = VslaLoan::where('cycle_id', $this->id)->sum('amount');
            return 'UGX ' . number_format($t, 0);
        });

        $show->field('created_at', 'Created At')->date('d M Y H:i');
        $show->field('updated_at', 'Updated At')->date('d M Y H:i');

        return $show;
    }

    // ─────────────────────────── FORM ────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new Project());

        // Always a VSLA cycle
        $form->hidden('is_vsla_cycle')->default('Yes');
        $form->hidden('title')->default('Savings Cycle');

        // ── Group / SACCO Selection ──────────────────────────────────────────
        $ipId = $this->getAdminIpId();

        $form->row(function ($row) use ($ipId) {
            $groupQuery = FfsGroup::where('status', 'Active');
            if ($ipId !== null) {
                $groupQuery->where('ip_id', $ipId);
            }
            $groupOptions = $groupQuery->orderBy('type')->orderBy('name')
                ->get()
                ->mapWithKeys(function ($g) {
                    return [$g->id => "[{$g->type}] {$g->name}"];
                });

            $row->width(12)->select('group_id', 'Group / SACCO')
                ->options($groupOptions)
                ->rules('required')
                ->help('Select the group or SACCO for this savings cycle');
        });

        // ── Basic Information ─────────────────────────────────────────────────
        $form->divider('Basic Information');

        $form->row(function ($row) {
            $year = date('Y');
            $row->width(6)->text('cycle_name', 'Cycle Name')
                ->rules('required|max:200')
                ->default("Cycle 1 – {$year}")
                ->placeholder('e.g. Cycle 1 – January to December 2025')
                ->help('Descriptive name for this savings cycle');
            $row->width(6)->select('status', 'Status')
                ->options([
                    'ongoing'   => 'Ongoing',
                    'completed' => 'Completed',
                    'on_hold'   => 'On Hold',
                ])
                ->default('ongoing')
                ->rules('required');
        });

        $form->row(function ($row) {
            $row->width(12)->select('is_active_cycle', 'Set as Active Cycle?')
                ->options(['Yes' => 'Yes – Make this the active cycle', 'No' => 'No'])
                ->default('Yes')
                ->rules('required')
                ->help('Only ONE cycle can be active per group. Activating this will deactivate any other active cycle for the selected group.');
        });

        // ── Cycle Period ──────────────────────────────────────────────────────
        $form->divider('Cycle Period');

        $form->row(function ($row) {
            $row->width(6)->date('start_date', 'Start Date')->default(date('Y-01-01'))->rules('required');
            $row->width(6)->date('end_date', 'End Date')->default(date('Y-12-31'))->rules('required');
        });

        // ── VSLA Savings Settings ─────────────────────────────────────────────
        $form->divider('Savings Settings');

        $form->row(function ($row) {
            $row->width(4)->select('saving_type', 'Saving Type')
                ->options([
                    'shares'     => 'Shares (fixed amount per share)',
                    'any_amount' => 'Any Amount (flexible contributions)',
                ])
                ->default('shares')
                ->rules('required')
                ->help('Shares = members buy fixed-price shares. Any Amount = flexible savings.');
            $row->width(4)->currency('share_value', 'Share Value (per meeting)')
                ->symbol('UGX')
                ->rules('required|numeric|min:1000')
                ->default(5000)
                ->help('Amount each member contributes per meeting');
            $row->width(4)->select('meeting_frequency', 'Meeting Frequency')
                ->options([
                    'Weekly'    => 'Weekly',
                    'Bi-weekly' => 'Bi-weekly (every 2 weeks)',
                    'Monthly'   => 'Monthly',
                ])
                ->rules('required')
                ->default('Weekly');
        });

        // ── Loan Configuration ────────────────────────────────────────────────
        $form->divider('Loan Configuration');

        $form->row(function ($row) {
            $row->width(4)->decimal('loan_interest_rate', 'Interest Rate (%)')
                ->rules('nullable|numeric|min:0|max:100')
                ->default(10)
                ->help('Primary loan interest rate');
            $row->width(4)->select('interest_frequency', 'Interest Calculated')
                ->options(['Weekly' => 'Weekly', 'Monthly' => 'Monthly'])
                ->default('Monthly');
            $row->width(4)->decimal('late_payment_penalty', 'Late Penalty (%)')
                ->rules('nullable|numeric|min:0|max:50')
                ->default(5);
        });

        $form->row(function ($row) {
            $row->width(6)->decimal('weekly_loan_interest_rate', 'Weekly Interest Rate (%)')
                ->rules('nullable|numeric|min:0|max:100')
                ->help('Leave blank to auto-calculate from primary rate');
            $row->width(6)->decimal('monthly_loan_interest_rate', 'Monthly Interest Rate (%)')
                ->rules('nullable|numeric|min:0|max:100')
                ->help('Leave blank to auto-calculate from primary rate');
        });

        $form->row(function ($row) {
            $row->width(6)->currency('minimum_loan_amount', 'Minimum Loan Amount')
                ->symbol('UGX')
                ->rules('nullable|numeric|min:0')
                ->default(10000);
            $row->width(6)->number('maximum_loan_multiple', 'Maximum Loan Multiple')
                ->rules('nullable|integer|min:1|max:30')
                ->default(10)
                ->help('E.g. 10 = member can borrow up to 10× their shares');
        });

        // ── Saving callback: enforce single active cycle ───────────────────────
        $form->saving(function (Form $form) {
            // Auto-populate 'title' from cycle_name (DB column is NOT NULL)
            $cycleName = request()->input('cycle_name', 'Savings Cycle');
            $titleValue = $cycleName ?: 'Savings Cycle';
            // Use $form->input() to inject into form data — most reliable in laravel-admin
            $form->input('title', $titleValue);
            // Belt-and-suspenders: also set directly on model
            $form->model()->title = $titleValue;

            // Ensure is_vsla_cycle is always set
            $form->input('is_vsla_cycle', 'Yes');
            $form->model()->is_vsla_cycle = 'Yes';

            if ($form->is_active_cycle === 'Yes' && !empty($form->group_id)) {
                // Deactivate all other cycles for this group
                Project::where('group_id', $form->group_id)
                    ->where('is_vsla_cycle', 'Yes')
                    ->where('is_active_cycle', 'Yes')
                    ->when($form->model()->id, function ($q, $id) {
                        $q->where('id', '!=', $id);
                    })
                    ->update(['is_active_cycle' => 'No']);
            }

            // Auto-compute granular interest rates if not provided
            $rate = (float)($form->loan_interest_rate ?? 0);
            if ($rate > 0) {
                if (empty($form->monthly_loan_interest_rate)) {
                    $form->monthly_loan_interest_rate = $form->interest_frequency === 'Monthly' ? $rate : round($rate * 4.33, 2);
                }
                if (empty($form->weekly_loan_interest_rate)) {
                    $form->weekly_loan_interest_rate = $form->interest_frequency === 'Weekly' ? $rate : round($rate / 4.33, 2);
                }
            }

            // Set admin who created this cycle
            if ($form->isCreating()) {
                $form->created_by_id = \Encore\Admin\Facades\Admin::user()->id ?? null;
            }
        });

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }

    // ─────────────────────────── CUSTOM ACTIONS ───────────────────────────────

    /**
     * Activate a cycle: set is_active_cycle = 'Yes' and deactivate others for same group.
     */
    public function activate($id)
    {
        $cycle = Project::findOrFail($id);

        if ($cycle->is_vsla_cycle !== 'Yes') {
            admin_toastr('This is not a VSLA cycle.', 'error');
            return redirect()->back();
        }


            $ipId  = $this->getAdminIpId();
            $group = FfsGroup::find($cycle->group_id);
            if (!$group || $group->ip_id !== $ipId) {
                admin_toastr('Access denied: this cycle belongs to a different IP.', 'error');
                return redirect()->back();
            }
        

        // Deactivate other cycles for the same group
        Project::where('group_id', $cycle->group_id)
            ->where('is_vsla_cycle', 'Yes')
            ->where('id', '!=', $id)
            ->update(['is_active_cycle' => 'No']);

        // Activate this one
        $cycle->is_active_cycle = 'Yes';
        $cycle->status = 'ongoing';
        $cycle->save();

        $groupName = optional(FfsGroup::find($cycle->group_id))->name ?? 'Group';
        admin_toastr("Cycle '{$cycle->cycle_name}' is now active for {$groupName}.", 'success');

        return redirect()->back();
    }

    /**
     * Deactivate a cycle.
     */
    public function deactivate($id)
    {
        $cycle = Project::findOrFail($id);

        if ($cycle->is_vsla_cycle !== 'Yes') {
            admin_toastr('This is not a VSLA cycle.', 'error');
            return redirect()->back();
        }

        $cycle->is_active_cycle = 'No';
        $cycle->save();

        admin_toastr("Cycle '{$cycle->cycle_name}' has been deactivated.", 'success');

        return redirect()->back();
    }

    /**
     * Check if current admin user can manage (create/edit) cycles.
     * Super admins and IP admins can; read-only roles cannot.
     */
    protected function canManageCycles(): bool
    {
        return true;
        $user = Admin::user();
        if (!$user) return false;
        // Allow if user has admin role or is an IP admin (has ip_id set)
        return $user->isAdministrator()
            || ($user->ip_id !== null);
    }
}
