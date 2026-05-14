<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cycle Report — {{ $group->name ?? 'Group' }} — {{ $cycle->title ?? $cycle->name ?? 'Cycle' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #1a1a2e;
            line-height: 1.4;
        }

        @page { margin: 18mm 15mm 22mm 15mm; size: A4 portrait; }

        .footer {
            position: fixed;
            bottom: -16mm;
            left: 0; right: 0;
            border-top: 2px solid #418FDE;
            padding-top: 4px;
            text-align: center;
            font-size: 6.5px;
            color: #888;
        }

        /* ── Cover / Header ───────────────────────────── */
        .cover {
            background-color: #418FDE;
            color: white;
            padding: 20px;
            margin-bottom: 12px;
            text-align: center;
        }
        .cover-org   { font-size: 9px; opacity: 0.9; margin-bottom: 4px; }
        .cover-title { font-size: 18px; font-weight: bold; margin-bottom: 4px; letter-spacing: 1px; }
        .cover-sub   { font-size: 9.5px; opacity: 0.9; }

        /* ── Info Grid ────────────────────────────────── */
        .info-grid {
            display: table; width: 100%;
            border: 1px solid #c8d8f5;
            margin-bottom: 10px;
        }
        .info-col {
            display: table-cell; width: 50%;
            padding: 7px 10px; vertical-align: top;
        }
        .info-col:first-child { border-right: 1px solid #c8d8f5; }
        .info-row { margin-bottom: 4px; }
        .info-label { font-weight: bold; color: #418FDE; font-size: 7px; display: inline-block; width: 100px; }
        .info-value { font-size: 8px; color: #222; }

        /* ── KPI Strip ────────────────────────────────── */
        .kpi-strip {
            display: table; width: 100%;
            margin-bottom: 10px;
        }
        .kpi-box {
            display: table-cell;
            background-color: #1a1a2e;
            color: white;
            text-align: center;
            padding: 8px 4px;
            border-right: 2px solid #418FDE;
            vertical-align: middle;
        }
        .kpi-box:last-child { border-right: none; }
        .kpi-val { font-size: 11px; font-weight: bold; display: block; margin-bottom: 2px; }
        .kpi-lbl { font-size: 6.5px; text-transform: uppercase; opacity: 0.85; }

        /* ── Section ──────────────────────────────────── */
        .section-title {
            background-color: #418FDE;
            color: white;
            font-size: 8.5px;
            font-weight: bold;
            padding: 4px 8px;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title.dark  { background-color: #1a1a2e; }
        .section-title.green { background-color: #27ae60; }
        .section-title.red   { background-color: #c0392b; }

        /* ── Table ────────────────────────────────────── */
        table {
            width: 100%; border-collapse: collapse; margin-bottom: 10px;
            font-size: 7.5px;
        }
        table thead tr { background-color: #e8f0fe; }
        table thead th {
            padding: 4px 5px; text-align: left;
            font-weight: bold; color: #1a1a2e;
            border: 1px solid #c8d8f5;
            font-size: 7px; text-transform: uppercase;
        }
        table tbody td {
            padding: 3.5px 5px;
            border: 1px solid #e0e0e0; vertical-align: middle;
        }
        table tbody tr:nth-child(even) { background-color: #fafafa; }
        .total-row td {
            background-color: #1a1a2e !important;
            color: white !important;
            font-weight: bold;
            border: 1px solid #111 !important;
            padding: 5px !important;
        }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .amount      { font-weight: bold; color: #1a5fa8; }
        .amount.g    { color: #27ae60; }
        .amount.r    { color: #c0392b; }

        /* ── Progress Bar ─────────────────────────────── */
        .bar-wrap { background-color: #e0e0e0; height: 6px; border-radius: 3px; }
        .bar-fill { background-color: #418FDE; height: 6px; border-radius: 3px; }
        .bar-fill.g { background-color: #27ae60; }

        /* ── Member Savings Table ─────────────────────── */
        .rank-badge {
            display: inline-block;
            background-color: #418FDE;
            color: white;
            font-size: 6.5px;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 2px;
        }

        .page-break { page-break-after: always; }
        .keep-together { page-break-inside: avoid; }
    </style>
</head>
<body>

<div class="footer">
    Cycle Report &bull; {{ $group->name ?? 'Group' }} &bull;
    {{ $cycle->title ?? $cycle->name ?? 'Cycle' }} &bull;
    Generated: {{ $generatedAt }} &bull; {{ $generatedBy }} &bull;
    Confidential — FAO FFS MIS
</div>

{{-- ─────────────────────────── COVER HEADER ───────────────────────────── --}}
<div class="cover">
    <div class="cover-org">FAO — FARMER FIELD SCHOOL MIS &bull; UNJP/UGA/068/EC — FOSTER</div>
    <div class="cover-title">VSLA CYCLE FINANCIAL REPORT</div>
    <div class="cover-sub">{{ $group->name ?? 'VSLA Group' }} &bull; {{ $cycle->title ?? $cycle->name ?? 'Savings Cycle' }}</div>
</div>

{{-- ─────────────────────────── CYCLE INFO ─────────────────────────────── --}}
<div class="info-grid">
    <div class="info-col">
        <div class="info-row"><span class="info-label">VSLA Group:</span><span class="info-value" style="font-weight:bold;">{{ $group->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">District:</span><span class="info-value">{{ $group->district_text ?? $group->district?->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Subcounty:</span><span class="info-value">{{ $group->subcounty_text ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Implementing Partner:</span><span class="info-value">{{ $group->ip_name ?? $group->implementingPartner?->name ?? 'N/A' }}</span></div>
    </div>
    <div class="info-col">
        <div class="info-row"><span class="info-label">Cycle Name:</span><span class="info-value" style="font-weight:bold;">{{ $cycle->title ?? $cycle->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Start Date:</span><span class="info-value">{{ optional($cycle->start_date)->format('d/m/Y') ?? $cycle->cycle_start_date ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Share Value:</span><span class="info-value">UGX {{ number_format($cycle->share_value ?? 0, 0) }} per share</span></div>
        <div class="info-row"><span class="info-label">Report Period:</span><span class="info-value">{{ $reportPeriod }}</span></div>
    </div>
</div>

{{-- ─────────────────────────── KPI STRIP ──────────────────────────────── --}}
<div class="kpi-strip">
    <div class="kpi-box">
        <span class="kpi-val">{{ $stats['total_meetings'] }}</span>
        <span class="kpi-lbl">Meetings Held</span>
    </div>
    <div class="kpi-box">
        <span class="kpi-val">UGX {{ number_format($stats['total_savings'], 0) }}</span>
        <span class="kpi-lbl">Total Savings</span>
    </div>
    <div class="kpi-box">
        <span class="kpi-val">UGX {{ number_format($stats['total_loans_disbursed'], 0) }}</span>
        <span class="kpi-lbl">Loans Disbursed</span>
    </div>
    <div class="kpi-box">
        <span class="kpi-val">UGX {{ number_format($stats['total_loans_repaid'], 0) }}</span>
        <span class="kpi-lbl">Repaid</span>
    </div>
    <div class="kpi-box">
        <span class="kpi-val">{{ $stats['avg_attendance_rate'] }}%</span>
        <span class="kpi-lbl">Avg Attendance</span>
    </div>
    <div class="kpi-box">
        <span class="kpi-val">{{ $stats['total_members'] }}</span>
        <span class="kpi-lbl">Active Members</span>
    </div>
</div>

{{-- ─────────────────────────── MEETING REGISTER ───────────────────────── --}}
<div class="keep-together">
<div class="section-title">1. Meeting Register — All {{ $stats['total_meetings'] }} Meetings</div>
@if($meetings->isNotEmpty())
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:12%">Date</th>
            <th class="text-right" style="width:14%">Savings (UGX)</th>
            <th class="text-right" style="width:14%">Loans Out (UGX)</th>
            <th class="text-right" style="width:13%">Repaid (UGX)</th>
            <th class="text-right" style="width:12%">Fines (UGX)</th>
            <th class="text-center" style="width:10%">Present</th>
            <th class="text-center" style="width:10%">Attend. %</th>
            <th class="text-center" style="width:10%">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($meetings as $m)
        @php
            $total = ($m->members_present ?? 0) + ($m->members_absent ?? 0);
            $rate  = $total > 0 ? round(($m->members_present / $total) * 100, 0) : 0;
        @endphp
        <tr>
            <td class="text-center">{{ $m->meeting_number }}</td>
            <td>{{ $m->meeting_date?->format('d/m/Y') ?? '—' }}</td>
            <td class="text-right amount g">{{ number_format($m->total_savings_collected ?? 0, 0) }}</td>
            <td class="text-right amount r">{{ number_format($m->total_loans_disbursed ?? 0, 0) }}</td>
            <td class="text-right amount">{{ number_format($m->total_loans_repaid ?? 0, 0) }}</td>
            <td class="text-right" style="color:#e67e22;">{{ number_format($m->total_fines_collected ?? 0, 0) }}</td>
            <td class="text-center">{{ $m->members_present ?? 0 }}/{{ $total }}</td>
            <td class="text-center">
                <div>{{ $rate }}%</div>
                <div class="bar-wrap" style="margin-top:2px;">
                    <div class="bar-fill {{ $rate >= 80 ? 'g' : '' }}" style="width:{{ $rate }}%;"></div>
                </div>
            </td>
            <td class="text-center" style="font-size:7px;">{{ ucfirst($m->processing_status ?? 'N/A') }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td class="text-right" colspan="2">CUMULATIVE TOTALS:</td>
            <td class="text-right">{{ number_format($stats['total_savings'], 0) }}</td>
            <td class="text-right">{{ number_format($stats['total_loans_disbursed'], 0) }}</td>
            <td class="text-right">{{ number_format($stats['total_loans_repaid'], 0) }}</td>
            <td class="text-right">{{ number_format($stats['total_fines'], 0) }}</td>
            <td class="text-center">—</td>
            <td class="text-center">{{ $stats['avg_attendance_rate'] }}%</td>
            <td></td>
        </tr>
    </tbody>
</table>
@else
    <div style="text-align:center;padding:12px;color:#999;font-size:8px;background:#fafafa;border:1px dashed #ddd;margin-bottom:10px;">No meetings recorded for this cycle yet.</div>
@endif
</div>

{{-- ─────────────────────────── MEMBER SAVINGS LEDGER ─────────────────── --}}
@if(!empty($memberSavings))
<div class="keep-together">
<div class="section-title green">2. Member Savings Ledger</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Member Name</th>
            <th class="text-right" style="width:20%">Total Saved (UGX)</th>
            <th class="text-right" style="width:18%">Total Shares</th>
            <th class="text-right" style="width:20%">Share Value (UGX)</th>
            <th class="text-center" style="width:12%">Meetings</th>
        </tr>
    </thead>
    <tbody>
        @foreach($memberSavings as $i => $ms)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>
                @if($i < 3)
                    <span class="rank-badge">TOP {{ $i + 1 }}</span>&nbsp;
                @endif
                {{ $ms['name'] ?? 'N/A' }}
            </td>
            <td class="text-right amount g">{{ number_format($ms['total_savings'] ?? 0, 0) }}</td>
            <td class="text-right">{{ $ms['total_shares'] ?? 0 }}</td>
            <td class="text-right amount">{{ number_format(($ms['total_shares'] ?? 0) * ($cycle->share_value ?? 0), 0) }}</td>
            <td class="text-center">{{ $ms['meetings_attended'] ?? 0 }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">GROUP TOTALS:</td>
            <td class="text-right">{{ number_format(collect($memberSavings)->sum('total_savings'), 0) }}</td>
            <td class="text-right">{{ collect($memberSavings)->sum('total_shares') }}</td>
            <td class="text-right">{{ number_format(collect($memberSavings)->sum('total_shares') * ($cycle->share_value ?? 0), 0) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- ─────────────────────────── LOAN PORTFOLIO ─────────────────────────── --}}
@if(!empty($loanPortfolio))
<div class="keep-together">
<div class="section-title red">3. Loan Portfolio</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Borrower</th>
            <th class="text-right" style="width:18%">Disbursed (UGX)</th>
            <th class="text-right" style="width:18%">Repaid (UGX)</th>
            <th class="text-right" style="width:18%">Outstanding (UGX)</th>
            <th class="text-center" style="width:12%">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($loanPortfolio as $i => $lp)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $lp['name'] ?? 'N/A' }}</td>
            <td class="text-right amount">{{ number_format($lp['disbursed'] ?? 0, 0) }}</td>
            <td class="text-right amount g">{{ number_format($lp['repaid'] ?? 0, 0) }}</td>
            <td class="text-right amount r">{{ number_format($lp['outstanding'] ?? 0, 0) }}</td>
            <td class="text-center" style="font-size:7px;">{{ $lp['status'] ?? '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">PORTFOLIO TOTALS:</td>
            <td class="text-right">{{ number_format(collect($loanPortfolio)->sum('disbursed'), 0) }}</td>
            <td class="text-right">{{ number_format(collect($loanPortfolio)->sum('repaid'), 0) }}</td>
            <td class="text-right">{{ number_format(collect($loanPortfolio)->sum('outstanding'), 0) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- ─────────────────────────── GENERATED BY ───────────────────────────── --}}
<div style="margin-top:14px; padding-top:8px; border-top:1px dashed #ccc; font-size:7.5px; color:#777; text-align:center;">
    Report generated on {{ $generatedAt }} by {{ $generatedBy }} &bull;
    This is a computer-generated document. &bull;
    © {{ date('Y') }} FAO FFS MIS — Confidential
</div>

</body>
</html>
