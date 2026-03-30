<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>IP KPI Performance Report {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { size: A4 portrait; margin: 20mm 25mm 22mm 25mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #222;
            line-height: 1.4;
        }

        /* ── Header ─────────────────────────────────────────────────────── */
        .header-bar {
            background: #003d80;
            color: #fff;
            padding: 12px 16px 10px;
            margin-bottom: 0;
        }
        .header-bar-inner { display: table; width: 100%; }
        .header-left  { display: table-cell; vertical-align: middle; width: 60%; }
        .header-right { display: table-cell; vertical-align: middle; width: 40%; text-align: right; }

        .org-name {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            opacity: 0.85;
            margin-bottom: 2px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .report-subtitle {
            font-size: 7.5pt;
            margin-top: 2px;
            opacity: 0.9;
        }
        .meta-line {
            font-size: 7pt;
            opacity: 0.85;
            margin-top: 1px;
        }

        .accent-strip {
            height: 3px;
            background: #0072c6;
            margin-bottom: 12px;
        }

        /* ── Summary cards ──────────────────────────────────────────────── */
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border: 1px solid #c0cfe0;
        }
        .sum-card {
            display: table-cell;
            text-align: center;
            padding: 7px 5px;
            border-right: 1px solid #c0cfe0;
            background: #f0f5fc;
        }
        .sum-card:last-child { border-right: none; }
        .sum-card .val {
            font-size: 13pt;
            font-weight: bold;
            color: #003d80;
            display: block;
        }
        .sum-card .lbl {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            margin-top: 1px;
        }
        .sum-card.highlight { background: #003d80; color: #fff; }
        .sum-card.highlight .val { color: #fff; }
        .sum-card.highlight .lbl { color: rgba(255,255,255,0.85); }

        /* ── Section headings ───────────────────────────────────────────── */
        .section-block { margin-bottom: 14px; }
        .section-title {
            background: #003d80;
            color: #fff;
            padding: 5px 12px;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 0;
        }

        /* ── Tables ─────────────────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #1a4d8f;
            color: #fff;
            padding: 5px 5px;
            font-size: 7pt;
            font-weight: bold;
            text-align: left;
            border: 1px solid #0d3a73;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        thead th.ctr { text-align: center; }
        thead th.right { text-align: right; padding-right: 6px; }

        tbody td {
            padding: 4px 5px;
            border: 1px solid #d0d8e8;
            font-size: 7.5pt;
            vertical-align: middle;
        }
        tbody tr:nth-child(even) { background: #f6f8fc; }
        tbody tr:nth-child(odd)  { background: #fff; }

        td.ctr   { text-align: center; }
        td.right { text-align: right; padding-right: 6px; }
        td.bold  { font-weight: bold; }

        /* ── Output sub-header ──────────────────────────────────────────── */
        .out-sub td {
            background: #e0e8f5 !important;
            font-weight: bold;
            font-size: 7.5pt;
            color: #003d80;
            border: 1px solid #a8bbd8;
            padding: 4px 8px;
        }

        /* ── Grand total row ────────────────────────────────────────────── */
        .grand-row td {
            background: #003d80 !important;
            font-weight: bold;
            border: 1px solid #002a5c;
            color: #fff;
            font-size: 7.5pt;
            padding: 5px 5px;
        }

        /* ── Performance bar ────────────────────────────────────────────── */
        .bar-wrap { display: table; width: 100%; }
        .bar-track {
            display: table-cell;
            width: 75%;
            vertical-align: middle;
            padding-right: 5px;
        }
        .bar-pct {
            display: table-cell;
            width: 25%;
            vertical-align: middle;
            font-weight: bold;
            font-size: 7.5pt;
            text-align: right;
        }
        .bar-outer {
            height: 10px;
            background: #e0e0e0;
            overflow: hidden;
        }
        .bar-inner { height: 10px; }

        /* ── Performance chip ───────────────────────────────────────────── */
        .chip {
            display: inline-block;
            padding: 1.5px 6px;
            font-size: 6.5pt;
            font-weight: bold;
            color: #fff;
        }
        .chip-exceed  { background: #1565c0; }
        .chip-ontrack { background: #2e7d32; }
        .chip-slight  { background: #e65100; }
        .chip-need    { background: #c62828; }

        /* ── Alert rows ─────────────────────────────────────────────────── */
        .alert-critical { color: #c62828; font-weight: bold; }
        .alert-warning  { color: #e65100; font-weight: bold; }
        .row-critical   { background: #fff5f5 !important; }
        .row-warning    { background: #fffde7 !important; }

        /* ── Legend ──────────────────────────────────────────────────────── */
        .legend-row {
            display: table;
            width: 100%;
            margin-top: 10px;
            padding: 5px 0;
            border: 1px solid #d0d8e8;
            background: #f8fafc;
        }
        .legend-item {
            display: table-cell;
            text-align: center;
            font-size: 6.5pt;
            color: #444;
            padding: 0 6px;
        }
        .legend-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            vertical-align: middle;
            margin-right: 3px;
        }

        /* ── No records ─────────────────────────────────────────────────── */
        .no-records {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border: 1px dashed #bbb;
            color: #666;
            font-size: 9pt;
        }
        .no-alerts {
            text-align: center;
            padding: 14px;
            background: #f0fff4;
            border: 1px solid #a5d6a7;
            color: #2e7d32;
            font-size: 8pt;
            font-weight: bold;
        }

        /* ── Footer ─────────────────────────────────────────────────────── */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 6px 0;
            border-top: 2px solid #003d80;
            font-size: 6.5pt;
            color: #444;
        }
        .footer-inner { display: table; width: 100%; }
        .footer-left  { display: table-cell; text-align: left; width: 35%; }
        .footer-center { display: table-cell; text-align: center; width: 35%; }
        .footer-right { display: table-cell; text-align: right; width: 30%; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

{{-- ── Header ────────────────────────────────────────────────────────────────── --}}
<div class="header-bar">
    <div class="header-bar-inner">
        <div class="header-left">
            <div class="org-name">Food and Agriculture Organization of the United Nations</div>
            <div class="report-title">IP KPI Performance Report &mdash; {{ $year }}</div>
            <div class="report-subtitle">
                UNJP/UGA/068/EC &mdash; FOSTER (Food Security and Resilience in Karamoja)
                @if($ip) &bull; {{ $ip->name }}{{ $ip->short_name ? ' ('.$ip->short_name.')' : '' }}@endif
            </div>
        </div>
        <div class="header-right">
            <div class="meta-line"><strong>Report Date:</strong> {{ $generatedAt }}</div>
            <div class="meta-line"><strong>Generated By:</strong> {{ $generatedBy }}</div>
            <div class="meta-line"><strong>Period:</strong> January &ndash; December {{ $year }}</div>
        </div>
    </div>
</div>
<div class="accent-strip"></div>

{{-- ── Summary ─────────────────────────────────────────────────────────────── --}}
@php
    $totalEntries = $entries->count();
    $totalTarget  = $entries->sum('target');
    $totalOverall = $entries->sum(fn($e) => $e->overall);
    $avgPerf      = $totalTarget > 0 ? round($totalOverall / $totalTarget * 100, 1) : 0;
    $onTrack      = $entries->filter(fn($e) => $e->performance_pct >= 85)->count();
    $needsAttn    = $entries->filter(fn($e) => $e->performance_pct < 85 && $e->target > 0)->count();
@endphp
<div class="summary-row">
    <div class="sum-card">
        <span class="val">{{ $totalEntries }}</span>
        <span class="lbl">KPI Entries</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ number_format($totalTarget, 0) }}</span>
        <span class="lbl">Total Target</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ number_format($totalOverall, 0) }}</span>
        <span class="lbl">Total Achieved</span>
    </div>
    <div class="sum-card highlight">
        <span class="val">{{ $avgPerf }}%</span>
        <span class="lbl">Overall Performance</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ $onTrack }}</span>
        <span class="lbl">On Track (&ge;85%)</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ $needsAttn }}</span>
        <span class="lbl">Needs Attention</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 1: KPI Progress by Output                                          --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="section-block">
    <div class="section-title">Section 1 &mdash; KPI Progress by Output</div>
    @if($entries->count() > 0)
    <table>
        <thead>
            <tr>
                @if($isSuperAdmin) <th style="width:9%;">IP</th> @endif
                <th style="width:{{ $isSuperAdmin ? '24%' : '29%' }};">KPI Indicator</th>
                <th style="width:7%;" class="ctr">Disagg.</th>
                <th style="width:10%;">Location</th>
                <th style="width:7%;" class="right">Target</th>
                <th style="width:7%;" class="right">Achieved</th>
                <th style="width:7%;" class="right">Variance</th>
                <th style="width:15%;" class="ctr">Performance</th>
                <th style="width:9%;" class="ctr">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byOutput as $outputNo => $outputEntries)
                @php
                    $outTarget = $outputEntries->sum('target');
                    $outActual = $outputEntries->sum(fn($e) => $e->overall);
                    $outPerf   = $outTarget > 0 ? round($outActual / $outTarget * 100, 1) : 0;
                    $outChip   = $outPerf >= 100 ? 'chip-exceed'
                               : ($outPerf >= 85  ? 'chip-ontrack'
                               : ($outPerf >= 70  ? 'chip-slight'
                               :                    'chip-need'));
                @endphp
                <tr class="out-sub">
                    @if($isSuperAdmin) <td></td> @endif
                    <td colspan="{{ $isSuperAdmin ? 7 : 8 }}" style="padding:4px 8px;">
                        {{ $outputNo ? 'Output '.$outputNo : 'Uncategorised' }}
                        &nbsp;&mdash;&nbsp;
                        Target: {{ number_format($outTarget, 0) }}
                        &bull; Achieved: {{ number_format($outActual, 0) }}
                        &bull; Performance: {{ $outPerf }}%
                    </td>
                    <td class="ctr">
                        <span class="chip {{ $outChip }}">{{ $outPerf }}%</span>
                    </td>
                </tr>
                @foreach($outputEntries as $entry)
                    @php
                        $pct = $entry->performance_pct;
                        $bc  = $pct >= 100 ? 'chip-exceed'
                             : ($pct >= 85  ? 'chip-ontrack'
                             : ($pct >= 70  ? 'chip-slight'
                             :                'chip-need'));
                        $vc  = $entry->variance <= 0 ? '#1565c0'
                             : ($entry->variance < ($entry->target * 0.15) ? '#e65100' : '#c62828');
                        $barW     = min($pct, 100);
                        $barColor = \App\Models\FfsKpiIpEntry::performanceColor($pct);
                    @endphp
                    <tr>
                        @if($isSuperAdmin)
                            <td style="font-size:7pt;">{{ $entry->ip->short_name ?? ($entry->ip->name ?? '—') }}</td>
                        @endif
                        <td>{{ $entry->indicator->indicator_name ?? '—' }}</td>
                        <td class="ctr" style="font-size:7pt;">{{ $entry->disaggregation ?: '—' }}</td>
                        <td style="font-size:7pt;">{{ $entry->location_display }}</td>
                        <td class="right bold">{{ number_format($entry->target, 0) }}</td>
                        <td class="right" style="color:#003d80; font-weight:bold;">{{ number_format($entry->overall, 0) }}</td>
                        <td class="right" style="color:{{ $vc }}; font-weight:bold;">
                            {{ $entry->variance <= 0 ? '+'.number_format(abs($entry->variance), 0) : '-'.number_format($entry->variance, 0) }}
                        </td>
                        <td class="ctr" style="padding:3px 5px;">
                            <div class="bar-wrap">
                                <div class="bar-track">
                                    <div class="bar-outer">
                                        <div class="bar-inner" style="width:{{ $barW }}%; background:{{ $barColor }};"></div>
                                    </div>
                                </div>
                                <div class="bar-pct" style="color:{{ $barColor }};">{{ $pct }}%</div>
                            </div>
                        </td>
                        <td class="ctr"><span class="chip {{ $bc }}">{{ \App\Models\FfsKpiIpEntry::performanceLabel($pct) }}</span></td>
                    </tr>
                @endforeach
            @endforeach

            {{-- Grand Total --}}
            <tr class="grand-row">
                @if($isSuperAdmin) <td></td> @endif
                <td colspan="3" style="padding-left:8px;">GRAND TOTAL ({{ $entries->count() }} entries)</td>
                <td class="right">{{ number_format($totalTarget, 0) }}</td>
                <td class="right">{{ number_format($totalOverall, 0) }}</td>
                <td class="right">
                    @php $gv = $totalTarget - $totalOverall; @endphp
                    {{ $gv <= 0 ? '+'.number_format(abs($gv), 0) : '-'.number_format($gv, 0) }}
                </td>
                <td class="ctr" style="padding:3px 5px;">
                    <div class="bar-wrap">
                        <div class="bar-track">
                            <div class="bar-outer" style="background:rgba(255,255,255,0.3);">
                                <div class="bar-inner" style="width:{{ min($avgPerf, 100) }}%; background:#fff;"></div>
                            </div>
                        </div>
                        <div class="bar-pct" style="color:#fff;">{{ $avgPerf }}%</div>
                    </div>
                </td>
                <td class="ctr">
                    <span class="chip" style="background:rgba(255,255,255,0.2); color:#fff;">
                        {{ \App\Models\FfsKpiIpEntry::performanceLabel($avgPerf) }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
    @else
        <div class="no-records">No KPI data records found for {{ $year }}.</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 2: IP Performance Comparison (Super Admin only)                    --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
@if($isSuperAdmin && $ipComparison && $ipComparison->count() > 0)
<div class="section-block">
    <div class="section-title">Section 2 &mdash; Implementing Partner Performance Comparison</div>
    <table>
        <thead>
            <tr>
                <th style="width:4%;" class="ctr">#</th>
                <th style="width:26%;">Implementing Partner</th>
                <th style="width:6%;" class="right">KPIs</th>
                <th style="width:9%;" class="right">Target</th>
                <th style="width:9%;" class="right">Achieved</th>
                <th style="width:9%;" class="right">Variance</th>
                <th style="width:22%;" class="ctr">Performance</th>
                <th style="width:10%;" class="ctr">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ipComparison as $rank => $row)
                @php
                    $pct = $row['pct'];
                    $bc  = $pct >= 100 ? 'chip-exceed'
                         : ($pct >= 85  ? 'chip-ontrack'
                         : ($pct >= 70  ? 'chip-slight'
                         :                'chip-need'));
                    $barColor   = \App\Models\FfsKpiIpEntry::performanceColor($pct);
                    $barW       = min($pct, 100);
                    $ipVariance = $row['target'] - $row['actual'];
                    $vc  = $ipVariance <= 0 ? '#1565c0'
                         : ($ipVariance < ($row['target'] * 0.15) ? '#e65100' : '#c62828');
                @endphp
                <tr>
                    <td class="ctr bold" style="color:#003d80;">{{ $loop->iteration }}</td>
                    <td>
                        <span style="font-weight:bold;">{{ $row['ip']->name ?? '—' }}</span>
                        @if($row['ip']->short_name ?? false)
                            <br><span style="font-size:6.5pt; color:#666;">({{ $row['ip']->short_name }})</span>
                        @endif
                    </td>
                    <td class="right">{{ $row['count'] }}</td>
                    <td class="right bold">{{ number_format($row['target'], 0) }}</td>
                    <td class="right" style="color:#003d80; font-weight:bold;">{{ number_format($row['actual'], 0) }}</td>
                    <td class="right" style="color:{{ $vc }}; font-weight:bold;">
                        {{ $ipVariance <= 0 ? '+'.number_format(abs($ipVariance), 0) : '-'.number_format($ipVariance, 0) }}
                    </td>
                    <td class="ctr" style="padding:4px 6px;">
                        <div class="bar-wrap">
                            <div class="bar-track">
                                <div class="bar-outer">
                                    <div class="bar-inner" style="width:{{ $barW }}%; background:{{ $barColor }};"></div>
                                </div>
                            </div>
                            <div class="bar-pct" style="color:{{ $barColor }};">{{ $pct }}%</div>
                        </div>
                    </td>
                    <td class="ctr"><span class="chip {{ $bc }}">{{ $row['label'] }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 3: Monitoring Alerts (below 85%)                                   --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
<div class="section-block">
    <div class="section-title">
        Section {{ $isSuperAdmin ? '3' : '2' }} &mdash; Monitoring Alerts: KPIs Below 85% Performance
    </div>
    @if($alerts->count() > 0)
    <table>
        <thead>
            <tr>
                @if($isSuperAdmin) <th style="width:9%;">IP</th> @endif
                <th style="width:{{ $isSuperAdmin ? '24%' : '28%' }};">KPI Indicator</th>
                <th style="width:7%;" class="ctr">Disagg.</th>
                <th style="width:10%;">Location</th>
                <th style="width:8%;" class="right">Target</th>
                <th style="width:8%;" class="right">Achieved</th>
                <th style="width:8%;" class="right">Shortfall</th>
                <th style="width:8%;" class="ctr">Perf%</th>
                <th style="width:10%;" class="ctr">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alerts as $entry)
                @php
                    $pct      = $entry->performance_pct;
                    $rowCls   = $pct < 70 ? 'row-critical' : 'row-warning';
                    $alertCls = $pct < 70 ? 'alert-critical' : 'alert-warning';
                    $bc       = $pct >= 70 ? 'chip-slight' : 'chip-need';
                @endphp
                <tr class="{{ $rowCls }}">
                    @if($isSuperAdmin)
                        <td style="font-size:7pt;">{{ $entry->ip->short_name ?? ($entry->ip->name ?? '—') }}</td>
                    @endif
                    <td>{{ $entry->indicator->indicator_name ?? '—' }}</td>
                    <td class="ctr" style="font-size:7pt;">{{ $entry->disaggregation ?: '—' }}</td>
                    <td style="font-size:7pt;">{{ $entry->location_display }}</td>
                    <td class="right bold">{{ number_format($entry->target, 0) }}</td>
                    <td class="right" style="color:#003d80; font-weight:bold;">{{ number_format($entry->overall, 0) }}</td>
                    <td class="right {{ $alertCls }}">-{{ number_format($entry->variance, 0) }}</td>
                    <td class="ctr {{ $alertCls }}">{{ $pct }}%</td>
                    <td class="ctr"><span class="chip {{ $bc }}">{{ \App\Models\FfsKpiIpEntry::performanceLabel($pct) }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <div class="no-alerts">
            All KPI indicators are performing at 85% or above. No monitoring alerts required.
        </div>
    @endif
</div>

{{-- ── Legend ───────────────────────────────────────────────────────────────── --}}
<div class="legend-row">
    <div class="legend-item">
        <span class="legend-dot" style="background:#1565c0;"></span>
        <strong>Exceeding</strong> &ge;100%
    </div>
    <div class="legend-item">
        <span class="legend-dot" style="background:#2e7d32;"></span>
        <strong>On Track</strong> 85%&ndash;99%
    </div>
    <div class="legend-item">
        <span class="legend-dot" style="background:#e65100;"></span>
        <strong>Slightly Behind</strong> 70%&ndash;84%
    </div>
    <div class="legend-item">
        <span class="legend-dot" style="background:#c62828;"></span>
        <strong>Needs Attention</strong> &lt;70%
    </div>
</div>

{{-- ── Footer ────────────────────────────────────────────────────────────────── --}}
<div class="page-footer">
    <div class="footer-inner">
        <div class="footer-left">FAO FFS MIS &bull; UNJP/UGA/068/EC &mdash; FOSTER</div>
        <div class="footer-center">IP KPI Performance Report &bull; {{ $year }}</div>
        <div class="footer-right">{{ $generatedBy }} &bull; {{ $generatedAt }}</div>
    </div>
</div>

</body>
</html>
