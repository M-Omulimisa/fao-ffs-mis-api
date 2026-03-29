<?php

namespace App\Admin\Controllers;

use App\Models\FfsKpiIpEntry;
use App\Models\FfsKpiIndicator;
use App\Models\FfsGroup;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;

/**
 * FFS KPI IP Entries — Manual monthly KPI tracking for Implementing Partners.
 *
 * Access:
 *   Super Admin → all IPs, full CRUD
 *   IP Admin    → own IP only, full CRUD
 */
class FfsKpiIpController extends AdminController
{
    use IpScopeable;

    protected $title = 'IP KPI Entries';

    // ── Grid ──────────────────────────────────────────────────────────────

    protected function grid()
    {
        $grid = new Grid(new FfsKpiIpEntry());
        $ipId = $this->getAdminIpId();

        // Scope to current IP (null = super admin sees everything)
        if ($ipId !== null) {
            $grid->model()->where('ip_id', $ipId);
        }

        $grid->model()
            ->with(['indicator', 'ip', 'group'])
            ->orderBy('year', 'desc')
            ->orderBy('id', 'desc');

        $grid->disableBatchActions();

        // ── Columns ───────────────────────────────────────────────────────

        $grid->column('id', 'ID')->sortable()->hide();

        // IP column — only meaningful for super admin
        if ($this->isSuperAdmin()) {
            $grid->column('ip.name', 'Partner')->display(function ($name) {
                return $name
                    ? "<strong>" . e($name) . "</strong>"
                    : '<span style="color:#999;">—</span>';
            });
        }

        $grid->column('output_badge', 'Output')->display(function () {
            $n = $this->indicator->output_number ?? '?';
            $colors = [1 => '#05179F', 2 => '#388e3c', 3 => '#e65100'];
            $color  = $colors[$n] ?? '#607d8b';
            return "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:700;'>Output {$n}</span>";
        });

        $grid->column('indicator.indicator_name', 'KPI Indicator')->display(function ($name) {
            return $name ? "<span>" . e($name) . "</span>" : '—';
        });

        $grid->column('disaggregation', 'Disaggregation')->display(function ($v) {
            return $v
                ? "<span style='display:inline-block;padding:2px 8px;background:#eceff1;color:#37474f;font-size:11px;font-weight:600;'>" . e($v) . "</span>"
                : '—';
        });

        $grid->column('location_display', 'Location')->display(function () {
            return e($this->location_display);
        });

        $grid->column('year', 'Year')->sortable();

        $grid->column('target', 'Target')->display(function ($v) {
            return "<span style='font-weight:600;'>" . number_format($v, 0) . "</span>";
        })->sortable();

        $grid->column('overall', 'Overall')->display(function () {
            $overall = $this->overall;
            $target  = (float) $this->target;
            if ($target > 0) {
                $pct = $overall / $target * 100;
                $color = $pct >= 100 ? '#4caf50' : ($pct >= 70 ? '#ff9800' : '#f44336');
            } else {
                $color = '#607d8b';
            }
            return "<strong style='color:{$color};'>" . number_format($overall, 0) . "</strong>";
        });

        $grid->column('performance_pct', 'Performance')->display(function () {
            $pct   = $this->performance_pct;
            $label = FfsKpiIpEntry::performanceLabel($pct);
            $color = FfsKpiIpEntry::performanceColor($pct);
            return "<span style='display:inline-block;padding:2px 10px;background:{$color};color:#fff;font-size:11px;font-weight:600;'>{$pct}% · {$label}</span>";
        });

        $grid->column('variance', 'Variance')->display(function () {
            $v = $this->variance;
            if ($v > 0) {
                return "<span style='color:#f44336;font-weight:600;'>-" . number_format($v, 0) . "</span>";
            }
            return "<span style='color:#4caf50;font-weight:600;'>+" . number_format(abs($v), 0) . "</span>";
        });

        $grid->column('created_at', 'Created')->display(function ($d) {
            return $d ? date('d M Y', strtotime($d)) : '—';
        })->sortable()->hide();

        // ── Filters ───────────────────────────────────────────────────────

        $grid->filter(function ($filter) use ($ipId) {
            $filter->disableIdFilter();

            $filter->column(1 / 3, function ($filter) use ($ipId) {
                // IP filter — super admin only
                if ($this->isSuperAdmin()) {
                    $filter->equal('ip_id', 'Implementing Partner')
                        ->select(ImplementingPartner::getDropdownOptions());
                }
                $filter->equal('year', 'Year')->select(array_combine(
                    range(date('Y'), date('Y') - 3),
                    range(date('Y'), date('Y') - 3)
                ));
            });

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('indicator_id', 'KPI Indicator')->select(
                    FfsKpiIndicator::where('type', 'ip')
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn($i) => [$i->id => "Output {$i->output_number} — {$i->indicator_name}"])
                        ->toArray()
                );
            });

            $filter->column(1 / 3, function ($filter) {
                $filter->equal('district', 'District')->select($this->northernUgandaDistricts());
                $filter->equal('disaggregation', 'Disaggregation')->select([
                    'Total'  => 'Total',
                    'Female' => 'Female',
                    'Male'   => 'Male',
                    'Youth'  => 'Youth',
                    'New'    => 'New',
                    'Old'    => 'Old',
                    'Number' => 'Number',
                    'N/A'    => 'N/A',
                ]);
            });
        });

        // ── Export ────────────────────────────────────────────────────────

        $grid->export(function ($export) {
            $export->filename('FFS_KPI_IP_' . date('Y-m-d'));
        });

        return $grid;
    }

    // ── Detail ────────────────────────────────────────────────────────────

    protected function detail($id)
    {
        $entry = FfsKpiIpEntry::with(['ip', 'indicator', 'group'])->findOrFail($id);
        $show  = new Show($entry);

        $show->panel()
            ->style('primary')
            ->title('KPI Entry — ' . ($entry->indicator->indicator_name ?? '—') . ' (' . $entry->disaggregation . ')');

        // Section: Session Info
        $show->divider('📋 KPI Entry Details');
        $show->field('ip.name', 'Implementing Partner');
        $show->field('output_badge_text', 'Output')->as(function () use ($entry) {
            $n = $entry->indicator->output_number ?? '?';
            return "Output {$n} — " . ($entry->indicator->output_name ?? '—');
        });
        $show->field('indicator.indicator_name', 'KPI Indicator');
        $show->field('disaggregation', 'Disaggregation');
        $show->field('year', 'Reporting Year');
        $show->field('target', 'Annual Target')->as(fn($v) => number_format($v, 0));

        // Section: Location
        $show->divider('📍 Location');
        $show->field('district', 'District')->as(fn($v) => $v ?: '—');
        $show->field('sub_county', 'Sub-County')->as(fn($v) => $v ?: '—');
        $show->field('group.name', 'FFS Group')->as(fn($v) => $v ?: '—');
        $show->field('institution', 'Institution')->as(fn($v) => $v ?: '—');
        $show->field('location_type', 'Location Type')->as(fn($v) => $v ?: '—');

        // Section: Monthly Actuals
        $show->divider('📅 Monthly Actuals (Jan – Dec)');
        $months = [
            'jan' => 'January',   'feb' => 'February',  'mar' => 'March',
            'apr' => 'April',     'may' => 'May',        'jun' => 'June',
            'jul' => 'July',      'aug' => 'August',     'sep' => 'September',
            'oct' => 'October',   'nov' => 'November',   'dec' => 'December',
        ];
        foreach ($months as $col => $label) {
            $show->field($col, $label)->as(fn($v) => $v !== null ? number_format($v, 0) : '—');
        }

        // Section: Computed Summary
        $show->divider('📊 Computed Summary');
        $show->field('overall_html', 'Overall Achieved')->unescape()->as(function () use ($entry) {
            $overall = $entry->overall;
            $target  = (float) $entry->target;
            $color   = $target > 0 && $overall >= $target ? '#4caf50' : ($overall > 0 ? '#ff9800' : '#f44336');
            return "<span style='font-size:20px;font-weight:800;color:{$color};'>" . number_format($overall, 0) . "</span>";
        });
        $show->field('performance_html', 'Performance')->unescape()->as(function () use ($entry) {
            $pct   = $entry->performance_pct;
            $label = FfsKpiIpEntry::performanceLabel($pct);
            $color = FfsKpiIpEntry::performanceColor($pct);
            return "<span style='font-size:16px;font-weight:800;color:{$color};'>{$pct}%</span> <span style='color:{$color};font-weight:600;'>({$label})</span>";
        });
        $show->field('variance_html', 'Variance (Target − Overall)')->unescape()->as(function () use ($entry) {
            $v = $entry->variance;
            if ($v > 0) {
                return "<span style='color:#f44336;font-weight:700;'>−" . number_format($v, 0) . " (shortfall)</span>";
            }
            return "<span style='color:#4caf50;font-weight:700;'>+" . number_format(abs($v), 0) . " (met/exceeded)</span>";
        });

        // Audit
        $show->divider('🕒 Audit');
        $show->field('comments', 'Comments')->as(fn($v) => $v ?: '—');
        $show->field('created_at', 'Created At')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');
        $show->field('updated_at', 'Last Updated')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');

        return $show;
    }

    // ── Form ──────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new FfsKpiIpEntry());
        $ipId = $this->getAdminIpId();

        // ── KPI Identification ────────────────────────────────────────────

        // Implementing Partner (standalone — handled by trait)
        $this->addIpFieldToForm($form);

        // KPI Indicator
        $indicators       = FfsKpiIndicator::where('type', 'ip')->orderBy('sort_order')->get();
        $indicatorOptions = $indicators->mapWithKeys(fn($i) => [
            $i->id => "Output {$i->output_number} — {$i->indicator_name}",
        ])->toArray();

        $form->select('indicator_id', 'KPI Indicator')
            ->options($indicatorOptions)
            ->rules('required');

        // Disaggregation
        $form->select('disaggregation', 'Disaggregation')
            ->options([
                'Total'  => 'Total',
                'Female' => 'Female',
                'Male'   => 'Male',
                'Youth'  => 'Youth',
                'PWD'    => 'PWD',
                'New'    => 'New',
                'Old'    => 'Old',
                'Number' => 'Number',
                'N/A'    => 'N/A',
            ])
            ->rules('required')
            ->help('<span id="kpi-disagg-hint" style="color:#1565c0;font-weight:600;font-size:12px;"></span>');

        // Reporting Year
        $form->number('year', 'Reporting Year')
            ->default(date('Y'))
            ->rules('required|integer|min:2020|max:2050');

        // Annual Target
        $form->decimal('target', 'Annual Target')
            ->default(0)
            ->rules('required|numeric|min:0');

        // ── Location ──────────────────────────────────────────────────────
        $form->divider('Location');

        $form->select('district', 'District')
            ->options($this->northernUgandaDistricts());

        // Sub-County — conditionally shown/hidden by JS
        $form->text('sub_county', 'Sub-County')
            ->placeholder('e.g. Katikekile');

        // FFS Group — shown for Output 1 (group location_config)
        $groupOptions = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->where('status', 'Active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
        $form->select('group_id', 'FFS Group')
            ->options($groupOptions);

        // Institution — shown for Output 2 (institution location_config)
        $form->text('institution', 'Institution')
            ->placeholder('e.g. Training college or partner name');

        // Location Type — shown for Output 3 (location_type config)
        $form->select('location_type', 'Location Type')
            ->options([
                'Watershed'    => 'Watershed',
                'Rangeland'    => 'Rangeland',
                'Nursery Site' => 'Nursery Site',
                'Household'    => 'Household',
                'Village'      => 'Village',
                'Field'        => 'Field',
                'Other'        => 'Other',
            ]);

        // ── Monthly Actuals ───────────────────────────────────────────────
        $form->divider('Monthly Actuals (Jan – Dec)');

        // Compact 6-per-row HTML grid.
        // Uses existing model values for edit pre-fill; old() covers validation re-submit.
        $model  = $form->model();
        $months = [
            'jan' => 'Jan', 'feb' => 'Feb', 'mar' => 'Mar',
            'apr' => 'Apr', 'may' => 'May', 'jun' => 'Jun',
            'jul' => 'Jul', 'aug' => 'Aug', 'sep' => 'Sep',
            'oct' => 'Oct', 'nov' => 'Nov', 'dec' => 'Dec',
        ];

        $monthsHtml = '<div class="row" style="margin:0 -4px 4px;">';
        foreach ($months as $col => $label) {
            $val         = old($col, $model->{$col} ?? 0);
            $monthsHtml .= '<div class="col-md-2" style="padding:0 4px;margin-bottom:8px;">'
                         . "<label style='display:block;text-align:center;font-size:11px;"
                         .         "font-weight:700;color:#666;margin:0 0 4px;"
                         .         "text-transform:uppercase;letter-spacing:0.5px;'>{$label}</label>"
                         . "<input type='number' name='{$col}' value='" . e((string) ($val ?? 0)) . "'"
                         .        " class='form-control' style='text-align:center;padding:5px 4px;"
                         .        "font-size:13px;' min='0' step='any' placeholder='0'>"
                         . '</div>';
        }
        $monthsHtml .= '</div>';

        $form->html($monthsHtml, '&nbsp;');

        // ── Notes ─────────────────────────────────────────────────────────
        $form->textarea('comments', 'Comments / Notes')
            ->rows(3)
            ->placeholder('Any additional context, challenges, or remarks for this entry');

        // ── Saving ────────────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            if (empty($form->year)) {
                $form->year = date('Y');
            }
            if (!$form->model()->exists) {
                $form->model()->created_by = Admin::user()->id ?? null;
            }
        });

        // ── JS: Disaggregation hint + conditional location field visibility ─
        $indicatorJsonData = FfsKpiIndicator::asJsData('ip');
        Admin::script("window._kpiIpIndicators = {$indicatorJsonData};");
        Admin::script(<<<'JS'
$(function () {

    function fieldGroup(name) {
        return $('[name="' + name + '"]').closest('.form-group');
    }

    function updateHint(indicatorId) {
        var data = window._kpiIpIndicators[indicatorId];
        if (data && data.disaggregations && data.disaggregations.length) {
            $('#kpi-disagg-hint').text('✔ Valid for this indicator: ' + data.disaggregations.join(' · '));
        } else {
            $('#kpi-disagg-hint').text('');
        }
    }

    function updateLocationFields(indicatorId) {
        var data = window._kpiIpIndicators[indicatorId];
        var lc   = data ? data.location_config : null;

        var $group     = fieldGroup('group_id');
        var $inst      = fieldGroup('institution');
        var $locType   = fieldGroup('location_type');
        var $subCounty = fieldGroup('sub_county');

        $group.hide(); $inst.hide(); $locType.hide();

        if (!lc) return;

        if (lc === 'group')          { $group.show();   $subCounty.show(); }
        else if (lc === 'institution')    { $inst.show();   $subCounty.show(); }
        else if (lc === 'location_type') { $locType.show(); $subCounty.hide(); }
        else if (lc === 'district_only') {                  $subCounty.hide(); }
    }

    // Initialise on page load
    var initId = $('[name="indicator_id"]').val();
    if (initId) {
        updateHint(initId);
        updateLocationFields(initId);
    } else {
        fieldGroup('group_id').hide();
        fieldGroup('institution').hide();
        fieldGroup('location_type').hide();
    }

    // React to indicator change
    $(document).on('change', '[name="indicator_id"]', function () {
        var id = $(this).val();
        updateHint(id);
        updateLocationFields(id);
    });
});
JS);

        return $form;
    }
}
