<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Meeting Report - {{ $meeting->group?->name }} #{{ $meeting->meeting_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #1a1a2e;
            line-height: 1.4;
        }

        /* ── Page ────────────────────────────────────── */
        @page { margin: 18mm 15mm 20mm 15mm; size: A4 portrait; }

        .footer {
            position: fixed;
            bottom: -14mm;
            left: 0; right: 0;
            border-top: 1.5px solid #418FDE;
            padding-top: 4px;
            text-align: center;
            font-size: 6.5px;
            color: #888;
        }

        /* ── Header ──────────────────────────────────── */
        .page-header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #418FDE;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-left { display: table-cell; width: 70%; vertical-align: middle; }
        .header-right { display: table-cell; width: 30%; vertical-align: middle; text-align: right; }

        .org-name {
            font-size: 11px;
            font-weight: bold;
            color: #418FDE;
            letter-spacing: 0.5px;
        }
        .project-name {
            font-size: 7.5px;
            color: #555;
            margin-top: 2px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a2e;
            letter-spacing: 0.5px;
        }
        .report-sub {
            font-size: 8px;
            color: #555;
        }

        /* ── Info Section ────────────────────────────── */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 2px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            padding: 7px 10px;
            vertical-align: top;
        }
        .info-col:first-child { border-right: 1px solid #e0e0e0; }
        .info-row { margin-bottom: 4px; }
        .info-label {
            font-weight: bold;
            color: #418FDE;
            font-size: 7.5px;
            display: inline-block;
            width: 95px;
        }
        .info-value { font-size: 8px; color: #222; }

        /* ── Summary Stats ───────────────────────────── */
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            background-color: #f8faff;
            border: 1px solid #c8d8f5;
        }
        .stat-box {
            display: table-cell;
            text-align: center;
            padding: 6px 4px;
            border-right: 1px solid #c8d8f5;
            vertical-align: middle;
        }
        .stat-box:last-child { border-right: none; }
        .stat-val {
            font-size: 10px;
            font-weight: bold;
            color: #418FDE;
            display: block;
            margin-bottom: 2px;
        }
        .stat-val.green { color: #27ae60; }
        .stat-val.red   { color: #e74c3c; }
        .stat-val.orange{ color: #e67e22; }
        .stat-val.dark  { color: #1a1a2e; }
        .stat-lbl {
            font-size: 6.5px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ── Section Titles ──────────────────────────── */
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
        .section-title.green { background-color: #27ae60; }
        .section-title.orange{ background-color: #e67e22; }
        .section-title.red   { background-color: #c0392b; }
        .section-title.teal  { background-color: #16a085; }
        .section-title.purple{ background-color: #8e44ad; }
        .section-title.dark  { background-color: #2c3e50; }

        /* ── Tables ──────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 7.5px;
        }
        table thead tr { background-color: #e8f0fe; }
        table thead th {
            padding: 4px 5px;
            text-align: left;
            font-weight: bold;
            color: #1a1a2e;
            border: 1px solid #c8d8f5;
            font-size: 7px;
            text-transform: uppercase;
        }
        table tbody td {
            padding: 3.5px 5px;
            border: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        table tbody tr:nth-child(even) { background-color: #fafafa; }
        .total-row td {
            background-color: #1a1a2e !important;
            color: white !important;
            font-weight: bold;
            border: 1px solid #111 !important;
            padding: 5px !important;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .badge-present {
            background-color: #d5f5e3;
            color: #1e8449;
            padding: 1px 4px;
            font-size: 6.5px;
            font-weight: bold;
        }
        .badge-absent {
            background-color: #fdecea;
            color: #c0392b;
            padding: 1px 4px;
            font-size: 6.5px;
            font-weight: bold;
        }
        .amount { font-weight: bold; color: #1a5fa8; }
        .amount.income { color: #27ae60; }
        .amount.expense { color: #c0392b; }

        /* ── Attendance summary bar ──────────────────── */
        .attendance-bar {
            display: table;
            width: 100%;
            margin-bottom: 6px;
            font-size: 7.5px;
        }
        .att-cell { display: table-cell; padding: 4px 8px; text-align: center; }
        .att-present { background-color: #d5f5e3; color: #1e8449; font-weight: bold; }
        .att-absent  { background-color: #fdecea; color: #c0392b; font-weight: bold; }
        .att-rate    { background-color: #e8f0fe; color: #1a5fa8; font-weight: bold; }

        /* ── Action Plans ────────────────────────────── */
        .action-row { margin-bottom: 5px; padding: 4px 8px; border-left: 3px solid #418FDE; background-color: #f8faff; }
        .action-row.done  { border-left-color: #27ae60; background-color: #f0fff4; }
        .action-row.pending { border-left-color: #e67e22; background-color: #fffbf0; }
        .action-desc { font-size: 8px; color: #1a1a2e; margin-bottom: 2px; }
        .action-meta { font-size: 7px; color: #777; }

        /* ── No-data ─────────────────────────────────── */
        .no-data {
            text-align: center;
            padding: 10px;
            color: #999;
            font-size: 8px;
            background-color: #f9f9f9;
            border: 1px dashed #ddd;
            margin-bottom: 10px;
        }

        /* ── Page break ─────────────────────────────── */
        .page-break { page-break-after: always; }
        .keep-together { page-break-inside: avoid; }
    </style>
</head>
<body>

{{-- ─────────────────────────── FOOTER (fixed) ─────────────────────────── --}}
<div class="footer">
    <strong>VSLA Meeting Report</strong> &bull;
    {{ $meeting->group?->name ?? 'Unknown Group' }} &bull;
    Meeting #{{ $meeting->meeting_number }} &bull;
    {{ $meeting->meeting_date?->format('d/m/Y') }} &bull;
    Generated: {{ $generatedAt }} &bull;
    Generated by: {{ $generatedBy }}
    &bull; Confidential — FAO FFS MIS
</div>

{{-- ─────────────────────────── HEADER ─────────────────────────────────── --}}
<div class="page-header">
    <div class="header-left">
        <div class="org-name">FAO — FARMER FIELD SCHOOL MIS</div>
        <div class="project-name">UNJP/UGA/068/EC — FOSTER | Food Security and Resilience in Karamoja, Uganda</div>
    </div>
    <div class="header-right">
        <div class="report-title">MEETING REPORT</div>
        <div class="report-sub">Meeting #{{ $meeting->meeting_number }}</div>
    </div>
</div>

{{-- ─────────────────────────── MEETING INFO ────────────────────────────── --}}
<div class="info-grid">
    <div class="info-col">
        <div class="info-row"><span class="info-label">VSLA Group:</span><span class="info-value text-bold">{{ $meeting->group?->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Savings Cycle:</span><span class="info-value">{{ $meeting->cycle?->title ?? $meeting->cycle?->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">District:</span><span class="info-value">{{ $meeting->group?->district_text ?? ($meeting->group?->district?->name ?? 'N/A') }}</span></div>
        <div class="info-row"><span class="info-label">Subcounty:</span><span class="info-value">{{ $meeting->group?->subcounty_text ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Implementing Partner:</span><span class="info-value">{{ $meeting->implementingPartner?->name ?? $meeting->group?->ip_name ?? 'N/A' }}</span></div>
    </div>
    <div class="info-col">
        <div class="info-row"><span class="info-label">Meeting Number:</span><span class="info-value text-bold">#{{ $meeting->meeting_number }}</span></div>
        <div class="info-row"><span class="info-label">Meeting Date:</span><span class="info-value text-bold">{{ $meeting->meeting_date?->format('l, d F Y') ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Processing Status:</span><span class="info-value">{{ ucfirst($meeting->processing_status ?? 'unknown') }}</span></div>
        <div class="info-row"><span class="info-label">Submitted By:</span><span class="info-value">{{ $meeting->creator?->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Received At:</span><span class="info-value">{{ $meeting->received_at?->format('d/m/Y H:i') ?? 'N/A' }}</span></div>
    </div>
</div>

{{-- ─────────────────────────── FINANCIAL SUMMARY ──────────────────────── --}}
<div class="stats-grid">
    <div class="stat-box">
        <span class="stat-val green">UGX {{ number_format($meeting->total_savings_collected ?? 0, 0) }}</span>
        <span class="stat-lbl">Savings</span>
    </div>
    <div class="stat-box">
        <span class="stat-val green">UGX {{ number_format($meeting->total_welfare_collected ?? 0, 0) }}</span>
        <span class="stat-lbl">Welfare</span>
    </div>
    <div class="stat-box">
        <span class="stat-val green">UGX {{ number_format($meeting->total_social_fund_collected ?? 0, 0) }}</span>
        <span class="stat-lbl">Social Fund</span>
    </div>
    <div class="stat-box">
        <span class="stat-val orange">UGX {{ number_format($meeting->total_fines_collected ?? 0, 0) }}</span>
        <span class="stat-lbl">Fines</span>
    </div>
    <div class="stat-box">
        <span class="stat-val red">UGX {{ number_format($meeting->total_loans_disbursed ?? 0, 0) }}</span>
        <span class="stat-lbl">Loans Out</span>
    </div>
    <div class="stat-box">
        <span class="stat-val dark">UGX {{ number_format($meeting->total_loans_repaid ?? 0, 0) }}</span>
        <span class="stat-lbl">Repaid</span>
    </div>
    <div class="stat-box">
        @php
            $netCash = ($meeting->total_savings_collected ?? 0)
                + ($meeting->total_welfare_collected ?? 0)
                + ($meeting->total_social_fund_collected ?? 0)
                + ($meeting->total_fines_collected ?? 0)
                + ($meeting->total_loans_repaid ?? 0)
                - ($meeting->total_loans_disbursed ?? 0);
        @endphp
        <span class="stat-val {{ $netCash >= 0 ? 'green' : 'red' }}">UGX {{ number_format($netCash, 0) }}</span>
        <span class="stat-lbl">Net Cash</span>
    </div>
    <div class="stat-box">
        <span class="stat-val">{{ $meeting->members_present ?? 0 }}/{{ ($meeting->members_present ?? 0) + ($meeting->members_absent ?? 0) }}</span>
        <span class="stat-lbl">Attendance</span>
    </div>
</div>

{{-- ─────────────────────────── ATTENDANCE ─────────────────────────────── --}}
<div class="keep-together">
<div class="section-title">1. Attendance Register</div>
@php
    $attendanceData = $meeting->attendance_data ?? [];
    $present = collect($attendanceData)->filter(fn($r) => filter_var($r['isPresent'] ?? $r['is_present'] ?? false, FILTER_VALIDATE_BOOLEAN));
    $absent  = collect($attendanceData)->filter(fn($r) => !filter_var($r['isPresent'] ?? $r['is_present'] ?? false, FILTER_VALIDATE_BOOLEAN));
@endphp
<div class="attendance-bar">
    <div class="att-cell att-present">Present: {{ $present->count() }}</div>
    <div class="att-cell att-absent">Absent: {{ $absent->count() }}</div>
    <div class="att-cell att-rate">
        Attendance Rate:
        {{ count($attendanceData) > 0 ? round(($present->count() / count($attendanceData)) * 100, 1) : 0 }}%
    </div>
</div>

@if(count($attendanceData) > 0)
    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:45%">Member Name</th>
                <th style="width:20%" class="text-center">Status</th>
                <th style="width:30%">Notes / Reason</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendanceData as $i => $rec)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $rec['memberName'] ?? $rec['member_name'] ?? 'Member #' . ($rec['memberId'] ?? $rec['member_id'] ?? '?') }}</td>
                <td class="text-center">
                    @if(filter_var($rec['isPresent'] ?? $rec['is_present'] ?? false, FILTER_VALIDATE_BOOLEAN))
                        <span class="badge-present">PRESENT</span>
                    @else
                        <span class="badge-absent">ABSENT</span>
                    @endif
                </td>
                <td>{{ $rec['reason'] ?? $rec['notes'] ?? $rec['absent_reason'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="no-data">No attendance data recorded for this meeting.</div>
@endif
</div>

{{-- ─────────────────────────── SAVINGS TRANSACTIONS ───────────────────── --}}
@php
    $txData = collect($meeting->transactions_data ?? []);
    $savings    = $txData->filter(fn($t) => ($t['accountType'] ?? $t['account_type'] ?? '') === 'savings');
    $welfare    = $txData->filter(fn($t) => in_array($t['accountType'] ?? $t['account_type'] ?? '', ['welfare', 'social_welfare']));
    $socialFund = $txData->filter(fn($t) => in_array($t['accountType'] ?? $t['account_type'] ?? '', ['social_fund', 'socialFund']));
    $fines      = $txData->filter(fn($t) => ($t['accountType'] ?? $t['account_type'] ?? '') === 'fine');
@endphp

@if($savings->isNotEmpty())
<div class="keep-together">
<div class="section-title green">2. Savings Contributions</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Member Name</th>
            <th class="text-right" style="width:22%">Amount (UGX)</th>
            <th style="width:28%">Description</th>
        </tr>
    </thead>
    <tbody>
        @foreach($savings as $i => $tx)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $tx['memberName'] ?? $tx['member_name'] ?? 'N/A' }}</td>
            <td class="text-right amount income">{{ number_format($tx['amount'] ?? 0, 0) }}</td>
            <td>{{ $tx['description'] ?? $tx['notes'] ?? '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">TOTAL SAVINGS:</td>
            <td class="text-right">UGX {{ number_format($savings->sum(fn($t) => $t['amount'] ?? 0), 0) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- Share Purchases --}}
@php $sharePurchases = collect($meeting->share_purchases_data ?? []); @endphp
@if($sharePurchases->isNotEmpty())
<div class="keep-together">
<div class="section-title green">3. Share Purchases</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Member Name</th>
            <th class="text-right" style="width:15%">Shares</th>
            <th class="text-right" style="width:22%">Amount (UGX)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sharePurchases as $i => $sp)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $sp['memberName'] ?? $sp['member_name'] ?? 'N/A' }}</td>
            <td class="text-right">{{ $sp['shares'] ?? $sp['numberOfShares'] ?? '—' }}</td>
            <td class="text-right amount income">{{ number_format($sp['amount'] ?? 0, 0) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">TOTAL SHARE VALUE:</td>
            <td class="text-right">{{ $sharePurchases->sum(fn($s) => $s['shares'] ?? $s['numberOfShares'] ?? 0) }} shares</td>
            <td class="text-right">UGX {{ number_format($sharePurchases->sum(fn($s) => $s['amount'] ?? 0), 0) }}</td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- Welfare & Social Fund --}}
@if($welfare->isNotEmpty() || $socialFund->isNotEmpty())
<div class="keep-together">
<div class="section-title teal">4. Welfare &amp; Social Fund</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Member Name</th>
            <th class="text-center" style="width:18%">Type</th>
            <th class="text-right" style="width:22%">Amount (UGX)</th>
        </tr>
    </thead>
    <tbody>
        @php $wsCombined = $welfare->merge($socialFund)->values(); @endphp
        @foreach($wsCombined as $i => $tx)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $tx['memberName'] ?? $tx['member_name'] ?? 'N/A' }}</td>
            <td class="text-center">{{ ucfirst(str_replace('_', ' ', $tx['accountType'] ?? $tx['account_type'] ?? '')) }}</td>
            <td class="text-right amount income">{{ number_format($tx['amount'] ?? 0, 0) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="3" class="text-right">TOTAL:</td>
            <td class="text-right">UGX {{ number_format($wsCombined->sum(fn($t) => $t['amount'] ?? 0), 0) }}</td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- Fines --}}
@if($fines->isNotEmpty())
<div class="keep-together">
<div class="section-title orange">5. Fines Collected</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Member Name</th>
            <th class="text-right" style="width:22%">Fine Amount (UGX)</th>
            <th>Reason</th>
        </tr>
    </thead>
    <tbody>
        @foreach($fines as $i => $f)
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $f['memberName'] ?? $f['member_name'] ?? 'N/A' }}</td>
            <td class="text-right amount expense">{{ number_format($f['amount'] ?? 0, 0) }}</td>
            <td>{{ $f['description'] ?? $f['reason'] ?? '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">TOTAL FINES:</td>
            <td class="text-right">UGX {{ number_format($fines->sum(fn($f) => $f['amount'] ?? 0), 0) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- ─────────────────────────── LOANS DISBURSED ────────────────────────── --}}
@php $loansData = collect($meeting->loans_data ?? []); @endphp
@if($loansData->isNotEmpty())
<div class="keep-together">
<div class="section-title red">6. Loans Disbursed</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Borrower</th>
            <th class="text-right" style="width:20%">Principal (UGX)</th>
            <th class="text-right" style="width:14%">Interest %</th>
            <th class="text-center" style="width:12%">Duration</th>
            <th class="text-right" style="width:18%">Total Due (UGX)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($loansData as $i => $loan)
        @php
            $principal = $loan['amount'] ?? $loan['principal'] ?? $loan['loanAmount'] ?? 0;
            $rate = $loan['interestRate'] ?? $loan['interest_rate'] ?? 0;
            $due = $loan['totalDue'] ?? $loan['total_due'] ?? round($principal * (1 + $rate / 100), 0);
        @endphp
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $loan['memberName'] ?? $loan['member_name'] ?? 'N/A' }}</td>
            <td class="text-right amount expense">{{ number_format($principal, 0) }}</td>
            <td class="text-right">{{ $rate }}%</td>
            <td class="text-center">{{ $loan['duration'] ?? $loan['durationWeeks'] ?? '—' }}</td>
            <td class="text-right amount">{{ number_format($due, 0) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">TOTAL DISBURSED:</td>
            <td class="text-right">UGX {{ number_format($loansData->sum(fn($l) => $l['amount'] ?? $l['principal'] ?? $l['loanAmount'] ?? 0), 0) }}</td>
            <td colspan="3"></td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- ─────────────────────────── LOAN REPAYMENTS ────────────────────────── --}}
@php $repayments = collect($meeting->loan_repayments_data ?? []); @endphp
@if($repayments->isNotEmpty())
<div class="keep-together">
<div class="section-title dark">7. Loan Repayments</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Member</th>
            <th class="text-right" style="width:19%">Principal (UGX)</th>
            <th class="text-right" style="width:19%">Interest (UGX)</th>
            <th class="text-right" style="width:19%">Total Paid (UGX)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($repayments as $i => $rep)
        @php
            $principal = $rep['principalAmount'] ?? $rep['principal_amount'] ?? $rep['principal'] ?? 0;
            $interest  = $rep['interestAmount']  ?? $rep['interest_amount']  ?? $rep['interest']  ?? 0;
            $total     = $rep['totalAmount']     ?? $rep['total_amount']     ?? ($principal + $interest);
        @endphp
        <tr>
            <td class="text-center">{{ $i + 1 }}</td>
            <td>{{ $rep['memberName'] ?? $rep['member_name'] ?? 'N/A' }}</td>
            <td class="text-right amount">{{ number_format($principal, 0) }}</td>
            <td class="text-right" style="color: #e67e22;">{{ number_format($interest, 0) }}</td>
            <td class="text-right amount income">{{ number_format($total, 0) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">TOTALS:</td>
            <td class="text-right">{{ number_format($repayments->sum(fn($r) => $r['principalAmount'] ?? $r['principal_amount'] ?? $r['principal'] ?? 0), 0) }}</td>
            <td class="text-right">{{ number_format($repayments->sum(fn($r) => $r['interestAmount'] ?? $r['interest_amount'] ?? $r['interest'] ?? 0), 0) }}</td>
            <td class="text-right">UGX {{ number_format($meeting->total_loans_repaid ?? 0, 0) }}</td>
        </tr>
    </tbody>
</table>
</div>
@endif

{{-- ─────────────────────────── ACTION PLANS ───────────────────────────── --}}
@php
    $prevPlans = collect($meeting->previous_action_plans_data ?? []);
    $nextPlans = collect($meeting->upcoming_action_plans_data ?? []);
@endphp

@if($prevPlans->isNotEmpty())
<div class="keep-together">
<div class="section-title purple">8. Review: Previous Action Plans</div>
@foreach($prevPlans as $plan)
@php $done = in_array(strtolower($plan['status'] ?? ''), ['done', 'completed', 'complete']); @endphp
<div class="action-row {{ $done ? 'done' : 'pending' }}">
    <div class="action-desc">{{ $plan['description'] ?? $plan['action'] ?? 'N/A' }}</div>
    <div class="action-meta">
        Responsible: {{ $plan['responsible'] ?? $plan['responsiblePerson'] ?? '—' }} &bull;
        Due: {{ $plan['dueDate'] ?? $plan['due_date'] ?? '—' }} &bull;
        Status: <strong>{{ ucfirst($plan['status'] ?? 'pending') }}</strong>
    </div>
</div>
@endforeach
<div style="margin-bottom: 8px;"></div>
</div>
@endif

@if($nextPlans->isNotEmpty())
<div class="keep-together">
<div class="section-title">9. New Action Plans</div>
@foreach($nextPlans as $plan)
<div class="action-row">
    <div class="action-desc">{{ $plan['description'] ?? $plan['action'] ?? 'N/A' }}</div>
    <div class="action-meta">
        Responsible: {{ $plan['responsible'] ?? $plan['responsiblePerson'] ?? '—' }} &bull;
        Due: {{ $plan['dueDate'] ?? $plan['due_date'] ?? '—' }}
    </div>
</div>
@endforeach
<div style="margin-bottom: 8px;"></div>
</div>
@endif

{{-- ─────────────────────────── NOTES ──────────────────────────────────── --}}
@if(!empty($meeting->notes))
<div class="keep-together">
<div class="section-title">10. Meeting Notes</div>
<div style="padding: 8px; border: 1px solid #e0e0e0; background: #fafafa; font-size: 8px; margin-bottom: 10px;">
    {{ $meeting->notes }}
</div>
</div>
@endif

{{-- ─────────────────────────── SIGNATURE BLOCK ────────────────────────── --}}
<div class="keep-together" style="margin-top: 16px;">
<div class="section-title">Certification</div>
<div style="display: table; width: 100%; padding: 10px 0; font-size: 8px;">
    <div style="display: table-cell; width: 33%; text-align: center;">
        <div style="border-bottom: 1px solid #333; margin: 20px 20px 4px;">_</div>
        <div style="font-weight: bold;">Group Chairperson</div>
        <div style="color: #666;">Name &amp; Signature</div>
    </div>
    <div style="display: table-cell; width: 33%; text-align: center;">
        <div style="border-bottom: 1px solid #333; margin: 20px 20px 4px;">_</div>
        <div style="font-weight: bold;">Group Secretary</div>
        <div style="color: #666;">Name &amp; Signature</div>
    </div>
    <div style="display: table-cell; width: 33%; text-align: center;">
        <div style="border-bottom: 1px solid #333; margin: 20px 20px 4px;">_</div>
        <div style="font-weight: bold;">Facilitator</div>
        <div style="color: #666;">Name &amp; Signature</div>
    </div>
</div>
</div>

</body>
</html>
