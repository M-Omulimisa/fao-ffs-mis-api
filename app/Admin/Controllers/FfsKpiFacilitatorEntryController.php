<?php

namespace App\Admin\Controllers;

use App\Models\FfsKpiFacilitatorEntry;
use App\Models\FfsKpiIndicator;
use App\Models\FfsKpiIpEntry;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;

/**
 * FFS KPI Facilitator Entries — Manual KPI tracking per session/facilitator.
 *
 * Access:
 *   Super Admin → all IPs, full CRUD
 *   IP Admin    → own IP only, full CRUD
 *   (Facilitators do NOT enter data directly — IP admins enter on their behalf)
 */
class FfsKpiFacilitatorEntryController extends AdminController
{
    use IpScopeable;

    protected $title = 'Facilitator KPI Entries';

    // ── Grid ──────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new FfsKpiFacilitatorEntry());
        $ipId = $this->getAdminIpId();

        if ($ipId !== null) {
            $grid->model()->where('ip_id', $ipId);
        }

        $grid->model()
            ->with(['indicator', 'ip', 'facilitator', 'group'])
            ->orderBy('session_date', 'desc')
            ->orderBy('id', 'desc');

        $grid->disableBatchActions();

        // ── Columns ───────────────────────────────────────────────────────

        $grid->column('id', 'ID')->sortable()->hide();

        if ($this->isSuperAdmin()) {
            $grid->column('ip.name', 'Partner')->display(function ($name) {
                return $name ? "<strong>" . e($name) . "</strong>" : '—';
            });
        }

        $grid->column('output_badge', 'Output')->display(function () {
            $n      = $this->indicator->output_number ?? '?';
            $colors = [1 => '#05179F', 2 => '#388e3c', 3 => '#e65100'];
            $color  = $colors[$n] ?? '#607d8b';
            return "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:700;'>Output {$n}</span>";
        });

        $grid->column('indicator.indicator_name', 'KPI Indicator')->display(function ($name) {
            return $name ? e($name) : '—';
        });

        $grid->column('disaggregation', 'Disaggregation')->display(function ($v) {
            return $v
                ? "<span style='display:inline-block;padding:2px 8px;background:#eceff1;color:#37474f;font-size:11px;font-weight:600;'>" . e($v) . "</span>"
                : '—';
        });

        $grid->column('facilitator_name', 'Facilitator')->display(function () {
            if ($this->facilitator) {
                return e(trim(($this->facilitator->first_name ?? '') . ' ' . ($this->facilitator->last_name ?? '')));
            }
            return '<span style="color:#999;">—</span>';
        });

        $grid->column('group.name', 'FFS Group')->display(function ($name) {
            return $name ? e($name) : '<span style="color:#999;">—</span>';
        });

        $grid->column('district', 'District')->display(fn($v) => $v ? e($v) : '—');

        $grid->column('session_date', 'Session Date')->display(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        })->sortable();

        $grid->column('value', 'Value')->display(function ($v) {
            $color = (float)$v > 0 ? '#05179F' : '#999';
            return "<strong style='font-size:14px;color:{$color};'>" . number_format((float)$v, 0) . "</strong>";
        })->sortable();

        $grid->column('comments', 'Comments')->display(function ($v) {
            return $v ? '<span title="' . e($v) . '">' . e(mb_strimwidth($v, 0, 50, '…')) . '</span>' : '—';
        })->hide();

        $grid->column('created_at', 'Recorded')->display(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        })->sortable()->hide();

        // ── Filters ───────────────────────────────────────────────────────

        $grid->filter(function ($filter) use ($ipId) {
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) {
                if ($this->isSuperAdmin()) {
                    $filter->equal('ip_id', 'Implementing Partner')
                        ->select(ImplementingPartner::getDropdownOptions());
                }
                $filter->between('session_date', 'Session Date Range')->date();
            });

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('indicator_id', 'KPI Indicator')->select(
                    FfsKpiIndicator::where('type', 'facilitator')
                        ->orderBy('sort_order')
                        ->pluck('indicator_name', 'id')
                        ->toArray()
                );
                $filter->equal('disaggregation', 'Disaggregation')->select([
                    'Female' => 'Female',
                    'Male'   => 'Male',
                    'Youth'  => 'Youth',
                    'PWD'    => 'PWD',
                ]);
                $filter->equal('district', 'District')->select($this->northernUgandaDistricts());
            });

            $filter->column(1 / 3, function ($filter) use ($ipId) {
                // Facilitator filter
                $facIds = DB::table('ffs_groups')
                    ->whereNotNull('facilitator_id')
                    ->distinct()
                    ->pluck('facilitator_id')
                    ->merge(DB::table('users')->whereNotNull('facilitator_start_date')->pluck('id'))
                    ->unique();
                $facilitators = User::whereIn('id', $facIds)
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->get()
                    ->mapWithKeys(fn($u) => [$u->id => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))])
                    ->toArray();
                $filter->equal('facilitator_id', 'Facilitator')->select($facilitators);

                // Group filter
                $groups = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->where('status', 'Active')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
                $filter->equal('group_id', 'FFS Group')->select($groups);
            });
        });

        // ── Export ────────────────────────────────────────────────────────

        $grid->export(function ($export) {
            $export->filename('FFS_KPI_Facilitator_' . date('Y-m-d'));
        });

        return $grid;
    }

    // ── Detail ────────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $entry = FfsKpiFacilitatorEntry::with(['ip', 'indicator', 'facilitator', 'group'])->findOrFail($id);
        $show  = new Show($entry);

        $indicatorName = $entry->indicator->indicator_name ?? '—';
        $show->panel()
            ->style('success')
            ->title('Facilitator KPI — ' . $indicatorName . ' (' . $entry->disaggregation . ')');

        $show->divider('📋 Entry Details');
        $show->field('ip.name', 'Implementing Partner');
        $show->field('output_and_indicator', 'Output & Indicator')->as(function () use ($entry) {
            $n = $entry->indicator->output_number ?? '?';
            return "Output {$n} — " . ($entry->indicator->indicator_name ?? '—');
        });
        $show->field('disaggregation', 'Disaggregation');

        $show->divider('👤 Facilitator & Location');
        $show->field('facilitator_name_display', 'Facilitator')->as(function () use ($entry) {
            if ($entry->facilitator) {
                return trim(($entry->facilitator->first_name ?? '') . ' ' . ($entry->facilitator->last_name ?? ''));
            }
            return '—';
        });
        $show->field('group.name', 'FFS Group')->as(fn($v) => $v ?: '—');
        $show->field('district', 'District')->as(fn($v) => $v ?: '—');
        $show->field('sub_county', 'Sub-County')->as(fn($v) => $v ?: '—');

        $show->divider('📊 Recorded Value');
        $show->field('session_date', 'Session Date')->as(fn($d) => $d ? date('d M Y', strtotime($d)) : '—');
        $show->field('value_display', 'Value')->unescape()->as(function () use ($entry) {
            $v = (float) $entry->value;
            return "<span style='font-size:24px;font-weight:800;color:#05179F;'>" . number_format($v, 0) . "</span>";
        });
        $show->field('target_reference', 'Indicator Target')->as(function () use ($entry) {
            return $entry->indicator ? number_format($entry->indicator->default_target, 0) : '—';
        });

        $show->divider('📝 Notes');
        $show->field('comments', 'Comments')->as(fn($v) => $v ?: '—');

        $show->divider('🕒 Audit');
        $show->field('created_at', 'Recorded At')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');
        $show->field('updated_at', 'Last Updated')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');

        return $show;
    }

    // ── Form ──────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new FfsKpiFacilitatorEntry());
        $ipId = $this->getAdminIpId();

        // IP field
        $this->addIpFieldToForm($form);

        // ── Facilitator ───────────────────────────────────────────────────
        $facIds = DB::table('ffs_groups')
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id')
            ->merge(DB::table('users')->whereNotNull('facilitator_start_date')->pluck('id'))
            ->unique();

        $facilitators = User::whereIn('id', $facIds)
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn($u) => [
                $u->id => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))
            ])
            ->toArray();

        $form->select('facilitator_id', 'Facilitator')
            ->options($facilitators)
            ->help('Select the facilitator who collected this data');

        // ── KPI Indicator (facilitator type only) ─────────────────────────
        $indicators = FfsKpiIndicator::where('type', 'facilitator')->orderBy('sort_order')->get();
        $indicatorOptions = $indicators->mapWithKeys(fn($i) => [
            $i->id => "Output {$i->output_number} — {$i->indicator_name}"
        ])->toArray();

        $form->select('indicator_id', 'KPI Indicator')
            ->options($indicatorOptions)
            ->rules('required');

        // ── Disaggregation — all possible values pre-loaded; JS shows a hint ─
        $form->select('disaggregation', 'Disaggregation')
            ->options([
                'Female' => 'Female',
                'Male'   => 'Male',
                'Youth'  => 'Youth',
                'PWD'    => 'PWD',
            ])
            ->rules('required')
            ->help('<span id="kpi-fac-disagg-hint" style="color:#1565c0;font-weight:600;font-size:12px;"></span>');

        // ── Location ──────────────────────────────────────────────────────
        $form->select('district', 'District')
            ->options($this->northernUgandaDistricts());
        $form->text('sub_county', 'Sub-County');

        $groups = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->where('status', 'Active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
        $form->select('group_id', 'FFS Group')->options($groups);

        // ── Session ───────────────────────────────────────────────────────
        $form->date('session_date', 'Session Date')
            ->default(date('Y-m-d'))
            ->rules('required');

        $form->decimal('value', 'Value')
            ->rules('required|numeric|min:0')
            ->help('The count / number recorded for this indicator in this session');

        $form->textarea('comments', 'Comments / Notes');

        // ── Saving Hook ───────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            if (!$form->model()->exists) {
                $form->model()->created_by = Admin::user()->id ?? null;
            }
        });

        // ── JavaScript: Disaggregation updates ───────────────────────────
        $indicatorJsonData = FfsKpiIndicator::asJsData('facilitator');

        Admin::script("window._kpiFacIndicators = {$indicatorJsonData};");
        Admin::script(<<<'JS'
$(function() {
    function updateFacHint(indicatorId) {
        var data = window._kpiFacIndicators[indicatorId];
        if (data && data.disaggregations && data.disaggregations.length) {
            var hint = '✔ Valid for this indicator: ' + data.disaggregations.join(' · ');
            $('#kpi-fac-disagg-hint').text(hint);
        } else {
            $('#kpi-fac-disagg-hint').text('');
        }
    }

    var initId = $('[name="indicator_id"]').val();
    if (initId) updateFacHint(initId);

    $(document).on('change', '[name="indicator_id"]', function() {
        updateFacHint($(this).val());
    });
});
JS);

        return $form;
    }
}
