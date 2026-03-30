<?php

namespace App\Admin\Controllers;

use App\Models\FfsGroup;
use App\Models\Project;
use App\Models\User;
use App\Models\VslaOpeningBalance;
use App\Services\OpeningBalanceService;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VslaOpeningBalanceController
 *
 * Manages VSLA opening balance records (header + per-member entries).
 *
 * Access tiers:
 *   Super Admin  → all groups / all IPs
 *   IP Manager   → only groups belonging to their IP
 *   Others       → standard IP scoping
 *
 * Index page layout:
 *   ① Panel — groups that have an active VSLA cycle but NO opening balance yet
 *   ② Grid  — existing opening balance records (filterable, sortable)
 *
 * Custom routes (must be registered BEFORE the resource):
 *   GET  vsla-opening-balance-cycles           → apiCyclesForGroup()
 *   GET  vsla-opening-balances/{id}/reprocess  → reprocess()
 */
class VslaOpeningBalanceController extends AdminController
{
    use IpScopeable;

    protected $title = 'VSLA Opening Balances';

    // ─── INDEX ────────────────────────────────────────────────────────────────

    public function index(Content $content)
    {
        return $content
            ->title('VSLA Opening Balances')
            ->description('Review and manage opening balance submissions per VSLA group and cycle')
            ->row(function (Row $row) {
                $this->renderMissingGroupsPanel($row);
            })
            ->row(function (Row $row) {
                $row->column(12, $this->grid());
            });
    }

    // ─── MANUAL REPROCESS ─────────────────────────────────────────────────────

    /**
     * Manually trigger (re-)processing of an opening balance record.
     * Useful when auto-processing failed or when the record is stuck in "submitted".
     */
    public function reprocess(int $id)
    {
        $ob = VslaOpeningBalance::findOrFail($id);

        // IP access check
        if (!$this->isSuperAdmin()) {
            $group = FfsGroup::find($ob->group_id);
            if (!$group || (int) $group->ip_id !== (int) $this->getAdminIpId()) {
                admin_toastr('Access denied: this record belongs to a different Implementing Partner.', 'error');
                return redirect(admin_url('vsla-opening-balances'));
            }
        }

        if ($ob->memberEntries()->count() === 0) {
            admin_toastr('Cannot reprocess: no member entries found for this opening balance.', 'error');
            return redirect(admin_url('vsla-opening-balances'));
        }

        try {
            DB::transaction(function () use ($ob) {
                $service = new OpeningBalanceService();
                $userId  = Admin::user()->id ?? $ob->submitted_by_id ?? $ob->group_id;
                $summary = $service->process($ob, $userId);

                $ob->updateQuietly([
                    'status'           => 'processed',
                    'is_processed'     => true,
                    'processed_at'     => now(),
                    'processing_notes' => json_encode($summary['log']),
                ]);
            });

            admin_toastr("Opening balance #{$id} reprocessed successfully.", 'success');
        } catch (\Throwable $e) {
            Log::error("Admin reprocess failed id={$id}: " . $e->getMessage());
            admin_toastr('Reprocess failed: ' . $e->getMessage(), 'error');
        }

        return redirect(admin_url('vsla-opening-balances'));
    }

    // ─── API — cycles for a given group (used by ->load() on the form) ────────

    /**
     * Returns VSLA cycles belonging to the given group.
     * Called with ?q=<group_id>. Returns [{id, text}] JSON for Encore Admin ->load().
     */
    public function apiCyclesForGroup(Request $request)
    {
        $groupId = (int) $request->get('q');
        if (!$groupId) {
            return response()->json([]);
        }

        // IP scope: verify the group belongs to this admin's IP
        $ipId = $this->getAdminIpId();
        if ($ipId !== null) {
            $group = FfsGroup::where('id', $groupId)->where('ip_id', $ipId)->first();
            if (!$group) {
                return response()->json([]);
            }
        }

        $cycles = Project::where('group_id', $groupId)
            ->where('is_vsla_cycle', 'Yes')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($c) => [
                'id'   => $c->id,
                'text' => $c->cycle_name ?: $c->title ?: "Cycle #{$c->id}",
            ])
            ->values();

        return response()->json($cycles);
    }

    // ─── ALL GROUPS PANEL ──────────────────────────────────────────────────────

    /**
     * Renders a comprehensive panel listing ALL groups with member count,
     * facilitator/chairperson info, and opening balance status.
     */
    private function renderMissingGroupsPanel(Row $row): void
    {
        $ipId = $this->getAdminIpId();

        // All groups (IP-scoped for non-super-admins)
        $allGroups = FfsGroup::query()
            ->with(['implementingPartner', 'facilitator', 'admin'])
            ->withCount('members')
            ->when($ipId !== null, fn($q) => $q->where('ip_id', $ipId))
            ->orderBy('name')
            ->get();

        // Group IDs that have at least one opening balance
        $groupsWithOb = VslaOpeningBalance::pluck('group_id')->unique();

        $row->column(12, function (Column $col) use ($allGroups, $groupsWithOb) {
            $total        = $allGroups->count();
            $withOb       = $allGroups->filter(fn($g) => $groupsWithOb->contains($g->id))->count();
            $withoutOb    = $total - $withOb;
            $totalMembers = $allGroups->sum('members_count');

            $html  = "<div style='background:#fff;border:1px solid #ddd;padding:16px;margin-bottom:16px;border-radius:2px;'>";

            // Header
            $html .= "<h4 style='margin:0 0 12px;'><i class='fa fa-users' style='color:#003d80;'></i>&nbsp; All Groups &mdash; Opening Balance Status</h4>";

            // Summary stat cards
            $html .= "<div style='display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;'>";
            $html .= $this->statCard($total, 'Total Groups', '#003d80');
            $html .= $this->statCard(number_format($totalMembers), 'Total Members', '#1565c0');
            $html .= $this->statCard($withOb, 'OB Submitted', '#2e7d32');
            $html .= $this->statCard($withoutOb, 'OB Pending', $withoutOb > 0 ? '#e65100' : '#4caf50');
            $html .= "</div>";

            if ($total === 0) {
                $html .= "<p class='text-muted' style='margin:0;padding:8px 0;'>No groups found.</p>";
            } else {
                $html .= "<div style='overflow-x:auto;max-height:420px;overflow-y:auto;'>";
                $html .= "<table class='table table-bordered table-condensed table-striped' style='margin:0;font-size:13px;'>";
                $html .= "<thead><tr style='background:#e8eef6; position:sticky;top:0;z-index:1;'>"
                        . "<th style='width:35px;'>#</th>"
                        . "<th>Group Name</th>"
                        . "<th>IP</th>"
                        . "<th style='width:70px;text-align:center;'>Members</th>"
                        . "<th>Facilitator</th>"
                        . "<th>Facilitator Phone</th>"
                        . "<th>Chairperson</th>"
                        . "<th>Chairperson Phone</th>"
                        . "<th style='width:90px;text-align:center;'>OB Status</th>"
                        . "</tr></thead><tbody>";

                foreach ($allGroups as $i => $group) {
                    $name    = e($group->name ?: 'Unnamed Group');
                    $ipName  = e(optional($group->implementingPartner)->name ?? '—');
                    $members = $group->members_count;

                    // Facilitator
                    $facUser  = $group->facilitator;
                    $facName  = $facUser
                        ? e($facUser->name ?: trim(($facUser->first_name ?? '') . ' ' . ($facUser->last_name ?? '')))
                        : '<span class="text-muted">—</span>';
                    $facPhone = $facUser && $facUser->phone_number
                        ? e($facUser->phone_number)
                        : '<span class="text-muted">—</span>';

                    // Chairperson (admin_id relationship, fallback to contact_person fields)
                    $chairUser  = $group->admin;
                    if ($chairUser) {
                        $chairName  = e($chairUser->name ?: trim(($chairUser->first_name ?? '') . ' ' . ($chairUser->last_name ?? '')));
                        $chairPhone = $chairUser->phone_number ? e($chairUser->phone_number) : '<span class="text-muted">—</span>';
                    } elseif ($group->contact_person_name) {
                        $chairName  = e($group->contact_person_name);
                        $chairPhone = $group->contact_person_phone ? e($group->contact_person_phone) : '<span class="text-muted">—</span>';
                    } else {
                        $chairName  = '<span class="text-muted">—</span>';
                        $chairPhone = '<span class="text-muted">—</span>';
                    }

                    // OB Status badge
                    $hasOb = $groupsWithOb->contains($group->id);
                    $obBadge = $hasOb
                        ? "<span class='label label-success'><i class='fa fa-check'></i> Submitted</span>"
                        : "<span class='label label-warning'><i class='fa fa-clock-o'></i> Pending</span>";

                    // Highlight rows without OB
                    $rowStyle = $hasOb ? '' : "style='background:#fff8e1 !important;'";

                    $html .= "<tr {$rowStyle}>"
                           . "<td style='color:#999;text-align:center;'>" . ($i + 1) . "</td>"
                           . "<td><strong>{$name}</strong></td>"
                           . "<td>{$ipName}</td>"
                           . "<td style='text-align:center;'><span class='badge' style='background:" . ($members > 0 ? '#607d8b' : '#e0e0e0') . ";'>{$members}</span></td>"
                           . "<td>{$facName}</td>"
                           . "<td>{$facPhone}</td>"
                           . "<td>{$chairName}</td>"
                           . "<td>{$chairPhone}</td>"
                           . "<td style='text-align:center;'>{$obBadge}</td>"
                           . "</tr>";
                }

                $html .= "</tbody></table></div>";
            }

            $html .= "</div>";
            $col->append($html);
        });
    }

    /**
     * Mini stat card HTML for the overview panel.
     */
    private function statCard($value, string $label, string $color): string
    {
        return "<div style='flex:1;min-width:110px;background:{$color};color:#fff;padding:10px 14px;border-radius:3px;text-align:center;'>"
             . "<div style='font-size:20px;font-weight:bold;'>{$value}</div>"
             . "<div style='font-size:11px;opacity:0.9;'>{$label}</div>"
             . "</div>";
    }

    // ─── GRID ─────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new VslaOpeningBalance());
        $ipId = $this->getAdminIpId();

        $grid->model()
            ->with(['group.facilitator', 'group.admin', 'cycle', 'submittedBy'])
            ->withCount('memberEntries')
            ->orderBy('id', 'desc');

        if ($ipId !== null) {
            $grid->model()->whereHas('group', fn($q) => $q->where('ip_id', $ipId));
        }

        $grid->disableBatchActions();
        $grid->disableCreateButton(); // Use the "Create" links in the panel above

        // ── Columns ──────────────────────────────────────────────────────────

        $grid->column('id', 'ID')->sortable()->width(60);

        $grid->column('group_id', 'Group')->display(function () {
            return $this->group
                ? '<strong>' . e($this->group->name) . '</strong>'
                : '<span class="text-muted">—</span>';
        });

        $grid->column('group_members', 'Members')->display(function () {
            if (!$this->group) return '—';
            $cnt = $this->group->members()->count();
            $bg  = $cnt > 0 ? '#607d8b' : '#e0e0e0';
            return "<span class='badge' style='background:{$bg};'>{$cnt}</span>";
        })->width(70);

        $grid->column('facilitator_info', 'Facilitator')->display(function () {
            if (!$this->group || !$this->group->facilitator) {
                return '<span class="text-muted">—</span>';
            }
            $u = $this->group->facilitator;
            $name  = e($u->name ?: trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')));
            $phone = $u->phone_number ? '<br><small class="text-muted">' . e($u->phone_number) . '</small>' : '';
            return $name . $phone;
        });

        $grid->column('chairperson_info', 'Chairperson')->display(function () {
            if (!$this->group) return '<span class="text-muted">—</span>';
            $u = $this->group->admin;
            if ($u) {
                $name  = e($u->name ?: trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')));
                $phone = $u->phone_number ? '<br><small class="text-muted">' . e($u->phone_number) . '</small>' : '';
                return $name . $phone;
            }
            if ($this->group->contact_person_name) {
                $name  = e($this->group->contact_person_name);
                $phone = $this->group->contact_person_phone
                    ? '<br><small class="text-muted">' . e($this->group->contact_person_phone) . '</small>' : '';
                return $name . $phone;
            }
            return '<span class="text-muted">—</span>';
        });

        $grid->column('cycle_id', 'Cycle')->display(function () {
            if (!$this->cycle) {
                return '<span class="text-muted">—</span>';
            }
            $label = e($this->cycle->cycle_name ?: $this->cycle->title ?: "Cycle #{$this->cycle_id}");
            return "<span class='label label-default'>{$label}</span>";
        });

        $grid->column('submitted_by_id', 'Submitted By')->display(function () {
            if (!$this->submittedBy) {
                return '<span class="text-muted">—</span>';
            }
            $u = $this->submittedBy;
            return e($u->name ?: trim($u->first_name . ' ' . $u->last_name));
        });

        $grid->column('status', 'Status')->display(function ($status) {
            $map = [
                'draft'     => ['label-default', 'pencil',       'Draft'],
                'submitted' => ['label-warning', 'clock-o',      'Submitted'],
                'processed' => ['label-success', 'check-circle', 'Processed'],
            ];
            [$cls, $icon, $text] = $map[$status] ?? ['label-default', 'question', ucfirst($status ?? '')];
            return "<span class='label {$cls}'><i class='fa fa-{$icon}'></i>&nbsp;{$text}</span>";
        });

        $grid->column('is_processed', 'Processed?')->display(function ($val) {
            return $val
                ? "<i class='fa fa-check-circle' style='color:#4caf50;font-size:16px;' title='Yes'></i>"
                : "<i class='fa fa-times-circle' style='color:#ccc;font-size:16px;' title='No'></i>";
        })->width(90);

        $grid->column('member_entries_count', '# Members')->display(function ($cnt) {
            return "<span class='badge' style='background:#607d8b;'>{$cnt}</span>";
        })->width(90);

        $grid->column('submission_date', 'Submitted At')->sortable()->display(function ($dt) {
            return $dt ? date('d M Y H:i', strtotime($dt)) : '—';
        });

        $grid->column('processed_at', 'Processed At')->sortable()->display(function ($dt) {
            return $dt ? date('d M Y H:i', strtotime($dt)) : '<span class="text-muted">—</span>';
        });

        $grid->column('created_at', 'Created')->sortable()->display(function ($dt) {
            return $dt ? date('d M Y', strtotime($dt)) : '—';
        });

        // ── Filters ──────────────────────────────────────────────────────────

        $grid->filter(function ($filter) use ($ipId) {
            $filter->disableIdFilter();

            $filter->equal('status', 'Status')->select([
                'draft'     => 'Draft',
                'submitted' => 'Submitted',
                'processed' => 'Processed',
            ]);

            $groupOptions = FfsGroup::query()
                ->when($ipId !== null, fn($q) => $q->where('ip_id', $ipId))
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();

            $filter->equal('group_id', 'Group')->select($groupOptions);

            $filter->equal('is_processed', 'Processed?')->select([
                '1' => 'Yes',
                '0' => 'No',
            ]);

            $filter->between('created_at', 'Created Date')->datetime();
        });

        // ── Row actions ──────────────────────────────────────────────────────

        $grid->actions(function ($actions) {
            $row    = $actions->row;
            $id     = $actions->getKey();
            $status = $row->status;

            // Edit only allowed while still in draft
            if ($status !== 'draft') {
                $actions->disableEdit();
            }

            // Reprocess button for submitted-but-unprocessed records
            if ($status === 'submitted' && !$row->is_processed) {
                $url = admin_url("vsla-opening-balances/{$id}/reprocess");
                $actions->prepend(
                    "<a href='{$url}' class='btn btn-xs btn-warning' "
                    . "onclick=\"return confirm('Reprocess opening balance #{$id}?')\" "
                    . "title='Manually trigger processing'>"
                    . "<i class='fa fa-refresh'></i> Reprocess"
                    . "</a>&nbsp;"
                );
            }

            // Keep soft-delete disabled on the grid — data should be preserved
            $actions->disableDelete();
        });

        return $grid;
    }

    // ─── FORM ─────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form    = new Form(new VslaOpeningBalance());
        $ipId    = $this->getAdminIpId();
        $groupId = (int) (request('group_id') ?? 0) ?: null;

        // In edit mode, seed cycle options for the group already attached to this record
        $seedGroupId = $groupId;
        if ($form->isEditing()) {
            $editId      = request()->route('vsla_opening_balance');
            $existing    = $editId ? VslaOpeningBalance::find($editId) : null;
            $seedGroupId = $existing ? (int) $existing->group_id : $groupId;
        }

        // ── Group select ──────────────────────────────────────────────────────

        $groupOptions = FfsGroup::query()
            ->when($ipId !== null, fn($q) => $q->where('ip_id', $ipId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $form->select('group_id', 'VSLA Group')
            ->options($groupOptions)
            ->default($groupId)
            ->required()
            ->load('cycle_id', admin_url('vsla-opening-balance-cycles'))
            ->help('Select the VSLA group. Cycle options will load automatically.');

        // ── Cycle select (seeded; updates via AJAX when group changes) ─────────

        $cycleOptions = [];
        if ($seedGroupId) {
            $cycleOptions = Project::where('group_id', $seedGroupId)
                ->where('is_vsla_cycle', 'Yes')
                ->orderBy('id', 'desc')
                ->get()
                ->mapWithKeys(fn($c) => [
                    $c->id => $c->cycle_name ?: $c->title ?: "Cycle #{$c->id}",
                ])
                ->toArray();
        }

        $form->select('cycle_id', 'Savings Cycle')
            ->options($cycleOptions)
            ->required()
            ->help('Savings cycle for this opening balance. Pick the group first to load its cycles.');

        // ── Submitted By ──────────────────────────────────────────────────────

        // Pre-load members for the selected group (if known) for a manageable list
        $memberOptions = [];
        if ($seedGroupId) {
            $memberOptions = User::where('group_id', $seedGroupId)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(function ($u) {
                    $name = $u->name ?: trim($u->first_name . ' ' . $u->last_name);
                    return [$u->id => $name . " (#{$u->id})"];
                })
                ->toArray();
        }

        $form->select('submitted_by_id', 'Submitted By (optional)')
            ->options($memberOptions)
            ->help('The group member (typically chairperson) who submitted this record. Leave blank to attribute to your admin account.');

        // ── Status ────────────────────────────────────────────────────────────

        $form->select('status', 'Status')
            ->options([
                'draft'     => 'Draft',
                'submitted' => 'Submitted',
                'processed' => 'Processed',
            ])
            ->default('draft')
            ->required()
            ->help('Draft = in progress. Submitted = triggers auto fan-out. Processed = fan-out complete.');

        // ── Date & Notes ──────────────────────────────────────────────────────

        $form->datetime('submission_date', 'Submission Date')
            ->default(now()->toDateTimeString());

        $form->textarea('notes', 'Notes')
            ->rows(3)
            ->placeholder('Optional notes about this opening balance...');

        // ── Processing info (read-only section visible in edit mode only) ──────

        if ($form->isEditing()) {
            $form->divider('Processing Info');

            $form->display('is_processed', 'Processed?')
                ->with(fn($v) => $v ? '✔ Yes' : '✘ No');

            $form->display('processed_at', 'Processed At');

            $form->display('processing_notes', 'Processing Log')
                ->with(function ($v) {
                    if (!$v) {
                        return '—';
                    }
                    $log = json_decode($v, true);
                    if (is_array($log)) {
                        return '<pre style="font-size:11px;max-height:150px;overflow-y:auto;'
                             . 'white-space:pre-wrap;margin:0;">'
                             . e(implode("\n", $log)) . '</pre>';
                    }
                    return e($v);
                });
        }

        // ── Save hooks ────────────────────────────────────────────────────────

        $form->saving(function (Form $form) {
            // Default submitted_by_id to the currently logged-in admin user
            if (empty($form->submitted_by_id)) {
                $form->submitted_by_id = Admin::user()->id ?? null;
            }
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->disableCreatingCheck();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        return $form;
    }

    // ─── DETAIL / SHOW ────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $record = VslaOpeningBalance::with([
            'group',
            'cycle',
            'submittedBy',
            'memberEntries.member',
        ])->findOrFail($id);

        $show = new Show($record);
        $show->panel()->style('primary')->title('Opening Balance Details');

        $show->field('id', 'ID');

        $show->field('group_id', 'VSLA Group')->as(function () use ($record) {
            return $record->group ? e($record->group->name) : '—';
        });

        $show->field('cycle_id', 'Savings Cycle')->as(function () use ($record) {
            if (!$record->cycle) {
                return '—';
            }
            return $record->cycle->cycle_name
                ?: $record->cycle->title
                ?: "Cycle #{$record->cycle_id}";
        });

        $show->field('submitted_by_id', 'Submitted By')->as(function () use ($record) {
            if (!$record->submittedBy) {
                return '—';
            }
            $u = $record->submittedBy;
            return e($u->name ?: trim($u->first_name . ' ' . $u->last_name));
        });

        $show->field('status', 'Status');
        $show->field('submission_date', 'Submission Date');

        $show->field('is_processed', 'Is Processed')->as(fn($v) => $v ? 'Yes' : 'No');
        $show->field('processed_at',  'Processed At')->as(fn($v) => $v ?: '—');

        $show->field('processing_notes', 'Processing Log')->as(function ($v) {
            if (!$v) {
                return '—';
            }
            $log = json_decode($v, true);
            return is_array($log) ? implode("\n", $log) : $v;
        });

        $show->field('notes', 'Notes')->as(fn($v) => $v ?: '—');
        $show->field('created_at', 'Created At');

        // Member entries sub-table
        $show->memberEntries('Member Entries', function ($entries) {
            $entries->id('ID')->width(60);

            $entries->member_id('Member')->display(function () {
                $m = $this->member;
                return $m
                    ? e($m->name ?: trim($m->first_name . ' ' . $m->last_name))
                    : "<span class='text-muted'>User #{$this->member_id}</span>";
            });

            $entries->total_shares('Total Shares (UGX)')->display(
                fn($v) => number_format((float) $v, 2)
            );
            $entries->share_count('Share Count')->display(
                fn($v) => number_format((float) $v, 2)
            );
            $entries->total_loan_amount('Loan Amount (UGX)')->display(
                fn($v) => number_format((float) $v, 2)
            );
            $entries->loan_balance('Loan Balance (UGX)')->display(
                fn($v) => number_format((float) $v, 2)
            );
            $entries->total_social_fund('Social Fund (UGX)')->display(
                fn($v) => number_format((float) $v, 2)
            );
        });

        return $show;
    }
}
