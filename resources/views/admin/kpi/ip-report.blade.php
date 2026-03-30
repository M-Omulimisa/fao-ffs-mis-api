<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>IP KPI Data Entry Report {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { size: A4 landscape; margin: 15mm 18mm 20mm 18mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7.5pt;
            color: #222;
            line-height: 1.4;
        }

        /* ── Header ─────────────────────────────────────────────────────── */
        .header-bar {
            background: #003d80;
            color: #fff;
            padding: 10px 14px 8px;
            margin-bottom: 0;
        }
        .header-bar-inner { display: table; width: 100%; }
        .header-left  { display: table-cell; vertical-align: middle; width: 62%; }
        .header-right { display: table-cell; vertical-align: middle; width: 38%; text-align: right; }

        .org-name {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            opacity: 0.85;
            margin-bottom: 2px;
        }
        .report-title {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .report-subtitle {
            font-size: 7pt;
            margin-top: 2px;
            opacity: 0.9;
        }
        .meta-line {
            font-size: 6.5pt;
            opacity: 0.85;
            margin-top: 1px;
        }

        /* ── Accent strip below header ──────────────────────────────────── */
        .accent-strip {
            height: 3px;
            background: #0072c6;
            margin-bottom: 10px;
        }

        /* ── Summary cards ──────────────────────────────────────────────── */
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #c0cfe0;
        }
        .sum-card {
            display: table-cell;
            text-align: center;
            padding: 6px 4px;
            border-right: 1px solid #c0cfe0;
            background: #f0f5fc;
        }
        .sum-card:last-child { border-right: none; }
        .sum-card .val {
            font-size: 12pt;
            font-weight: bold;
            color: #003d80;
            display: block;
        }
        .sum-card .lbl {
            font-size: 5.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            margin-top: 1px;
        }
        .sum-card.highlight {
            background: #003d80;
            color: #fff;
        }
        .sum-card.highlight .val { color: #fff; }
        .sum-card.highlight .lbl { color: rgba(255,255,255,0.85); }

        /* ── Output section ──────────────────────────────────────────────── */
        .output-block { margin-bottom: 10px; }
        .output-header {
            background: #003d80;
            color: #fff;
            padding: 4px 10px;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .output-stats {
            font-weight: normal;
            font-size: 6.5pt;
            opacity: 0.9;
        }

        /* ── Data Table ──────────────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        thead th {
            background: #1a4d8f;
            color: #fff;
            padding: 4px 3px;
            font-size: 6pt;
            font-weight: bold;
            text-align: center;
            border: 1px solid #0d3a73;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            word-wrap: break-word;
        }
        thead th.left { text-align: left; padding-left: 5px; }

        tbody td {
            padding: 3px 3px;
            border: 1px solid #d0d8e8;
            font-size: 6.5pt;
            vertical-align: middle;
            word-wrap: break-word;
        }
        tbody tr:nth-child(even) { background: #f6f8fc; }
        tbody tr:nth-child(odd)  { background: #fff; }

        td.num  { text-align: right; padding-right: 4px; }
        td.ctr  { text-align: center; }
        td.left { text-align: left; padding-left: 5px; }

        /* ── Column widths ──────────────────────────────────────────────── */
        .col-ip         { width: 8%;  }
        .col-indicator  { width: 16%; }
        .col-disagg     { width: 5%;  }
        .col-location   { width: 8%;  }
        .col-target     { width: 4%;  }
        .col-month      { width: 3%;  }
        .col-overall    { width: 4.5%; }
        .col-perf       { width: 4.5%; }
        .col-variance   { width: 4.5%; }

        /* ── Performance chip ───────────────────────────────────────────── */
        .chip {
            display: inline-block;
            padding: 1px 4px;
            font-size: 6pt;
            font-weight: bold;
            color: #fff;
        }
        .chip-exceed  { background: #1565c0; }
        .chip-ontrack { background: #2e7d32; }
        .chip-slight  { background: #e65100; }
        .chip-need    { background: #c62828; }

        /* ── Totals row ─────────────────────────────────────────────────── */
        .totals-row td {
            background: #dce5f5 !important;
            font-weight: bold;
            border: 1px solid #a8bbd8;
            color: #003d80;
            font-size: 6.5pt;
        }

        /* ── Grand Total ────────────────────────────────────────────────── */
        .grand-table { margin-top: 6px; }
        .grand-table th {
            background: #003d80;
            color: #fff;
            padding: 5px 3px;
            border: 1px solid #002a5c;
            font-size: 7pt;
            font-weight: bold;
        }
        .grand-table th.num { text-align: right; padding-right: 5px; }
        .grand-table th.ctr { text-align: center; }
        .grand-table th.left { text-align: left; padding-left: 6px; }

        /* ── No records ─────────────────────────────────────────────────── */
        .no-records {
            text-align: center;
            padding: 24px;
            background: #f8f9fa;
            border: 1px dashed #bbb;
            color: #666;
            font-size: 9pt;
            margin-top: 10px;
        }

        /* ── Footer ─────────────────────────────────────────────────────── */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 5px 18mm;
            border-top: 2px solid #003d80;
            font-size: 6pt;
            color: #444;
        }
        .footer-inner { display: table; width: 100%; }
        .footer-left  { display: table-cell; text-align: left; width: 40%; }
        .footer-center { display: table-cell; text-align: center; width: 30%; }
        .footer-right { display: table-cell; text-align: right; width: 30%; }

        /* ── Legend row ──────────────────────────────────────────────────── */
        .legend-row {
            display: table;
            width: 100%;
            margin-top: 8px;
            padding: 4px 0;
            border-top: 1px solid #ccc;
        }
        .legend-item {
            display: table-cell;
            text-align: center;
            font-size: 6pt;
            color: #444;
            padding: 0 4px;
        }
        .legend-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            vertical-align: middle;
            margin-right: 2px;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

{{-- ── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="header-bar">
    <div class="header-bar-inner">
        <div class="header-left">
            <div class="org-name">Food and Agriculture Organization of the United Nations</div>
            <div class="report-title">IP KPI Data Entry Report &mdash; {{ $year }}</div>
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
        <span class="lbl">Total Entries</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ number_format($totalTarget, 0) }}</span>
        <span class="lbl">Annual Target</span>
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

{{-- ── Entries by Output ──────────────────────────────────────────────────── --}}
@forelse($byOutput as $outputNo => $outputEntries)
    @php
        $outputLabel = $outputNo ? 'Output ' . $outputNo : 'Uncategorised';
        $outTarget   = $outputEntries->sum('target');
        $outActual   = $outputEntries->sum(fn($e) => $e->overall);
        $outPerf     = $outTarget > 0 ? round($outActual / $outTarget * 100, 1) : 0;
    @endphp
    <div class="output-block">
        <div class="output-header">
            {{ $outputLabel }}
            <span class="output-stats">
                &nbsp;&mdash;&nbsp;
                Target: {{ number_format($outTarget, 0) }} &nbsp;&bull;&nbsp;
                Achieved: {{ number_format($outActual, 0) }} &nbsp;&bull;&nbsp;
                Performance: {{ $outPerf }}%
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    @if($isSuperAdmin) <th class="col-ip left">IP</th> @endif
                    <th class="col-indicator left">KPI Indicator</th>
                    <th class="col-disagg">Disagg.</th>
                    <th class="col-location left">Location</th>
                    <th class="col-target">Target</th>
                    <th class="col-month">Jan</th>
                    <th class="col-month">Feb</th>
                    <th class="col-month">Mar</th>
                    <th class="col-month">Apr</th>
                    <th class="col-month">May</th>
                    <th class="col-month">Jun</th>
                    <th class="col-month">Jul</th>
                    <th class="col-month">Aug</th>
                    <th class="col-month">Sep</th>
                    <th class="col-month">Oct</th>
                    <th class="col-month">Nov</th>
                    <th class="col-month">Dec</th>
                    <th class="col-overall">Overall</th>
                    <th class="col-perf">Perf%</th>
                    <th class="col-variance">Variance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outputEntries as $entry)
                    @php
                        $pct      = $entry->performance_pct;
                        $chipCls  = $pct >= 100 ? 'chip-exceed'
                                  : ($pct >= 85  ? 'chip-ontrack'
                                  : ($pct >= 70  ? 'chip-slight'
                                  :                'chip-need'));
                        $variance = $entry->variance;
                        $varColor = $variance <= 0 ? '#1565c0' : ($variance < ($entry->target * 0.15) ? '#e65100' : '#c62828');
                    @endphp
                    <tr>
                        @if($isSuperAdmin)
                            <td class="left">{{ $entry->ip->short_name ?? ($entry->ip->name ?? '—') }}</td>
                        @endif
                        <td class="left">{{ $entry->indicator->indicator_name ?? '—' }}</td>
                        <td class="ctr">{{ $entry->disaggregation ?: '—' }}</td>
                        <td class="left">{{ $entry->location_display }}</td>
                        <td class="num" style="font-weight:bold;">{{ number_format($entry->target, 0) }}</td>

                        @foreach(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'] as $m)
                            <td class="num" style="color:{{ ($entry->$m && $entry->$m > 0) ? '#003d80' : '#aaa' }};">
                                {{ $entry->$m !== null ? number_format($entry->$m, 0) : '—' }}
                            </td>
                        @endforeach

                        <td class="num" style="font-weight:bold; color:#003d80;">
                            {{ number_format($entry->overall, 0) }}
                        </td>
                        <td class="ctr">
                            <span class="chip {{ $chipCls }}">{{ $pct }}%</span>
                        </td>
                        <td class="num" style="color:{{ $varColor }}; font-weight:bold;">
                            {{ $variance <= 0 ? '+'.number_format(abs($variance), 0) : '-'.number_format($variance, 0) }}
                        </td>
                    </tr>
                @endforeach

                {{-- Output totals row --}}
                <tr class="totals-row">
                    @if($isSuperAdmin) <td class="ctr">&mdash;</td> @endif
                    <td colspan="3" class="left" style="padding-left:6px;">
                        {{ strtoupper($outputLabel) }} TOTAL ({{ $outputEntries->count() }} entries)
                    </td>
                    <td class="num">{{ number_format($outTarget, 0) }}</td>
                    @foreach(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'] as $m)
                        <td class="num">{{ number_format($outputEntries->sum($m), 0) }}</td>
                    @endforeach
                    <td class="num">{{ number_format($outActual, 0) }}</td>
                    <td class="ctr" style="font-weight:bold;">{{ $outPerf }}%</td>
                    <td class="num">
                        @php $outVariance = $outTarget - $outActual; @endphp
                        {{ $outVariance <= 0 ? '+'.number_format(abs($outVariance), 0) : '-'.number_format($outVariance, 0) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@empty
    <div class="no-records">No KPI data entry records found for {{ $year }}.</div>
@endforelse

{{-- ── Grand Total ─────────────────────────────────────────────────────────── --}}
@if($entries->count() > 1)
<table class="grand-table">
    <thead>
        <tr>
            @if($isSuperAdmin) <th class="col-ip">&nbsp;</th> @endif
            <th colspan="3" class="left" style="padding-left:6px; font-size:7.5pt;">
                GRAND TOTAL &mdash; ALL OUTPUTS ({{ $entries->count() }} entries)
            </th>
            <th class="col-target num">{{ number_format($totalTarget, 0) }}</th>
            @foreach(['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'] as $m)
                <th class="col-month num" style="font-size:6pt;">{{ number_format($entries->sum($m), 0) }}</th>
            @endforeach
            <th class="col-overall num">{{ number_format($totalOverall, 0) }}</th>
            <th class="col-perf ctr">{{ $avgPerf }}%</th>
            <th class="col-variance num">
                @php $gv = $totalTarget - $totalOverall; @endphp
                {{ $gv <= 0 ? '+'.number_format(abs($gv), 0) : '-'.number_format($gv, 0) }}
            </th>
        </tr>
    </thead>
</table>
@endif

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
        <div class="footer-center">IP KPI Data Entry Report &bull; {{ $year }}</div>
        <div class="footer-right">Generated by {{ $generatedBy }} &bull; {{ $generatedAt }}</div>
    </div>
</div>

</body>
</html>
