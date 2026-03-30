<?php

namespace App\Admin\Controllers;

use App\Models\FfsKpiFacilitatorEntry;
use App\Models\FfsKpiIndicator;
use App\Models\FfsGroup;
use App\Models\User;
use App\Models\ImplementingPartner;
use App\Admin\Traits\IpScopeable;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
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
                $facIds = $this->getFacilitatorIds();
                $facilitators = User::whereIn('id', $facIds)
                    ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
                    ->get()
                    ->mapWithKeys(fn($u) => [$u->id => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))])
                    ->toArray();
                $filter->equal('facilitator_id', 'Facilitator')->select($facilitators);

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

        // ── PDF toolbar buttons ──────────────────────────────────────────

        $grid->tools(function ($tools) use ($ipId) {
            $curYear = date('Y');
            $ipParam = $ipId ? "&ip_id={$ipId}" : '';
            $baseReport = admin_url('ffs-kpi-facilitator-entries/pdf-report') . "?year={$curYear}{$ipParam}";

            $yearOpts = '';
            for ($y = $curYear; $y >= $curYear - 4; $y--) {
                $yearOpts .= "<option value='{$y}'>{$y}</option>";
            }

            $tools->append("
                <div style='display:inline-flex;align-items:center;gap:4px;margin-left:6px;'>
                    <select id='kpi-fac-pdf-year' class='form-control input-sm' style='width:80px;'>
                        {$yearOpts}
                    </select>
                    <a id='btn-kpi-fac-pdf' href='{$baseReport}' target='_blank'
                       class='btn btn-sm btn-primary' title='Download Facilitator KPI Data Report'>
                        <i class='fa fa-file-pdf-o'></i> Data Report
                    </a>
                </div>
                <script>
                (function() {
                    var ip = '{$ipParam}';
                    var base = '" . admin_url('ffs-kpi-facilitator-entries/pdf-report') . "';
                    \$(document).on('change', '#kpi-fac-pdf-year', function() {
                        var y = \$(this).val();
                        \$('#btn-kpi-fac-pdf').attr('href', base + '?year=' + y + ip);
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
        $entry = FfsKpiFacilitatorEntry::with(['ip', 'indicator', 'facilitator', 'group'])->findOrFail($id);
        $show  = new Show($entry);

        $indicatorName = $entry->indicator->indicator_name ?? '—';
        $show->panel()
            ->style('success')
            ->title('Facilitator KPI — ' . $indicatorName . ' (' . $entry->disaggregation . ')');

        $show->divider('Entry Details');
        $show->field('ip.name', 'Implementing Partner');
        $show->field('output_and_indicator', 'Output & Indicator')->as(function () use ($entry) {
            $n = $entry->indicator->output_number ?? '?';
            return "Output {$n} — " . ($entry->indicator->indicator_name ?? '—');
        });
        $show->field('disaggregation', 'Disaggregation');

        $show->divider('Facilitator & Location');
        $show->field('facilitator_name_display', 'Facilitator')->as(function () use ($entry) {
            if ($entry->facilitator) {
                return trim(($entry->facilitator->first_name ?? '') . ' ' . ($entry->facilitator->last_name ?? ''));
            }
            return '—';
        });
        $show->field('district', 'District')->as(fn($v) => $v ?: '—');
        $show->field('sub_county', 'Sub-County')->as(fn($v) => $v ?: '—');
        $show->field('group.name', 'FFS Group')->as(fn($v) => $v ?: '—');

        $show->divider('Recorded Value');
        $show->field('session_date', 'Session Date')->as(fn($d) => $d ? date('d M Y', strtotime($d)) : '—');
        $show->field('value_display', 'Value')->unescape()->as(function () use ($entry) {
            $v = (float) $entry->value;
            return "<span style='font-size:24px;font-weight:800;color:#05179F;'>" . number_format($v, 0) . "</span>";
        });
        $show->field('target_reference', 'Indicator Target')->as(function () use ($entry) {
            return $entry->indicator ? number_format($entry->indicator->default_target, 0) : '—';
        });

        $show->divider('Notes');
        $show->field('comments', 'Comments')->as(fn($v) => $v ?: '—');

        $show->divider('Audit');
        $show->field('created_at', 'Recorded At')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');
        $show->field('updated_at', 'Last Updated')->as(fn($d) => $d ? date('d M Y h:i A', strtotime($d)) : '—');

        return $show;
    }

    // ── Form ──────────────────────────────────────────────────────────────

    protected function form()
    {
        $form = new Form(new FfsKpiFacilitatorEntry());
        $ipId = $this->getAdminIpId();

        // ── Implementing Partner ─────────────────────────────────────────
        $this->addIpFieldToForm($form);

        // ── Facilitator ──────────────────────────────────────────────────
        $facIds = $this->getFacilitatorIds();

        // Build facilitator options with group label
        $facilitatorUsers = User::whereIn('id', $facIds)
            ->when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->orderBy('first_name')
            ->get();

        $facGroupMap = FfsGroup::where('status', 'Active')
            ->whereIn('facilitator_id', $facIds)
            ->get()
            ->groupBy('facilitator_id');

        $facilitators = $facilitatorUsers->mapWithKeys(function ($u) use ($facGroupMap) {
            $name = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            $groups = $facGroupMap->get($u->id);
            if ($groups && $groups->count()) {
                $name .= ' [' . $groups->pluck('name')->take(2)->implode(', ') . ']';
            }
            return [$u->id => $name];
        })->toArray();

        $form->select('facilitator_id', 'Facilitator')
            ->options($facilitators)
            ->rules('required')
            ->help('Select the facilitator who collected this data');

        // ── Location ─────────────────────────────────────────────────────
        $form->divider('Location');

        $form->select('district', 'District')
            ->options($this->northernUgandaDistricts())
            ->rules('required');

        // Sub-county: select with all known sub-counties, JS filters by district
        $subcountyMap = $this->karamojaSubcounties();
        $allSubcounties = [];
        foreach ($subcountyMap as $subs) {
            foreach ($subs as $s) {
                $allSubcounties[$s] = $s;
            }
        }
        ksort($allSubcounties);

        $form->select('sub_county', 'Sub-County')
            ->options($allSubcounties);

        // FFS Group — JS filters by district
        $activeGroups = FfsGroup::when($ipId, fn($q) => $q->where('ip_id', $ipId))
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name', 'district_text']);

        $groupOptions = $activeGroups->pluck('name', 'id')->toArray();

        $form->select('group_id', 'FFS Group')
            ->options($groupOptions)
            ->rules('required');

        // ── KPI Indicator ────────────────────────────────────────────────
        $form->divider('KPI Data');

        $indicators = FfsKpiIndicator::where('type', 'facilitator')->orderBy('sort_order')->get();
        $indicatorOptions = $indicators->mapWithKeys(fn($i) => [
            $i->id => "Output {$i->output_number} — {$i->indicator_name}"
        ])->toArray();

        $form->select('indicator_id', 'KPI Indicator')
            ->options($indicatorOptions)
            ->rules('required');

        // Disaggregation — rebuilt by JS based on indicator
        $form->select('disaggregation', 'Disaggregation')
            ->options([
                'Female' => 'Female',
                'Male'   => 'Male',
                'Youth'  => 'Youth',
                'PWD'    => 'PWD',
            ])
            ->rules('required')
            ->help('<span id="kpi-fac-disagg-hint" style="color:#1565c0;font-weight:600;font-size:12px;"></span>');

        // ── Session & Value ──────────────────────────────────────────────
        $form->divider('Session Data');

        $form->date('session_date', 'Session Date')
            ->default(date('Y-m-d'))
            ->rules('required');

        $form->decimal('value', 'Value')
            ->default(0)
            ->rules('required|numeric|min:0')
            ->help('The count / number recorded for this indicator in this session');

        $form->textarea('comments', 'Comments / Notes')
            ->rows(3)
            ->placeholder('Any additional context or remarks');

        // ── Saving Hook ──────────────────────────────────────────────────
        $form->saving(function (Form $form) {
            if (!$form->model()->exists) {
                $form->model()->created_by = Admin::user()->id ?? null;
            }
        });

        // ── JavaScript: Smart cascading & disaggregation filtering ───────
        $indicatorJsonData = FfsKpiIndicator::asJsData('facilitator');

        // Build groups-by-district JS data
        $groupsByDistrict = $activeGroups->groupBy('district_text')
            ->map(fn($g) => $g->map(fn($grp) => ['id' => $grp->id, 'name' => $grp->name])->values())
            ->toArray();

        Admin::script("window._kpiFacIndicators = {$indicatorJsonData};");
        Admin::script("window._districtSubcounties = " . json_encode($subcountyMap) . ";");
        Admin::script("window._groupsByDistrict = " . json_encode($groupsByDistrict) . ";");

        Admin::script(<<<'JS'
$(function () {

    // ── Store all initial option sets ────────────────────────────────────
    var allDisaggOptions = [
        {value: 'Female', text: 'Female'},
        {value: 'Male',   text: 'Male'},
        {value: 'Youth',  text: 'Youth'},
        {value: 'PWD',    text: 'PWD'}
    ];

    var $subCountySel  = $('[name="sub_county"]');
    var $groupSel      = $('[name="group_id"]');
    var $disaggSel     = $('[name="disaggregation"]');
    var $indicatorSel  = $('[name="indicator_id"]');
    var $districtSel   = $('[name="district"]');

    // Store all group options from the initial render (for fallback)
    var allGroupOptions = [];
    $groupSel.find('option').each(function () {
        if ($(this).val()) {
            allGroupOptions.push({id: $(this).val(), name: $(this).text()});
        }
    });

    // ── 1. Disaggregation filtering on indicator change ──────────────────
    function updateDisaggregation(indicatorId) {
        var data = window._kpiFacIndicators[indicatorId];
        var raw  = data ? data.disaggregations : null;

        var opts = [];
        if (Array.isArray(raw)) {
            opts = raw;
        } else if (typeof raw === 'string' && raw.length) {
            try { opts = JSON.parse(raw); } catch(e) { opts = [raw]; }
        }

        var currentVal = $disaggSel.val();

        // Rebuild select options
        $disaggSel.empty().append('<option value="">-- Select --</option>');
        var matchedOpts = opts.length
            ? allDisaggOptions.filter(function (o) { return opts.indexOf(o.value) !== -1; })
            : allDisaggOptions;

        matchedOpts.forEach(function (o) {
            $disaggSel.append('<option value="' + o.value + '">' + o.text + '</option>');
        });

        // Auto-select if only 1, or preserve current value
        if (matchedOpts.length === 1) {
            $disaggSel.val(matchedOpts[0].value);
        } else if (currentVal && matchedOpts.some(function (o) { return o.value === currentVal; })) {
            $disaggSel.val(currentVal);
        }
        $disaggSel.trigger('change.select2');

        // Update hint
        if (opts.length) {
            $('#kpi-fac-disagg-hint').text('Valid for this indicator: ' + opts.join(' / '));
        } else {
            $('#kpi-fac-disagg-hint').text('');
        }
    }

    // ── 2. Sub-county filtering on district change ───────────────────────
    function updateSubcounties(district) {
        var map  = window._districtSubcounties || {};
        var subs = map[district] || [];

        var currentVal = $subCountySel.val();
        $subCountySel.empty().append('<option value="">-- Select --</option>');

        if (subs.length) {
            subs.forEach(function (s) {
                $subCountySel.append('<option value="' + s + '">' + s + '</option>');
            });
        }

        if (currentVal && subs.indexOf(currentVal) !== -1) {
            $subCountySel.val(currentVal);
        }
        $subCountySel.trigger('change.select2');
    }

    // ── 3. FFS Group filtering on district change ────────────────────────
    function updateGroups(district) {
        var map    = window._groupsByDistrict || {};
        var groups = map[district] || [];

        var currentVal = $groupSel.val();
        $groupSel.empty().append('<option value="">-- Select --</option>');

        var source = groups.length ? groups : allGroupOptions;
        source.forEach(function (g) {
            var id   = g.id   || g.id;
            var name = g.name || g.name;
            $groupSel.append('<option value="' + id + '">' + name + '</option>');
        });

        if (currentVal) {
            $groupSel.val(currentVal);
        }
        $groupSel.trigger('change.select2');
    }

    // ── Event bindings ───────────────────────────────────────────────────
    $(document).on('change', '[name="indicator_id"]', function () {
        updateDisaggregation($(this).val());
    });

    $(document).on('change', '[name="district"]', function () {
        var d = $(this).val();
        updateSubcounties(d);
        updateGroups(d);
    });

    // ── Initialise on page load (for edit forms) ─────────────────────────
    var initIndicator = $indicatorSel.val();
    if (initIndicator) {
        updateDisaggregation(initIndicator);
    }

    var initDistrict = $districtSel.val();
    if (initDistrict) {
        updateSubcounties(initDistrict);
        updateGroups(initDistrict);
    }
});
JS);

        return $form;
    }

    // ── PDF: Facilitator KPI Data Report (landscape) ─────────────────────

    public function pdfReport(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));
        $ipId = $this->getAdminIpId() ?? (int) $request->get('ip_id') ?: null;

        $query = FfsKpiFacilitatorEntry::with(['indicator', 'ip', 'facilitator', 'group'])
            ->whereYear('session_date', $year);

        if ($ipId) {
            $query->where('ip_id', $ipId);
        }

        $entries = $query->orderBy('indicator_id')
            ->orderBy('session_date')
            ->get();

        $byIndicator = $entries->groupBy('indicator_id');

        $ip = $ipId ? ImplementingPartner::find($ipId) : null;

        $pdf = \PDF::loadView('admin.kpi.facilitator-report', [
            'entries'            => $entries,
            'byIndicator'       => $byIndicator,
            'year'               => $year,
            'ip'                 => $ip,
            'isSuperAdmin'       => $this->isSuperAdmin(),
            'generatedAt'        => now()->format('d M Y h:i A'),
            'generatedBy'        => Admin::user()->name ?? 'System',
        ])->setPaper('A4', 'landscape');

        return $pdf->stream("FFS_KPI_Facilitator_Report_{$year}.pdf");
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Get all user IDs that are facilitators (from group assignments + facilitator_start_date).
     */
    protected function getFacilitatorIds()
    {
        return DB::table('ffs_groups')
            ->whereNotNull('facilitator_id')
            ->distinct()
            ->pluck('facilitator_id')
            ->merge(DB::table('users')->whereNotNull('facilitator_start_date')->pluck('id'))
            ->unique();
    }

    /**
     * Karamoja sub-counties grouped by district.
     */
    protected function karamojaSubcounties(): array
    {
        return [
            'Abim'          => ['Abim Town Council', 'Alerek', 'Lotuke', 'Morulem', 'Nyakwae'],
            'Amudat'        => ['Amudat Town Council', 'Karita', 'Loroo', 'Amudat'],
            'Kaabong'       => ['Kaabong Town Council', 'Kalapata', 'Kathile', 'Lodiko', 'Lolelia', 'Sidok', 'Lobalangit', 'Kamion'],
            'Karenga'       => ['Karenga Town Council', 'Kapedo', 'Sangar', 'Watchman'],
            'Kotido'        => ['Kotido Town Council', 'Kacheri', 'Nakapelimoru', 'Panyangara', 'Rengen'],
            'Moroto'        => ['Moroto Municipality', 'Katikekile', 'Nadunget', 'Rupa', 'Tapac'],
            'Nakapiripirit' => ['Nakapiripirit Town Council', 'Kakomongole', 'Lolachat', 'Lorengedwat', 'Namalu'],
            'Napak'         => ['Napak Town Council', 'Bokora', 'Iriiri', 'Lopeei', 'Lorengecora', 'Lotome', 'Matany'],
            'Nabilatuk'     => ['Nabilatuk Town Council', 'Kosiroi', 'Lolachat', 'Lorengedwat', 'Nabilatuk'],
        ];
    }
}
