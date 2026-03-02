<?php

namespace App\Admin\Controllers;

use App\Models\FfsTrainingSession;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FfsTrainingSessionController extends AdminController
{
    use IpScopeable;

    protected $title = 'FFS Training Sessions';

    protected function grid()
    {
        $grid = new Grid(new FfsTrainingSession());

        $grid->model()->with(['group', 'facilitator', 'coFacilitator'])->orderBy('session_date', 'desc');

        // IP Scoping
        $this->applyIpScope($grid);

        $grid->quickSearch('title', 'topic')->placeholder('Search by title or topic');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $this->addIpFilter($filter);

            $ipId = $this->getAdminIpId();
            $groupQuery = FfsGroup::orderBy('name');
            if ($ipId) $groupQuery->where('ip_id', $ipId);
            $filter->equal('group_id', 'Group')->select($groupQuery->pluck('name', 'id'));

            $filter->equal('facilitator_id', 'Facilitator')->select(
                User::where(function ($q) {
                    $q->where('user_type', 'Admin')
                      ->orWhere('user_type', 'Employee');
                })->orderBy('name')->pluck('name', 'id')
            );
            $filter->equal('session_type', 'Type')->select(FfsTrainingSession::getSessionTypes());
            $filter->equal('status', 'Status')->select(FfsTrainingSession::getStatuses());
            $filter->equal('report_status', 'Report Status')->select(FfsTrainingSession::getReportStatuses());
            $filter->between('session_date', 'Session Date')->date();
        });

        // Columns
        $grid->column('id', 'ID')->sortable()->hide();

        $grid->column('title', 'Title')->display(function ($title) {
            return "<strong>" . \Illuminate\Support\Str::limit($title, 30) . "</strong>";
        })->sortable();

        $grid->column('group.name', 'Group')->display(function ($name) {
            return $name ? "<span class='label label-info'>{$name}</span>" : '-';
        })->sortable();

        $grid->column('facilitator.name', 'Facilitator')->sortable();

        $grid->column('topic', 'Topic')->display(function ($topic) {
            return $topic ? \Illuminate\Support\Str::limit($topic, 25) : '-';
        });

        $grid->column('session_date', 'Date')->display(function ($date) {
            return date('M d, Y', strtotime($date));
        })->sortable();

        $grid->column('time_slot', 'Time')->display(function () {
            if (!$this->start_time) return '-';
            $start = date('H:i', strtotime($this->start_time));
            $end = $this->end_time ? date('H:i', strtotime($this->end_time)) : '';
            return $end ? "{$start} - {$end}" : $start;
        });

        $grid->column('session_type', 'Type')->label([
            'classroom' => 'primary',
            'field' => 'success',
            'demonstration' => 'warning',
            'workshop' => 'info',
        ])->sortable();

        $grid->column('attendance', 'Attendance')->display(function () {
            $present = $this->participants()->where('attendance_status', 'present')->count();
            $total = $this->participants()->count();
            $expected = $this->expected_participants;
            if ($total == 0 && $expected == 0) return '<span class="text-muted">-</span>';
            $color = $total > 0 && ($present / max($total, 1)) >= 0.75 ? 'success' : 'warning';
            return "<span class='label label-{$color}'>{$present}/{$total}" . ($expected > 0 ? " (exp: {$expected})" : '') . "</span>";
        });

        $grid->column('resolutions_count', 'Resolutions')->display(function () {
            $count = $this->resolutions()->count();
            return $count > 0 ? "<span class='label label-primary'>{$count}</span>" : '0';
        });

        $grid->column('status', 'Status')->label([
            'scheduled' => 'default',
            'ongoing' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
        ])->sortable();

        $grid->column('report_status', 'Report')->label([
            'draft' => 'warning',
            'submitted' => 'success',
        ])->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(FfsTrainingSession::findOrFail($id));

        $show->panel()->style('primary')->title('Training Session Details');

        $show->field('id', 'ID');
        $show->field('title', 'Title');
        $show->field('topic', 'Topic');
        $show->divider();

        $show->field('group_name', 'Group');
        $show->field('facilitator_name', 'Facilitator');
        $show->field('co_facilitator_id', 'Co-Facilitator')->as(function ($id) {
            $user = $id ? User::find($id) : null;
            return $user ? $user->name : '-';
        });
        $show->divider();

        $show->field('session_date', 'Date');
        $show->field('start_time', 'Start Time');
        $show->field('end_time', 'End Time');
        $show->field('venue', 'Venue');
        $show->field('session_type_text', 'Session Type');
        $show->divider();

        $show->field('status_text', 'Status');
        $show->field('report_status_text', 'Report Status');
        $show->field('expected_participants', 'Expected Participants');
        $show->field('actual_participants', 'Actual Participants');
        $show->divider();

        $show->field('description', 'Description')->unescape();
        $show->field('materials_used', 'Materials Used')->unescape();
        $show->field('notes', 'Session Notes')->unescape();
        $show->field('challenges', 'Challenges Encountered')->unescape();
        $show->field('recommendations', 'Recommendations')->unescape();
        $show->divider();

        $show->field('photo', 'Photo')->image();
        $show->field('photos', 'Additional Photos')->as(function ($photos) {
            if (!$photos || !is_array($photos)) return '-';
            return implode(' ', array_map(function ($p) {
                $url = url('storage/' . $p);
                return "<img src='{$url}' style='max-width:120px;margin:4px' />";
            }, $photos));
        })->unescape();

        $show->field('gps_latitude', 'GPS Latitude');
        $show->field('gps_longitude', 'GPS Longitude');
        $show->field('created_at', 'Created');

        // Show participants inline
        $show->participants('Session Participants', function ($participants) {
            $participants->disableCreateButton();
            $participants->disableExport();
            $participants->disableBatchActions();
            $participants->column('user.name', 'Participant');
            $participants->column('attendance_status', 'Status')->label([
                'present' => 'success', 'absent' => 'danger',
                'excused' => 'warning', 'late' => 'info',
            ]);
            $participants->column('remarks', 'Remarks');
        });

        // Show resolutions inline
        $show->resolutions('Resolutions / GAP', function ($resolutions) {
            $resolutions->disableCreateButton();
            $resolutions->disableExport();
            $resolutions->disableBatchActions();
            $resolutions->column('resolution', 'Resolution');
            $resolutions->column('gap_category', 'Category')->label('info');
            $resolutions->column('status', 'Status')->label([
                'pending' => 'default', 'in_progress' => 'warning',
                'completed' => 'success', 'cancelled' => 'danger',
            ]);
            $resolutions->column('target_date', 'Target Date');
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new FfsTrainingSession());

        $ipId = $this->getAdminIpId();

        // Auto-assign IP
        $this->addIpFieldToForm($form);

        // ── Session Identity ─────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(8)->text('title', 'Session Title')
                ->required()
                ->placeholder('e.g. Soil Preparation for Cassava Growing');
            $row->width(4)->text('topic', 'Topic')
                ->placeholder('e.g. Soil Management, Pest Control');
        });

        // ── Group & Facilitators ─────────────────────────────────────────
        $form->row(function ($row) use ($ipId) {
            $groupQuery = FfsGroup::where('status', 'Active')->orderBy('name');
            if ($ipId) $groupQuery->where('ip_id', $ipId);

            $row->width(4)->select('group_id', 'Primary Group')
                ->options($groupQuery->pluck('name', 'id'))
                ->required()
                ->help('Main group hosting this session');

            $facilitatorQuery = User::where(function ($q) {
                $q->where('user_type', 'Admin')
                  ->orWhere('user_type', 'Employee');
            })->orderBy('name');
            if ($ipId) $facilitatorQuery->where('ip_id', $ipId);

            $row->width(4)->select('facilitator_id', 'Lead Facilitator')
                ->options($facilitatorQuery->pluck('name', 'id'));
            $row->width(4)->select('co_facilitator_id', 'Co-Facilitator')
                ->options($facilitatorQuery->pluck('name', 'id'));
        });

        // ── Multi-group targeting ────────────────────────────────────────
        $form->row(function ($row) use ($ipId) {
            $allGroups = FfsGroup::where('status', 'Active')->orderBy('name');
            if ($ipId) $allGroups->where('ip_id', $ipId);

            $row->width(12)->multipleSelect('targetGroups', 'Additional Target Groups')
                ->options($allGroups->pluck('name', 'id'))
                ->help('Select extra groups that should attend. Primary group above is always included.');
        });

        // ── Schedule ─────────────────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(4)->date('session_date', 'Session Date')
                ->default(date('Y-m-d'))
                ->required();
            $row->width(4)->time('start_time', 'Start Time')->default('09:00:00');
            $row->width(4)->time('end_time', 'End Time')->default('12:00:00');
        });

        $form->row(function ($row) {
            $row->width(6)->text('venue', 'Venue/Location')
                ->placeholder('e.g. Community Hall, Under the mango tree');
            $row->width(3)->select('session_type', 'Session Type')
                ->options(FfsTrainingSession::getSessionTypes())
                ->default('classroom');
            $row->width(3)->number('expected_participants', 'Expected Participants')->default(0);
        });

        // ── Status & Reporting ───────────────────────────────────────────
        $form->row(function ($row) {
            $row->width(4)->select('status', 'Session Status')
                ->options(FfsTrainingSession::getStatuses())
                ->default('scheduled');
            $row->width(4)->select('report_status', 'Report Status')
                ->options(FfsTrainingSession::getReportStatuses())
                ->default('draft');
            $row->width(4)->number('actual_participants', 'Actual Participants')->default(0)
                ->help('Fill after session is completed');
        });

        // ── Rich content fields (Quill editors) ──────────────────────────
        $form->divider('Session Content & Report');

        $form->quill('description', 'Session Description / Agenda')
            ->placeholder('Describe the session agenda, objectives, and outline...');

        $form->quill('materials_used', 'Materials Used')
            ->placeholder('List training materials, handouts, tools used...');

        $form->quill('notes', 'Session Notes & Observations')
            ->placeholder('Key observations, outcomes, farmer reactions...');

        $form->quill('challenges', 'Challenges Encountered')
            ->placeholder('What difficulties were faced during this session?');

        $form->quill('recommendations', 'Recommendations & Follow-up')
            ->placeholder('Next steps, recommendations, action items...');

        // ── Media ────────────────────────────────────────────────────────
        $form->divider('Media & Location');

        $form->row(function ($row) {
            $row->width(6)->image('photo', 'Main Photo')->removable()->uniqueName();
            $row->width(6)->multipleImage('photos', 'Additional Photos')->removable()->uniqueName()
                ->help('Upload multiple session photos');
        });

        $form->row(function ($row) {
            $row->width(6)->decimal('gps_latitude', 'GPS Latitude')
                ->placeholder('e.g. 0.347596');
            $row->width(6)->decimal('gps_longitude', 'GPS Longitude')
                ->placeholder('e.g. 32.582520');
        });

        // ── Save logic ──────────────────────────────────────────────────
        $form->hidden('created_by_id');
        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->input('created_by_id', \Encore\Admin\Facades\Admin::user()->id);

                // Auto-assign IP from group if not set
                $ipId = $this->getAdminIpId();
                if ($ipId) {
                    $form->input('ip_id', $ipId);
                } elseif ($form->group_id) {
                    $group = FfsGroup::find($form->group_id);
                    if ($group && $group->ip_id) {
                        $form->input('ip_id', $group->ip_id);
                    }
                }
            }

            // Auto-set actual_participants from participants count when completing
            if ($form->status === 'completed' && empty($form->actual_participants)) {
                $count = $form->model()->participants()->count();
                if ($count > 0) {
                    $form->input('actual_participants', $count);
                }
            }

            // Track report submission
            if ($form->report_status === 'submitted' && !$form->model()->report_submitted_at) {
                $form->input('report_submitted_at', now());
                $form->input('submitted_by_id', \Encore\Admin\Facades\Admin::user()->id);
            }
        });

        $form->disableViewCheck();
        $form->disableEditingCheck();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
