<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @font-face {
        font-family: 'DejaVu Sans';
        font-style: normal;
        font-weight: normal;
        src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format('truetype');
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 9px;
        color: #1a1a2e;
        background: #fff;
    }

    /* ── Header ── */
    .header {
        background: linear-gradient(135deg, #418FDE 0%, #1a5fa8 100%);
        padding: 14px 18px;
        margin-bottom: 0;
    }
    .header-inner { display: table; width: 100%; }
    .header-logo-cell { display: table-cell; width: 70px; vertical-align: middle; }
    .header-logo-cell img { width: 60px; height: auto; }
    .header-text-cell { display: table-cell; vertical-align: middle; padding-left: 12px; }
    .header-org { font-size: 7px; color: rgba(255,255,255,0.85); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
    .header-title { font-size: 15px; font-weight: bold; color: #fff; line-height: 1.2; }
    .header-subtitle { font-size: 8px; color: rgba(255,255,255,0.9); margin-top: 3px; }
    .header-right-cell { display: table-cell; text-align: right; vertical-align: middle; }
    .header-badge { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); border-radius: 4px; padding: 5px 10px; display: inline-block; }
    .header-badge-label { font-size: 6.5px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 0.5px; }
    .header-badge-value { font-size: 12px; font-weight: bold; color: #fff; margin-top: 1px; }

    /* ── Status Banner ── */
    .status-banner { padding: 5px 18px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-submitted { background: #d4edda; color: #155724; border-bottom: 2px solid #28a745; }
    .status-pending   { background: #fff3cd; color: #856404; border-bottom: 2px solid #ffc107; }
    .status-draft     { background: #e2e3e5; color: #383d41; border-bottom: 2px solid #6c757d; }

    /* ── Section wrapper ── */
    .section { margin: 10px 18px; }
    .section-title {
        font-size: 8.5px; font-weight: bold; color: #418FDE;
        text-transform: uppercase; letter-spacing: 0.5px;
        border-bottom: 1.5px solid #418FDE; padding-bottom: 3px; margin-bottom: 7px;
    }

    /* ── Info grid ── */
    .info-grid { display: table; width: 100%; border-collapse: collapse; }
    .info-row  { display: table-row; }
    .info-cell {
        display: table-cell; width: 50%; padding: 4px 8px;
        border: 1px solid #dde3f0; vertical-align: top;
        background: #f7f9fd;
    }
    .info-cell.full { width: 100%; }
    .info-label { font-size: 7px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
    .info-value { font-size: 9px; color: #1a1a2e; font-weight: bold; }

    /* ── KPI strip ── */
    .kpi-strip { background: #0f1f3d; padding: 8px 18px; display: table; width: 100%; }
    .kpi-item { display: table-cell; text-align: center; border-right: 1px solid rgba(255,255,255,0.15); padding: 0 12px; }
    .kpi-item:last-child { border-right: none; }
    .kpi-num  { font-size: 16px; font-weight: bold; color: #f0b429; display: block; }
    .kpi-lbl  { font-size: 6.5px; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-top: 1px; }

    /* ── Table ── */
    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .data-table th {
        background: #418FDE; color: #fff;
        font-size: 7.5px; font-weight: bold; text-align: left;
        padding: 5px 6px; text-transform: uppercase; letter-spacing: 0.3px;
    }
    .data-table td {
        font-size: 8px; padding: 5px 6px;
        border-bottom: 1px solid #e8edf5; vertical-align: top; color: #1a1a2e;
    }
    .data-table tr:nth-child(even) td { background: #f5f8fe; }
    .data-table tr:hover td { background: #eaf0fb; }
    .data-table .center { text-align: center; }
    .data-table .right  { text-align: right; }

    /* ── Risk badges ── */
    .badge { border-radius: 3px; padding: 1px 5px; font-size: 7px; font-weight: bold; display: inline-block; }
    .badge-high    { background: #fde8e8; color: #c53030; border: 1px solid #fc8181; }
    .badge-medium  { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .badge-low     { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
    .badge-none    { background: #e2e8f0; color: #4a5568; border: 1px solid #a0aec0; }
    .badge-score-good     { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .badge-score-moderate { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .badge-score-poor     { background: #fde8e8; color: #c53030; border: 1px solid #fc8181; }

    /* ── Health progress bar ── */
    .score-bar { height: 6px; border-radius: 3px; background: #e5e7eb; margin-top: 2px; }
    .score-fill { height: 6px; border-radius: 3px; }
    .score-fill-good     { background: #10b981; }
    .score-fill-moderate { background: #f59e0b; }
    .score-fill-poor     { background: #ef4444; }

    /* ── Sub-header rows in table ── */
    .sub-header-row td {
        background: #eaf0fb; font-weight: bold; font-size: 8px;
        color: #1a3a6e; padding: 4px 6px;
    }

    /* ── Remarks box ── */
    .remarks-box { background: #f7f9fd; border: 1px solid #dde3f0; border-left: 3px solid #418FDE; padding: 8px 10px; border-radius: 2px; }
    .remarks-text { font-size: 8.5px; color: #1a1a2e; line-height: 1.5; }

    /* ── Footer ── */
    .footer {
        margin-top: 12px; padding: 8px 18px;
        background: #f7f9fd; border-top: 1.5px solid #dde3f0;
        display: table; width: 100%;
    }
    .footer-left  { display: table-cell; font-size: 7px; color: #6c757d; }
    .footer-right { display: table-cell; text-align: right; font-size: 7px; color: #6c757d; }

    .no-data { font-style: italic; color: #999; font-size: 8px; padding: 8px 0; }
    .divider  { border: none; border-top: 1px dashed #dde3f0; margin: 10px 0; }

    /* Photo grid */
    .photo-note { font-size: 8px; color: #6c757d; font-style: italic; padding: 4px 0; }
</style>
</head>
<body>

{{-- ═══════════════════ HEADER ═══════════════════ --}}
<div class="header">
    <div class="header-inner">
        <div class="header-text-cell">
            <div class="header-org">Food and Agriculture Organization &bull; FAO Uganda &bull; FOSTER Programme</div>
            <div class="header-title">AESA Field Observation Report</div>
            <div class="header-subtitle">Agro-Ecosystem Analysis (AESA) &mdash; Session Data Sheet</div>
        </div>
        <div class="header-right-cell">
            <div class="header-badge">
                <div class="header-badge-label">Data Sheet #</div>
                <div class="header-badge-value">{{ $session->data_sheet_number ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Status banner --}}
@php
    $statusClass = match($session->status ?? '') {
        'submitted', 'approved' => 'status-submitted',
        'pending'               => 'status-pending',
        default                 => 'status-draft',
    };
@endphp
<div class="status-banner {{ $statusClass }}">
    Status: {{ $session->status_text ?? ucfirst($session->status ?? 'Draft') }}
    &nbsp;&bull;&nbsp;
    Generated: {{ $generatedAt }}
    @if($generatedBy) &nbsp;&bull;&nbsp; By: {{ $generatedBy }} @endif
</div>

{{-- ═══════════════════ SESSION INFO ═══════════════════ --}}
<div class="section">
    <div class="section-title">Session Information</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Group / FFS Unit</div>
                <div class="info-value">{{ $session->group_name_display ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Observation Date</div>
                <div class="info-value">{{ $session->formatted_date ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Facilitator</div>
                <div class="info-value">{{ $session->facilitator_name_display ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Time</div>
                <div class="info-value">{{ $session->formatted_time ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">District</div>
                <div class="info-value">{{ $session->district_name_display ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Sub-County</div>
                <div class="info-value">{{ $session->sub_county_name_display ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Village</div>
                <div class="info-value">{{ $session->village_name_display ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Observation Location</div>
                <div class="info-value">{{ $session->location_display ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Mini-Group Name</div>
                <div class="info-value">{{ $session->mini_group_name ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Season / Cycle</div>
                <div class="info-value">{{ $session->season ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ KPI STRIP ═══════════════════ --}}
@php
    $animalCount = $session->observations->count();
    $cropCount   = $session->cropObservations->count();
    $highRiskAnimals = $session->observations->where('risk_level', 'High')->count();
    $highRiskCrops   = $session->cropObservations->where('risk_level', 'High')->count();
    $avgAnimalScore  = $animalCount > 0
        ? round($session->observations->avg('health_score'))
        : 0;
    $avgCropScore    = $cropCount > 0
        ? round($session->cropObservations->avg('crop_health_score'))
        : 0;
@endphp
<div class="kpi-strip">
    <div class="kpi-item">
        <span class="kpi-num">{{ $animalCount }}</span>
        <span class="kpi-lbl">Animals Observed</span>
    </div>
    <div class="kpi-item">
        <span class="kpi-num">{{ $cropCount }}</span>
        <span class="kpi-lbl">Crop Plots Observed</span>
    </div>
    <div class="kpi-item">
        <span class="kpi-num">{{ $highRiskAnimals + $highRiskCrops }}</span>
        <span class="kpi-lbl">High Risk Cases</span>
    </div>
    <div class="kpi-item">
        <span class="kpi-num">{{ $avgAnimalScore > 0 ? $avgAnimalScore . '%' : 'N/A' }}</span>
        <span class="kpi-lbl">Avg Animal Health</span>
    </div>
    <div class="kpi-item">
        <span class="kpi-num">{{ $avgCropScore > 0 ? $avgCropScore . '%' : 'N/A' }}</span>
        <span class="kpi-lbl">Avg Crop Health</span>
    </div>
</div>

{{-- ═══════════════════ ANIMAL OBSERVATIONS ═══════════════════ --}}
@if($animalCount > 0)
<div class="section">
    <div class="section-title">Animal Observations ({{ $animalCount }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:8%">Tag ID</th>
                <th style="width:8%">Type</th>
                <th style="width:7%">Breed</th>
                <th style="width:5%">Sex</th>
                <th style="width:7%">Age</th>
                <th style="width:8%">Owner</th>
                <th style="width:10%">Health Status</th>
                <th style="width:7%">Body Cond.</th>
                <th style="width:8%">Main Problem</th>
                <th style="width:8%">Risk</th>
                <th style="width:10%">Immediate Action</th>
                <th style="width:10%">Health Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->observations as $i => $obs)
            @php
                $score = $obs->health_score ?? 0;
                $scoreClass = $score >= 70 ? 'good' : ($score >= 40 ? 'moderate' : 'poor');
                $riskBadge = match(strtolower($obs->risk_level ?? '')) {
                    'high'   => 'badge-high',
                    'medium' => 'badge-medium',
                    'low'    => 'badge-low',
                    default  => 'badge-none',
                };
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $obs->animal_id_tag ?? 'N/A' }}</td>
                <td>{{ $obs->animal_type_display ?? $obs->animal_type ?? 'N/A' }}</td>
                <td>{{ $obs->breed_display ?? $obs->breed ?? 'N/A' }}</td>
                <td class="center">{{ $obs->sex ?? 'N/A' }}</td>
                <td>{{ $obs->age_category ?? 'N/A' }}</td>
                <td>{{ $obs->owner_display ?? $obs->owner_name ?? 'N/A' }}</td>
                <td>{{ $obs->health_status_display ?? $obs->animal_health_status ?? 'N/A' }}</td>
                <td>{{ $obs->body_condition ?? 'N/A' }}</td>
                <td>{{ $obs->main_problem ?? $obs->main_problem_other ?? 'None noted' }}</td>
                <td class="center">
                    <span class="badge {{ $riskBadge }}">
                        {{ ucfirst($obs->risk_level ?? 'N/A') }}
                    </span>
                </td>
                <td>{{ $obs->immediate_action ?? $obs->immediate_action_other ?? 'N/A' }}</td>
                <td>
                    {{ $score }}%
                    <div class="score-bar">
                        <div class="score-fill score-fill-{{ $scoreClass }}" style="width: {{ $score }}%;"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Animal details with full notes --}}
    @foreach($session->observations as $i => $obs)
    @php
        $hasProblem = ($obs->problem_description || $obs->wounds_injuries_description
            || $obs->skin_infection_description || $obs->swelling_description
            || $obs->coughing_description || $obs->diarrhea_description
            || $obs->mini_group_findings || $obs->facilitator_remarks);
    @endphp
    @if($hasProblem)
    <div style="margin-top: 6px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 3px; padding: 6px 8px;">
        <div style="font-size: 8px; font-weight: bold; color: #1a3a6e; margin-bottom: 4px;">
            Animal #{{ $i + 1 }} — {{ $obs->animal_id_tag ?? 'Untagged' }}
            ({{ $obs->animal_type_display ?? $obs->animal_type ?? 'Unknown' }}) — Detailed Notes
        </div>
        @if($obs->problem_description)
        <div style="margin-bottom: 3px;"><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Problem Description: </span><span style="font-size: 8px;">{{ $obs->problem_description }}</span></div>
        @endif
        @if($obs->mini_group_findings)
        <div style="margin-bottom: 3px;"><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Group Findings: </span><span style="font-size: 8px;">{{ $obs->mini_group_findings }}</span></div>
        @endif
        @if($obs->facilitator_remarks)
        <div><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Facilitator Remarks: </span><span style="font-size: 8px;">{{ $obs->facilitator_remarks }}</span></div>
        @endif
    </div>
    @endif
    @endforeach

</div>
@else
<div class="section">
    <div class="section-title">Animal Observations</div>
    <p class="no-data">No animal observations recorded for this session.</p>
</div>
@endif

{{-- ═══════════════════ CROP OBSERVATIONS ═══════════════════ --}}
@if($cropCount > 0)
<div class="section">
    <div class="section-title">Crop Plot Observations ({{ $cropCount }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:7%">Plot ID</th>
                <th style="width:9%">Crop Type</th>
                <th style="width:8%">Variety</th>
                <th style="width:8%">Growth Stage</th>
                <th style="width:8%">Farmer</th>
                <th style="width:7%">Plot Size</th>
                <th style="width:8%">Pest Pressure</th>
                <th style="width:8%">Disease Pressure</th>
                <th style="width:8%">Main Problem</th>
                <th style="width:7%">Risk</th>
                <th style="width:9%">Immediate Action</th>
                <th style="width:7%">Health Score</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->cropObservations as $i => $crop)
            @php
                $score = $crop->crop_health_score ?? 0;
                $scoreClass = $score >= 70 ? 'good' : ($score >= 40 ? 'moderate' : 'poor');
                $riskBadge = match(strtolower($crop->risk_level ?? '')) {
                    'high'   => 'badge-high',
                    'medium' => 'badge-medium',
                    'low'    => 'badge-low',
                    default  => 'badge-none',
                };
                $pestLevel    = $crop->pest_pressure_level    ?? 'N/A';
                $diseaseLevel = $crop->disease_pressure_level ?? 'N/A';
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $crop->plot_id ?? 'N/A' }}</td>
                <td>{{ $crop->crop_type_display ?? $crop->crop_type ?? 'N/A' }}</td>
                <td>{{ $crop->variety ?? 'N/A' }}</td>
                <td>{{ $crop->growth_stage ?? 'N/A' }}</td>
                <td>{{ $crop->farmer_display ?? $crop->farmer_name ?? 'N/A' }}</td>
                <td class="right">{{ $crop->plot_size_acres ? $crop->plot_size_acres . ' ac' : 'N/A' }}</td>
                <td class="center">
                    @php
                        $pl = strtolower($pestLevel);
                        $pestBadge = str_contains($pl, 'high') || str_contains($pl, 'severe') ? 'badge-high'
                            : (str_contains($pl, 'medium') || str_contains($pl, 'moderate') ? 'badge-medium'
                            : (str_contains($pl, 'low') || str_contains($pl, 'minor') ? 'badge-low' : 'badge-none'));
                    @endphp
                    <span class="badge {{ $pestBadge }}">{{ $pestLevel }}</span>
                </td>
                <td class="center">
                    @php
                        $dl = strtolower($diseaseLevel);
                        $disBadge = str_contains($dl, 'high') || str_contains($dl, 'severe') ? 'badge-high'
                            : (str_contains($dl, 'medium') || str_contains($dl, 'moderate') ? 'badge-medium'
                            : (str_contains($dl, 'low') || str_contains($dl, 'minor') ? 'badge-low' : 'badge-none'));
                    @endphp
                    <span class="badge {{ $disBadge }}">{{ $diseaseLevel }}</span>
                </td>
                <td>{{ $crop->main_problem ?? $crop->main_problem_other ?? 'None noted' }}</td>
                <td class="center">
                    <span class="badge {{ $riskBadge }}">{{ ucfirst($crop->risk_level ?? 'N/A') }}</span>
                </td>
                <td>{{ $crop->immediate_action ?? $crop->immediate_action_other ?? 'N/A' }}</td>
                <td>
                    {{ $score }}%
                    <div class="score-bar">
                        <div class="score-fill score-fill-{{ $scoreClass }}" style="width: {{ $score }}%;"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Crop detailed notes --}}
    @foreach($session->cropObservations as $i => $crop)
    @php
        $hasCropNotes = ($crop->problem_description || $crop->mini_group_findings
            || $crop->key_learning_points || $crop->facilitator_remarks
            || $crop->final_agreed_decision);
    @endphp
    @if($hasCropNotes)
    <div style="margin-top: 6px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 3px; padding: 6px 8px;">
        <div style="font-size: 8px; font-weight: bold; color: #065f46; margin-bottom: 4px;">
            Plot #{{ $i + 1 }} — {{ $crop->plot_id ?? 'No ID' }}
            ({{ $crop->crop_type_display ?? $crop->crop_type ?? 'Unknown Crop' }}) — Detailed Notes
        </div>
        @if($crop->problem_description)
        <div style="margin-bottom: 3px;"><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Problem: </span><span style="font-size: 8px;">{{ $crop->problem_description }}</span></div>
        @endif
        @if($crop->mini_group_findings)
        <div style="margin-bottom: 3px;"><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Group Findings: </span><span style="font-size: 8px;">{{ $crop->mini_group_findings }}</span></div>
        @endif
        @if($crop->key_learning_points)
        <div style="margin-bottom: 3px;"><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Key Learnings: </span><span style="font-size: 8px;">{{ $crop->key_learning_points }}</span></div>
        @endif
        @if($crop->final_agreed_decision)
        <div style="margin-bottom: 3px;"><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Agreed Decision: </span><span style="font-size: 8px;">{{ $crop->final_agreed_decision }}</span></div>
        @endif
        @if($crop->facilitator_remarks)
        <div><span style="font-size: 7px; color: #6c757d; text-transform: uppercase;">Facilitator Remarks: </span><span style="font-size: 8px;">{{ $crop->facilitator_remarks }}</span></div>
        @endif
    </div>
    @endif
    @endforeach
</div>
@else
<div class="section">
    <div class="section-title">Crop Plot Observations</div>
    <p class="no-data">No crop observations recorded for this session.</p>
</div>
@endif

{{-- ═══════════════════ FOLLOW-UP ACTIONS SUMMARY ═══════════════════ --}}
@php
    $animalFollowUps = $session->observations->filter(fn($o) => !empty($o->follow_up_date));
    $cropFollowUps   = $session->cropObservations->filter(fn($c) => !empty($c->follow_up_date));
    $hasFollowUps    = $animalFollowUps->isNotEmpty() || $cropFollowUps->isNotEmpty();
@endphp
@if($hasFollowUps)
<div class="section">
    <div class="section-title">Follow-up Action Tracker</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Type</th>
                <th>Subject</th>
                <th>Problem Identified</th>
                <th>Assigned Action</th>
                <th>Responsible Person</th>
                <th>Follow-up Date</th>
                <th>Risk</th>
            </tr>
        </thead>
        <tbody>
            @foreach($animalFollowUps as $obs)
            @php
                $riskBadge = match(strtolower($obs->risk_level ?? '')) {
                    'high' => 'badge-high', 'medium' => 'badge-medium', 'low' => 'badge-low', default => 'badge-none'
                };
            @endphp
            <tr>
                <td><span class="badge badge-none">Animal</span></td>
                <td>{{ $obs->animal_id_tag ?? 'Untagged' }} ({{ $obs->animal_type_display ?? $obs->animal_type ?? 'N/A' }})</td>
                <td>{{ $obs->main_problem ?? $obs->main_problem_other ?? 'N/A' }}</td>
                <td>{{ $obs->immediate_action ?? $obs->immediate_action_other ?? 'N/A' }}</td>
                <td>{{ $obs->responsible_person ?? $obs->responsible_person_other ?? 'N/A' }}</td>
                <td>{{ $obs->follow_up_date ? $obs->follow_up_date->format('d/m/Y') : 'N/A' }}</td>
                <td class="center"><span class="badge {{ $riskBadge }}">{{ ucfirst($obs->risk_level ?? 'N/A') }}</span></td>
            </tr>
            @endforeach
            @foreach($cropFollowUps as $crop)
            @php
                $riskBadge = match(strtolower($crop->risk_level ?? '')) {
                    'high' => 'badge-high', 'medium' => 'badge-medium', 'low' => 'badge-low', default => 'badge-none'
                };
            @endphp
            <tr>
                <td><span class="badge badge-low">Crop</span></td>
                <td>{{ $crop->plot_id ?? 'N/A' }} ({{ $crop->crop_type_display ?? $crop->crop_type ?? 'N/A' }})</td>
                <td>{{ $crop->main_problem ?? $crop->main_problem_other ?? 'N/A' }}</td>
                <td>{{ $crop->immediate_action ?? $crop->immediate_action_other ?? 'N/A' }}</td>
                <td>{{ $crop->responsible_person ?? $crop->responsible_person_other ?? 'N/A' }}</td>
                <td>{{ $crop->follow_up_date ? $crop->follow_up_date->format('d/m/Y') : 'N/A' }}</td>
                <td class="center"><span class="badge {{ $riskBadge }}">{{ ucfirst($crop->risk_level ?? 'N/A') }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ═══════════════════ SESSION NOTES & REMARKS ═══════════════════ --}}
@php
    $sessionNotes = $session->general_observations ?? $session->session_notes ?? null;
    $facilitatorFinal = $session->facilitator_remarks ?? null;
    $keyLessons = $session->key_lessons_learned ?? null;
@endphp
@if($sessionNotes || $facilitatorFinal || $keyLessons)
<div class="section">
    <div class="section-title">Session Notes & Facilitator Remarks</div>
    @if($sessionNotes)
    <div style="margin-bottom: 6px;">
        <div class="info-label" style="font-size:7.5px; margin-bottom:3px;">General Observations</div>
        <div class="remarks-box"><div class="remarks-text">{{ $sessionNotes }}</div></div>
    </div>
    @endif
    @if($keyLessons)
    <div style="margin-bottom: 6px;">
        <div class="info-label" style="font-size:7.5px; margin-bottom:3px;">Key Lessons Learned</div>
        <div class="remarks-box" style="border-left-color: #10b981;"><div class="remarks-text">{{ $keyLessons }}</div></div>
    </div>
    @endif
    @if($facilitatorFinal)
    <div>
        <div class="info-label" style="font-size:7.5px; margin-bottom:3px;">Facilitator's Final Remarks</div>
        <div class="remarks-box" style="border-left-color: #f59e0b;"><div class="remarks-text">{{ $facilitatorFinal }}</div></div>
    </div>
    @endif
</div>
@endif

{{-- ═══════════════════ FOOTER ═══════════════════ --}}
<div class="footer">
    <div class="footer-left">
        FAO Uganda &mdash; FOSTER Programme &mdash; AESA Data Sheet #{{ $session->data_sheet_number ?? 'N/A' }}<br>
        Confidential &mdash; For Internal Use Only
    </div>
    <div class="footer-right">
        Generated: {{ $generatedAt }}<br>
        Powered by FAO FFS MIS &bull; 8Technologies
    </div>
</div>

</body>
</html>
