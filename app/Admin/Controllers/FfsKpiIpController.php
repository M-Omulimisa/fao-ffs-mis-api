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
use Illuminate\Http\Request;

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

        // ── PDF toolbar buttons ────────────────────────────────────────────

        $grid->tools(function ($tools) use ($ipId) {
            $curYear = date('Y');
            $ipParam = $ipId ? "&ip_id={$ipId}" : '';

            $baseReport = admin_url('ffs-kpi-ip-entries/pdf-report') . "?year={$curYear}{$ipParam}";
            $basePerf   = admin_url('ffs-kpi-ip-entries/pdf-performance') . "?year={$curYear}{$ipParam}";

            $yearOpts = '';
            for ($y = $curYear; $y >= $curYear - 4; $y--) {
                $yearOpts .= "<option value='{$y}'>{$y}</option>";
            }

            $tools->append("
                <div style='display:inline-flex;align-items:center;gap:4px;margin-left:6px;'>
                    <select id='kpi-pdf-year' class='form-control input-sm' style='width:80px;'>
                        {$yearOpts}
                    </select>
                    <a id='btn-kpi-data-pdf' href='{$baseReport}' target='_blank'
                       class='btn btn-sm btn-primary' title='Download KPI Data Report PDF'>
                        <i class='fa fa-file-pdf-o'></i> Data Report
                    </a>
                    <a id='btn-kpi-perf-pdf' href='{$basePerf}' target='_blank'
                       class='btn btn-sm btn-success' title='Download KPI Performance Report PDF'>
                        <i class='fa fa-bar-chart'></i> Performance
                    </a>
                </div>
                <script>
                (function() {
                    var ip = '{$ipParam}';
                    var baseR = '" . admin_url('ffs-kpi-ip-entries/pdf-report') . "';
                    var baseP = '" . admin_url('ffs-kpi-ip-entries/pdf-performance') . "';
                    \$(document).on('change', '#kpi-pdf-year', function () {
                        var y = \$(this).val();
                        \$('#btn-kpi-data-pdf').attr('href', baseR + '?year=' + y + ip);
                        \$('#btn-kpi-perf-pdf').attr('href', baseP + '?year=' + y + ip);
                    });
                })();
                </script>
            ");
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

        // Disaggregation — free text so any value can be entered
        $form->text('disaggregation', 'Disaggregation')
            ->placeholder('e.g. Total, Female, Male, Youth, PWD …')
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

        $form->decimal('jan', 'January')->default(0)->placeholder('0');
        $form->decimal('feb', 'February')->default(0)->placeholder('0');
        $form->decimal('mar', 'March')->default(0)->placeholder('0');
        $form->decimal('apr', 'April')->default(0)->placeholder('0');
        $form->decimal('may', 'May')->default(0)->placeholder('0');
        $form->decimal('jun', 'June')->default(0)->placeholder('0');
        $form->decimal('jul', 'July')->default(0)->placeholder('0');
        $form->decimal('aug', 'August')->default(0)->placeholder('0');
        $form->decimal('sep', 'September')->default(0)->placeholder('0');
        $form->decimal('oct', 'October')->default(0)->placeholder('0');
        $form->decimal('nov', 'November')->default(0)->placeholder('0');
        $form->decimal('dec', 'December')->default(0)->placeholder('0');

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

    // ── Helpers ──────────────────────────────────────────────────────────

    function fieldGroup(name) {
        return $('[name="' + name + '"]').closest('.form-group');
    }

    // ── 1. Disaggregation: show valid options as a hint under the text field.
    //       Auto-fills the field when the indicator has only one valid value.
    function updateDisaggregation(indicatorId) {
        var data = window._kpiIpIndicators[indicatorId];
        var raw  = data ? data.disaggregations : null;

        // raw may be a JSON string (double-encoded) or a real array — normalise
        var opts = [];
        if (Array.isArray(raw)) {
            opts = raw;
        } else if (typeof raw === 'string' && raw.length) {
            try { opts = JSON.parse(raw); } catch(e) { opts = [raw]; }
        }

        // Update hint text
        if (opts.length) {
            $('#kpi-disagg-hint').text('Valid for this indicator: ' + opts.join(' · '));
        } else {
            $('#kpi-disagg-hint').text('');
        }

        // Auto-fill when only one option is valid
        if (opts.length === 1) {
            $('[name="disaggregation"]').val(opts[0]);
        }
    }

    // ── 2. Target: auto-fill from the indicator's default_target.
    function updateTarget(indicatorId) {
        var data = window._kpiIpIndicators[indicatorId];
        if (data && data.default_target !== undefined && data.default_target !== null) {
            $('[name="target"]').val(data.default_target);
        }
    }

    // ── 3. Disaggregation hint (informational text below the select).
    function updateHint(indicatorId) {
        var data = window._kpiIpIndicators[indicatorId];
        if (data && data.disaggregations && data.disaggregations.length) {
            $('#kpi-disagg-hint').text('✔ Valid for this indicator: ' + data.disaggregations.join(' · '));
        } else {
            $('#kpi-disagg-hint').text('');
        }
    }

    // ── 4. Location fields: show/hide based on indicator's location_config.
    //
    //   group         → District + Sub-County + FFS Group
    //   institution   → District + Sub-County + Institution
    //   location_type → District + Location Type  (no Sub-County)
    //   district_only → District + Sub-County     (no Group / Institution / Location Type)
    //
    function updateLocationFields(indicatorId) {
        var data = window._kpiIpIndicators[indicatorId];
        var lc   = data ? data.location_config : null;

        var $group     = fieldGroup('group_id');
        var $inst      = fieldGroup('institution');
        var $locType   = fieldGroup('location_type');
        var $subCounty = fieldGroup('sub_county');

        // Reset — hide the three Output-specific sub-fields
        $group.hide(); $inst.hide(); $locType.hide();

        if (!lc) return;

        if      (lc === 'group')         { $group.show();   $subCounty.show(); }
        else if (lc === 'institution')   { $inst.show();    $subCounty.show(); }
        else if (lc === 'location_type') { $locType.show(); $subCounty.hide(); }
        else if (lc === 'district_only') {                  $subCounty.show(); }   // District + Sub-County, no group/inst/locType
    }

    // ── Master update: run all four whenever the indicator changes ────────
    function onIndicatorChange(indicatorId) {
        updateDisaggregation(indicatorId);
        updateTarget(indicatorId);
        updateHint(indicatorId);
        updateLocationFields(indicatorId);
    }

    // ── Initialise on page load ───────────────────────────────────────────
    var initId = $('[name="indicator_id"]').val();
    if (initId) {
        onIndicatorChange(initId);
    } else {
        // No indicator selected yet — hide all conditional location fields
        $('[name="disaggregation"]').closest('.form-group').show(); // keep visible
        fieldGroup('group_id').hide();
        fieldGroup('institution').hide();
        fieldGroup('location_type').hide();
    }

    // ── React to indicator change ─────────────────────────────────────────
    $(document).on('change', '[name="indicator_id"]', function () {
        onIndicatorChange($(this).val());
    });
});
JS);

        return $form;
    }

    // ── PDF: KPI Data Entry Report (landscape) ────────────────────────────

    public function pdfReport(Request $request)
    {
        $year  = (int) $request->get('year', date('Y'));
        $ipId  = $this->getAdminIpId() ?? (int) $request->get('ip_id') ?: null;

        $query = FfsKpiIpEntry::with(['indicator', 'ip', 'group'])
            ->where('year', $year);

        if ($ipId) {
            $query->where('ip_id', $ipId);
        }

        $entries  = $query->orderBy('ip_id')->orderBy('indicator_id')->orderBy('disaggregation')->get();
        $byOutput = $entries->groupBy(fn($e) => $e->indicator->output_number ?? 0)->sortKeys();

        $ip = $ipId ? ImplementingPartner::find($ipId) : null;

        $pdf = \PDF::loadView('admin.kpi.ip-report', [
            'entries'      => $entries,
            'byOutput'     => $byOutput,
            'year'         => $year,
            'ip'           => $ip,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'generatedAt'  => now()->format('d M Y h:i A'),
            'generatedBy'  => Admin::user()->name ?? 'System',
        ])->setPaper('A4', 'landscape');

        return $pdf->stream("FFS_KPI_IP_Data_Report_{$year}.pdf");
    }

    // ── PDF: KPI Performance Report (portrait) ────────────────────────────

    public function pdfPerformance(Request $request)
    {
        $year  = (int) $request->get('year', date('Y'));
        $ipId  = $this->getAdminIpId() ?? (int) $request->get('ip_id') ?: null;

        $query = FfsKpiIpEntry::with(['indicator', 'ip'])
            ->where('year', $year);

        if ($ipId) {
            $query->where('ip_id', $ipId);
        }

        $entries  = $query->orderBy('indicator_id')->get();
        $byOutput = $entries->groupBy(fn($e) => $e->indicator->output_number ?? 0)->sortKeys();

        // IP comparison — super admin sees all IPs side by side
        $ipComparison = null;
        if ($this->isSuperAdmin()) {
            $allEntries = FfsKpiIpEntry::with('ip')->where('year', $year)->get();
            $ipComparison = $allEntries->groupBy('ip_id')
                ->map(function ($ipEntries) {
                    $ip           = $ipEntries->first()->ip;
                    $totalTarget  = (float) $ipEntries->sum('target');
                    $totalOverall = $ipEntries->sum(fn($e) => $e->overall);
                    $pct          = $totalTarget > 0 ? round($totalOverall / $totalTarget * 100, 1) : 0;
                    return [
                        'ip'     => $ip,
                        'target' => $totalTarget,
                        'actual' => $totalOverall,
                        'pct'    => $pct,
                        'label'  => FfsKpiIpEntry::performanceLabel($pct),
                        'color'  => FfsKpiIpEntry::performanceColor($pct),
                        'count'  => $ipEntries->count(),
                    ];
                })
                ->sortByDesc('pct');
        }

        // Alerts: below 85% and target > 0
        $alerts = $entries->filter(fn($e) => $e->performance_pct < 85 && $e->target > 0)
            ->sortBy('performance_pct');

        $ip = $ipId ? ImplementingPartner::find($ipId) : null;

        $pdf = \PDF::loadView('admin.kpi.ip-performance', [
            'entries'      => $entries,
            'byOutput'     => $byOutput,
            'year'         => $year,
            'ip'           => $ip,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'ipComparison' => $ipComparison,
            'alerts'       => $alerts,
            'generatedAt'  => now()->format('d M Y h:i A'),
            'generatedBy'  => Admin::user()->name ?? 'System',
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("FFS_KPI_IP_Performance_{$year}.pdf");
    }
}
