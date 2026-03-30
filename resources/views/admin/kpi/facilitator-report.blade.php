<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facilitator KPI Data Report {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { size: A4 landscape; margin: 20mm 25mm 22mm 25mm; }

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

        /* ── Indicator section ────────────────────────────────────────────── */
        .indicator-block { margin-bottom: 10px; }
        .indicator-header {
            background: #003d80;
            color: #fff;
            padding: 4px 10px;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .indicator-stats {
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
        .col-facilitator{ width: 13%; }
        .col-district   { width: 8%;  }
        .col-subcounty  { width: 8%;  }
        .col-group      { width: 12%; }
        .col-disagg     { width: 6%;  }
        .col-date       { width: 8%;  }
        .col-value      { width: 6%;  }
        .col-comments   { width: 18%; }

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
            padding: 5px 0;
            border-top: 2px solid #003d80;
            font-size: 6pt;
            color: #444;
        }
        .footer-inner { display: table; width: 100%; }
        .footer-left  { display: table-cell; text-align: left; width: 40%; }
        .footer-center { display: table-cell; text-align: center; width: 30%; }
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
            <div class="report-title">Facilitator KPI Data Report &mdash; {{ $year }}</div>
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
    $totalEntries       = $entries->count();
    $totalValue         = $entries->sum('value');
    $uniqueFacilitators = $entries->pluck('facilitator_id')->unique()->count();
    $uniqueGroups       = $entries->pluck('group_id')->unique()->filter()->count();
    $uniqueDistricts    = $entries->pluck('district')->unique()->filter()->count();
@endphp
<div class="summary-row">
    <div class="sum-card">
        <span class="val">{{ $totalEntries }}</span>
        <span class="lbl">Total Entries</span>
    </div>
    <div class="sum-card highlight">
        <span class="val">{{ number_format($totalValue, 0) }}</span>
        <span class="lbl">Total Value</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ $uniqueFacilitators }}</span>
        <span class="lbl">Facilitators</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ $uniqueGroups }}</span>
        <span class="lbl">FFS Groups</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ $uniqueDistricts }}</span>
        <span class="lbl">Districts</span>
    </div>
    <div class="sum-card">
        <span class="val">{{ $entries->pluck('indicator_id')->unique()->count() }}</span>
        <span class="lbl">Indicators</span>
    </div>
</div>

{{-- ── Entries by Indicator ───────────────────────────────────────────────── --}}
@forelse($byIndicator as $indicatorId => $indicatorEntries)
    @php
        $indicator      = $indicatorEntries->first()->indicator;
        $indicatorTotal = $indicatorEntries->sum('value');
        $outputNo       = $indicator->output_number ?? '?';
        $indicatorName  = $indicator->indicator_name ?? 'Unknown';
    @endphp
    <div class="indicator-block">
        <div class="indicator-header">
            Output {{ $outputNo }} &mdash; {{ $indicatorName }}
            <span class="indicator-stats">
                &nbsp;&mdash;&nbsp;
                Entries: {{ $indicatorEntries->count() }} &nbsp;&bull;&nbsp;
                Total Value: {{ number_format($indicatorTotal, 0) }} &nbsp;&bull;&nbsp;
                Default Target: {{ number_format($indicator->default_target ?? 0, 0) }}
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    @if($isSuperAdmin) <th class="col-ip left">IP</th> @endif
                    <th class="col-facilitator left">Facilitator</th>
                    <th class="col-district left">District</th>
                    <th class="col-subcounty left">Sub-County</th>
                    <th class="col-group left">FFS Group</th>
                    <th class="col-disagg">Disagg.</th>
                    <th class="col-date">Session Date</th>
                    <th class="col-value">Value</th>
                    <th class="col-comments left">Comments</th>
                </tr>
            </thead>
            <tbody>
                @foreach($indicatorEntries as $entry)
                    @php
                        $facName = $entry->facilitator
                            ? trim(($entry->facilitator->first_name ?? '') . ' ' . ($entry->facilitator->last_name ?? ''))
                            : '—';
                    @endphp
                    <tr>
                        @if($isSuperAdmin)
                            <td class="left">{{ $entry->ip->short_name ?? ($entry->ip->name ?? '—') }}</td>
                        @endif
                        <td class="left">{{ $facName }}</td>
                        <td class="left">{{ $entry->district ?: '—' }}</td>
                        <td class="left">{{ $entry->sub_county ?: '—' }}</td>
                        <td class="left">{{ $entry->group->name ?? '—' }}</td>
                        <td class="ctr">{{ $entry->disaggregation ?: '—' }}</td>
                        <td class="ctr">{{ $entry->session_date ? $entry->session_date->format('d M Y') : '—' }}</td>
                        <td class="num" style="font-weight:bold; color:#003d80;">
                            {{ number_format($entry->value, 0) }}
                        </td>
                        <td class="left" style="font-size:6pt; color:#555;">
                            {{ $entry->comments ? \Illuminate\Support\Str::limit($entry->comments, 50) : '—' }}
                        </td>
                    </tr>
                @endforeach

                {{-- Indicator totals row --}}
                <tr class="totals-row">
                    @if($isSuperAdmin) <td class="ctr">&mdash;</td> @endif
                    <td colspan="5" class="left" style="padding-left:6px;">
                        {{ strtoupper($indicatorName) }} &mdash; TOTAL ({{ $indicatorEntries->count() }} entries)
                    </td>
                    <td class="ctr">&mdash;</td>
                    <td class="num" style="font-size:7pt;">{{ number_format($indicatorTotal, 0) }}</td>
                    <td class="left">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    </div>
@empty
    <div class="no-records">No facilitator KPI records found for {{ $year }}.</div>
@endforelse

{{-- ── Grand Total ─────────────────────────────────────────────────────────── --}}
@if($entries->count() > 1)
<table class="grand-table">
    <thead>
        <tr>
            @if($isSuperAdmin) <th class="col-ip">&nbsp;</th> @endif
            <th colspan="5" class="left" style="padding-left:6px; font-size:7.5pt;">
                GRAND TOTAL &mdash; ALL INDICATORS ({{ $entries->count() }} entries)
            </th>
            <th class="col-date ctr">&mdash;</th>
            <th class="col-value num">{{ number_format($totalValue, 0) }}</th>
            <th class="col-comments">&nbsp;</th>
        </tr>
    </thead>
</table>
@endif

{{-- ── Footer ────────────────────────────────────────────────────────────────── --}}
<div class="page-footer">
    <div class="footer-inner">
        <div class="footer-left">FAO FFS MIS &bull; UNJP/UGA/068/EC &mdash; FOSTER</div>
        <div class="footer-center">Facilitator KPI Data Report &bull; {{ $year }}</div>
        <div class="footer-right">Generated by {{ $generatedBy }} &bull; {{ $generatedAt }}</div>
    </div>
</div>

</body>
</html>
