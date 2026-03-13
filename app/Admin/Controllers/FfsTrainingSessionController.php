<?php

namespace App\Admin\Controllers;

use App\Models\FfsTrainingSession;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

/**
 * FfsTrainingSessionController — manages FFS training sessions.
 *
 * Access tiers:
 *   Super Admin  → all sessions (any IP)
 *   IP Manager   → sessions whose ip_id matches their own
 *   Facilitator  → only sessions where they are the lead or co-facilitator
 */
class FfsTrainingSessionController extends AdminController
{
    use IpScopeable;

    protected $title = 'FFS Training Sessions';

    // ─────────────────────────────────────────────────────────────────────────
    // GRID
    // ─────────────────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new FfsTrainingSession());

        $grid->model()->with(['group', 'facilitator', 'coFacilitator'])->orderBy('session_date', 'desc');

        $currentAdmin  = Admin::user();
        $isSuperAdmin  = $this->isSuperAdmin();
        $isFacilitator = !$isSuperAdmin && $currentAdmin
            && $this->userHasRoleSlug($currentAdmin, 'facilitator');
        $ipId = $this->getAdminIpId();

        // ── Three-tier access ──────────────────────────────────────────────
        if ($isFacilitator) {
            // Facilitators see only sessions they lead or co-facilitate
            $grid->model()->where(function ($q) use ($currentAdmin) {
                $q->where('facilitator_id', $currentAdmin->id)
                  ->orWhere('co_facilitator_id', $currentAdmin->id);
            });
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
            });
        } elseif (!$isSuperAdmin && $ipId) {
            $grid->model()->where('ip_id', $ipId);
        }

        // ── Quick search ──────────────────────────────────────────────────
        $grid->quickSearch('title', 'topic')->placeholder('Search title or topic…');

        // ── Filters ───────────────────────────────────────────────────────
        $grid->filter(function ($filter) use ($ipId, $isSuperAdmin, $isFacilitator, $currentAdmin) {
            $filter->disableIdFilter();

            if ($isSuperAdmin) {
                $filter->equal('ip_id', 'Implementing Partner')
                    ->select(ImplementingPartner::getDropdownOptions());
            }

            $groupQuery = FfsGroup::orderBy('name');
            if ($ipId) {
                $groupQuery->where('ip_id', $ipId);
            }
            if ($isFacilitator) {
                $groupQuery->whereHas('sessions', fn($q) =>
                    $q->where('facilitator_id', $currentAdmin->id)
                      ->orWhere('co_facilitator_id', $currentAdmin->id)
                );
            }
            $filter->equal('group_id', 'Group')->select($groupQuery->pluck('name', 'id'));

            if (!$isFacilitator) {
                $facilitatorQuery = $this->facilitatorUserQuery($ipId);
                $filter->equal('facilitator_id', 'Facilitator')
                    ->select($facilitatorQuery->pluck('name', 'id'));
            }

            $filter->equal('session_type', 'Type')->select(FfsTrainingSession::getSessionTypes());
            $filter->equal('status', 'Status')->select(FfsTrainingSession::getStatuses());
            $filter->equal('report_status', 'Report Status')->select(FfsTrainingSession::getReportStatuses());
            $filter->between('session_date', 'Session Date')->date();
        });

        // ── Columns ───────────────────────────────────────────────────────
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('title', 'Title')->display(function ($title) {
            $short = e(\Illuminate\Support\Str::limit($title, 35));
            return "<strong>{$short}</strong>";
        })->sortable();

        $grid->column('group.name', 'Group')->display(function ($name) {
            return $name
                ? '<span class="label label-info">' . e($name) . '</span>'
                : '<span class="text-muted">—</span>';
        });

        $grid->column('facilitator.name', 'Facilitator')->display(function ($name) {
            return $name ? e($name) : '<span class="text-muted">—</span>';
        })->sortable();

        $grid->column('session_date', 'Date')->display(function ($date) {
            return $date ? date('d M Y', strtotime($date)) : '—';
        })->sortable();

        $grid->column('session_type', 'Type')->label([
            'classroom'     => 'primary',
            'field'         => 'success',
            'demonstration' => 'warning',
            'workshop'      => 'info',
        ])->sortable();

        $grid->column('attendance', 'Attendance')->display(function () {
            $present  = $this->participants()->whereIn('attendance_status', ['present', 'late'])->count();
            $total    = $this->participants()->count();
            $expected = (int) $this->expected_participants;
            if ($total === 0 && $expected === 0) return '<span class="text-muted">—</span>';
            $rate   = $total > 0 ? ($present / $total) : 0;
            $colour = $rate >= 0.75 ? 'success' : ($rate >= 0.5 ? 'warning' : 'danger');
            $exp    = $expected > 0 ? " / {$expected}" : '';
            return "<span class='label label-{$colour}'>{$present}{$exp}</span>";
        });

        $grid->column('resolutions_count', 'GAPs')->display(function () {
            $count = $this->resolutions()->count();
            return $count > 0
                ? "<span class='label label-primary'>{$count}</span>"
                : '<span class="text-muted">0</span>';
        });

        $grid->column('status', 'Status')->label([
            'scheduled' => 'default',
            'ongoing'   => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
        ])->sortable();

        $grid->column('report_status', 'Report')->label([
            'draft'     => 'warning',
            'submitted' => 'success',
        ])->sortable();

        if ($isSuperAdmin) {
            $grid->column('ip_id', 'Partner')->display(function ($id) {
                if (!$id) return '—';
                $ip = ImplementingPartner::find($id);
                return $ip ? e($ip->short_name ?: $ip->name) : "IP #{$id}";
            });
        }

        return $grid;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $record = FfsTrainingSession::findOrFail($id);

        // Access control
        if (!$this->isSuperAdmin()) {
            $currentAdmin  = Admin::user();
            $isFacilitator = $this->userHasRoleSlug($currentAdmin, 'facilitator');

            if ($isFacilitator) {
                if (
                    (int) $record->facilitator_id    !== (int) $currentAdmin->id &&
                    (int) $record->co_facilitator_id !== (int) $currentAdmin->id
                ) {
                    return $this->denyIpAccess();
                }
            } elseif (!$this->verifyIpAccess($record)) {
                return $this->denyIpAccess();
            }
        }

        $show = new Show($record);
        $show->panel()->style('primary')->title('Training Session');

        $show->field('id',    'ID');
        $show->field('title', 'Title');
        $show->field('topic', 'Topic');
        $show->divider();

        $show->field('group_name',      'Group');
        $show->field('facilitator_name','Lead Facilitator');
        $show->field('co_facilitator_id', 'Co-Facilitator')->as(function ($id) {
            if (!$id) return '—';
            $u = User::find($id);
            return $u ? $u->name : "User #{$id}";
        });
        $show->divider();

        $show->field('session_date',   'Date');
        $show->field('start_time',     'Start Time');
        $show->field('end_time',       'End Time');
        $show->field('venue',          'Venue');
        $show->field('session_type_text', 'Session Type');
        $show->divider();

        $show->field('ip_id', 'Implementing Partner')->as(function ($id) {
            $ip = ImplementingPartner::find($id);
            return $ip ? "{$ip->name} ({$ip->short_name})" : '—';
        });
        $show->field('status_text',        'Status');
        $show->field('report_status_text', 'Report Status');
        $show->field('expected_participants', 'Expected Participants');
        $show->field('actual_participants',   'Actual Participants');
        $show->divider();

        $show->field('description',      'Session Description / Agenda')->unescape();
        $show->field('materials_used',   'Materials Used')->unescape();
        $show->field('notes',            'Session Notes')->unescape();
        $show->field('challenges',       'Challenges')->unescape();
        $show->field('recommendations',  'Recommendations')->unescape();
        $show->divider();

        $show->field('photo',  'Main Photo')->image();
        $show->field('photos', 'Additional Photos')->as(function ($photos) {
            if (!$photos || !is_array($photos)) return '—';
            return implode(' ', array_map(function ($p) {
                $url = url('storage/' . $p);
                return "<img src='{$url}' style='max-width:120px;margin:4px' />";
            }, $photos));
        })->unescape();

        $show->field('gps_latitude',  'GPS Latitude');
        $show->field('gps_longitude', 'GPS Longitude');
        $show->field('created_at',    'Created');

        // ── Inline participants ────────────────────────────────────────────
        $show->participants('Session Participants', function ($participants) {
            $participants->disableCreateButton();
            $participants->disableExport();
            $participants->disableBatchActions();
            $participants->column('user.name', 'Participant');
            $participants->column('attendance_status', 'Status')->label([
                'present' => 'success',
                'absent'  => 'danger',
                'excused' => 'warning',
                'late'    => 'info',
                'pending' => 'default',
            ]);
            $participants->column('remarks', 'Remarks');
        });

        // ── Inline resolutions ────────────────────────────────────────────
        $show->resolutions('Resolutions / GAP', function ($resolutions) {
            $resolutions->disableCreateButton();
            $resolutions->disableExport();
            $resolutions->disableBatchActions();
            $resolutions->column('resolution', 'Resolution');
            $resolutions->column('gap_category', 'Category')->label('info');
            $resolutions->column('status', 'Status')->label([
                'pending'     => 'default',
                'in_progress' => 'warning',
                'completed'   => 'success',
                'cancelled'   => 'danger',
            ]);
            $resolutions->column('target_date', 'Target Date');
        });

        return $show;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FORM
    // ─────────────────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new FfsTrainingSession());

        $ipId = $this->getAdminIpId();

        // ── Implementing Partner ──────────────────────────────────────────
        $this->addIpFieldToForm($form);

        // ── Session Identity ──────────────────────────────────────────────
        $form->divider('Session Details');

        $form->row(function ($row) {
            $row->width(8)->text('title', 'Session Title')
                ->required()
                ->placeholder('e.g. Soil Preparation for Cassava Growing');
            $row->width(4)->text('topic', 'Topic / Module')
                ->placeholder('e.g. Soil Management, Pest Control');
        });

        // ── Group & Facilitators ──────────────────────────────────────────
        $form->row(function ($row) use ($ipId) {
            $groupQuery = FfsGroup::where('status', 'Active')->orderBy('name');
            if ($ipId) {
                $groupQuery->where('ip_id', $ipId);
            }

            $row->width(4)->select('group_id', 'Primary Group')
                ->options($groupQuery->pluck('name', 'id'))
                ->required()
                ->help('Main group hosting this session');

            $facilitatorQuery = $this->facilitatorUserQuery($ipId);

            $row->width(4)->select('facilitator_id', 'Lead Facilitator')
                ->options($facilitatorQuery->get()->pluck('name', 'id'))
                ->help('Facilitator leading this session');

            $row->width(4)->select('co_facilitator_id', 'Co-Facilitator')
                ->options($facilitatorQuery->get()->pluck('name', 'id'))
                ->help('Optional second facilitator');
        });

        // ── Additional target groups ──────────────────────────────────────
        $form->row(function ($row) use ($ipId) {
            $allGroups = FfsGroup::where('status', 'Active')->orderBy('name');
            if ($ipId) {
                $allGroups->where('ip_id', $ipId);
            }
            $row->width(12)->multipleSelect('targetGroups', 'Additional Target Groups')
                ->options($allGroups->pluck('name', 'id'))
                ->help('Select extra groups attending. The primary group above is always included.');
        });

        // ── Schedule & Logistics ──────────────────────────────────────────
        $form->divider('Schedule & Logistics');

        $form->row(function ($row) {
            $row->width(4)->date('session_date', 'Session Date')
                ->default(date('Y-m-d'))
                ->required();
            $row->width(4)->time('start_time', 'Start Time')->default('09:00:00');
            $row->width(4)->time('end_time',   'End Time')->default('12:00:00');
        });

        $form->row(function ($row) {
            $row->width(6)->text('venue', 'Venue / Location')
                ->placeholder('e.g. Community Hall, Under the mango tree');
            $row->width(3)->select('session_type', 'Session Type')
                ->options(FfsTrainingSession::getSessionTypes())
                ->default('classroom');
            $row->width(3)->number('expected_participants', 'Expected Count')->default(0);
        });

        // ── Status & Reporting ────────────────────────────────────────────
        $form->divider('Status & Reporting');

        $form->row(function ($row) {
            $row->width(4)->select('status', 'Session Status')
                ->options(FfsTrainingSession::getStatuses())
                ->default('scheduled');
            $row->width(4)->select('report_status', 'Report Status')
                ->options(FfsTrainingSession::getReportStatuses())
                ->default('draft');
            $row->width(4)->number('actual_participants', 'Actual Participants')->default(0)
                ->help('Auto-filled from attendance records when status = Completed');
        });

        // ── Session Content ───────────────────────────────────────────────
        $form->divider('Session Content & Report');

        $form->quill('description', 'Description / Agenda')
            ->placeholder('Describe session agenda, objectives, and outline…');

        $form->quill('materials_used', 'Materials Used')
            ->placeholder('List training materials, handouts, tools used…');

        $form->quill('notes', 'Session Notes & Observations')
            ->placeholder('Key observations, outcomes, farmer reactions…');

        $form->quill('challenges', 'Challenges Encountered')
            ->placeholder('What difficulties were faced during this session?');

        $form->quill('recommendations', 'Recommendations & Follow-up')
            ->placeholder('Next steps, recommendations, action items…');

        // ── Media & Location ──────────────────────────────────────────────
        $form->divider('Media & GPS');

        $form->row(function ($row) {
            $row->width(6)->image('photo', 'Main Photo')->removable()->uniqueName();
            $row->width(6)->multipleImage('photos', 'Additional Photos')
                ->removable()
                ->uniqueName()
                ->help('Upload multiple session photos');
        });

        $form->row(function ($row) {
            $row->width(6)->decimal('gps_latitude',  'GPS Latitude')->placeholder('e.g. 2.7809');
            $row->width(6)->decimal('gps_longitude', 'GPS Longitude')->placeholder('e.g. 34.1531');
        });

        // ── Save logic ────────────────────────────────────────────────────
        $form->hidden('created_by_id');
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->input('created_by_id', Admin::user()->id);

                // Auto-assign ip_id from group if not already set
                if (empty($form->ip_id) && $form->group_id) {
                    $group = FfsGroup::find($form->group_id);
                    if ($group && $group->ip_id) {
                        $form->input('ip_id', $group->ip_id);
                    }
                }
            }

            // Auto-update actual_participants from attendance
            if ($form->status === 'completed' && empty($form->actual_participants)) {
                $present = $form->model()->participants()
                    ->whereIn('attendance_status', ['present', 'late'])
                    ->count();
                if ($present > 0) {
                    $form->input('actual_participants', $present);
                }
            }

            // Track when report is submitted
            if ($form->report_status === 'submitted' && !$form->model()->report_submitted_at) {
                $form->input('report_submitted_at', now()->toDateTimeString());
                $form->input('submitted_by_id', Admin::user()->id);
            }
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a query for facilitator users scoped to an IP.
     * Facilitators are Customer-type users linked to ffs_groups OR
     * who have a facilitator_start_date set.
     */
    private function facilitatorUserQuery(?int $ipId)
    {
        $fromGroups = DB::table('ffs_groups')
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id');

        $fromDate = DB::table('users')
            ->whereNotNull('facilitator_start_date')
            ->pluck('id');

        $facilitatorIds = $fromGroups->merge($fromDate)->unique()->values();

        $query = User::whereIn('id', $facilitatorIds)->orderBy('name');
        if ($ipId) {
            $query->where('ip_id', $ipId);
        }
        return $query;
    }
}
