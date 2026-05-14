<style>
/* ── Stat cards ── follows system-health-check pattern exactly ── */
.ec-stat-card {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 4px;
    background: #fff; border-left: 4px solid #ddd;
    box-shadow: 0 1px 2px rgba(0,0,0,.08);
    margin-bottom: 10px;
}
.ec-stat-icon  { font-size: 22px; opacity: .7; width: 28px; text-align: center; }
.ec-stat-num   { font-size: 22px; font-weight: 700; line-height: 1.1; }
.ec-stat-lbl   { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
.ec-stat-blue  { border-left-color: #3c8dbc; }
.ec-stat-blue  .ec-stat-icon, .ec-stat-blue  .ec-stat-num { color: #3c8dbc; }
.ec-stat-green { border-left-color: #00a65a; }
.ec-stat-green .ec-stat-icon, .ec-stat-green .ec-stat-num { color: #00a65a; }
.ec-stat-cyan   { border-left-color: #00c0ef; }
.ec-stat-cyan   .ec-stat-icon, .ec-stat-cyan   .ec-stat-num { color: #00c0ef; }
.ec-stat-orange { border-left-color: #f39c12; }
.ec-stat-orange .ec-stat-icon, .ec-stat-orange .ec-stat-num { color: #f39c12; }

/* ── Page header banner ── */
.ec-page-header {
    background: #418FDE; color: #fff;
    border-radius: 3px; padding: 14px 18px;
    margin-bottom: 14px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
}
.ec-page-header h2  { margin: 0; font-size: 17px; font-weight: 700; color: #fff; }
.ec-page-header p   { margin: 3px 0 0; font-size: 12px; color: rgba(255,255,255,.85); }
.ec-page-header-right { font-size: 11px; color: rgba(255,255,255,.8); text-align: right; }

/* ── Section panel ── */
.ec-panel {
    background: #fff; border: 1px solid #e4e4e4;
    border-radius: 3px; box-shadow: 0 1px 2px rgba(0,0,0,.06);
    margin-bottom: 14px;
}
.ec-panel-header {
    padding: 9px 14px; border-bottom: 1px solid #f0f0f0;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 6px;
}
.ec-panel-title {
    font-size: 13px; font-weight: 700; color: #333;
    display: flex; align-items: center; gap: 6px;
}
.ec-panel-title .fa { font-size: 13px; }
.ec-panel-body { padding: 12px 14px; }

/* ── Tabs ── */
.ec-nav-tabs { border-bottom: 2px solid #418FDE; margin-bottom: 14px; display: flex; flex-wrap: wrap; gap: 2px; }
.ec-nav-tab {
    padding: 7px 16px; font-size: 13px; font-weight: 600;
    color: #555; cursor: pointer; border-radius: 3px 3px 0 0;
    border: 1px solid transparent; border-bottom: none;
    transition: all .15s;
}
.ec-nav-tab:hover { color: #418FDE; background: #f5f8ff; }
.ec-nav-tab.active {
    color: #418FDE; background: #fff;
    border-color: #ddd #ddd #fff; margin-bottom: -2px;
}
.ec-tab-pane { display: none; }
.ec-tab-pane.active { display: block; }

/* ── Filter bar ── */
.ec-filter-bar {
    background: #f9f9f9; border: 1px solid #e8e8e8;
    border-radius: 3px; padding: 10px 12px; margin-bottom: 12px;
}
.ec-filter-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; }
.ec-filter-row .form-group { margin-bottom: 0; }
.ec-filter-row label { font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: .4px; display: block; margin-bottom: 3px; }
.ec-filter-row .form-control { height: 28px; font-size: 12px; padding: 3px 8px; }
.ec-filter-row .btn { height: 28px; line-height: 1; }

/* ── Quick download strip ── */
.ec-quick-strip {
    background: #f0f5ff; border: 1px solid #d0dff5;
    border-radius: 3px; padding: 8px 12px;
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
    margin-bottom: 12px;
}
.ec-quick-strip .ec-strip-label {
    font-size: 11px; font-weight: 700; color: #5a6178;
    text-transform: uppercase; letter-spacing: .4px;
    margin-right: 4px;
}

/* ── Table tweaks ── */
.ec-table thead th {
    background: #f5f5f5; font-size: 11px; font-weight: 700;
    text-transform: uppercase; color: #888; letter-spacing: .4px;
    padding: 6px 8px; white-space: nowrap;
}
.ec-table tbody td { font-size: 12px; padding: 6px 8px; vertical-align: middle; }
.ec-table tbody tr:hover td { background: #f7f9ff; }

/* ── Status labels ── */
.lbl-completed  { background: #00a65a; }
.lbl-pending    { background: #f39c12; }
.lbl-processing { background: #3c8dbc; }
.lbl-failed     { background: #dd4b39; }
.lbl-submitted  { background: #00a65a; }
.lbl-draft      { background: #aaa; }

/* ── Alert/Info bar ── */
.ec-info {
    background: #d9edf7; border: 1px solid #bce8f1; color: #31708f;
    border-radius: 3px; padding: 7px 12px; font-size: 12px; margin-bottom: 10px;
}
.ec-info strong { color: #286090; }
.ec-info .fa { margin-right: 4px; }

/* ── Section divider ── */
.ec-divider { border: none; border-top: 1px dashed #e0e0e0; margin: 10px 0; }

/* ── Count badge ── */
.ec-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 18px; border-radius: 9px;
    font-size: 11px; font-weight: 700; padding: 0 6px;
    background: #3c8dbc; color: #fff;
}
.ec-count.green  { background: #00a65a; }
.ec-count.orange { background: #f39c12; }

/* ── Empty state ── */
.ec-empty { text-align: center; padding: 24px; color: #aaa; font-size: 12px; font-style: italic; }

/* ── New-tab icon hint ── */
.fa-external-link { font-size: 10px; opacity: .7; margin-left: 2px; }
</style>

{{-- ═══════════════════ PAGE HEADER ═══════════════════ --}}
<div class="ec-page-header">
    <div>
        <h2><i class="fa fa-download"></i> &nbsp;Export Centre</h2>
        <p>Download VSLA meeting reports, cycle summaries &amp; AESA field observation data — PDF or CSV</p>
    </div>
    <div class="ec-page-header-right">
        All reports open in a new tab &nbsp;<i class="fa fa-external-link"></i>
    </div>
</div>

{{-- ═══════════════════ STAT CARDS ═══════════════════ --}}
<div class="row" style="margin-bottom:6px">
    <div class="col-md-3 col-sm-6">
        <div class="ec-stat-card ec-stat-blue">
            <div class="ec-stat-icon"><i class="fa fa-handshake-o"></i></div>
            <div>
                <div class="ec-stat-num">{{ number_format($meetingCount) }}</div>
                <div class="ec-stat-lbl">VSLA Meetings</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="ec-stat-card ec-stat-cyan">
            <div class="ec-stat-icon"><i class="fa fa-refresh"></i></div>
            <div>
                <div class="ec-stat-num">{{ number_format($cyclesWithStats->count()) }}</div>
                <div class="ec-stat-lbl">VSLA Cycles</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="ec-stat-card ec-stat-green">
            <div class="ec-stat-icon"><i class="fa fa-leaf"></i></div>
            <div>
                <div class="ec-stat-num">{{ number_format($aesaCount) }}</div>
                <div class="ec-stat-lbl">AESA Sessions</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="ec-stat-card ec-stat-orange">
            <div class="ec-stat-icon"><i class="fa fa-graduation-cap"></i></div>
            <div>
                <div class="ec-stat-num">{{ number_format($trainingCount) }}</div>
                <div class="ec-stat-lbl">Training Sessions</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ TABS ═══════════════════ --}}
<div class="ec-nav-tabs">
    <div class="ec-nav-tab active" onclick="ecTab('vsla-meetings', this)">
        <i class="fa fa-handshake-o"></i> &nbsp;VSLA Meetings
    </div>
    <div class="ec-nav-tab" onclick="ecTab('vsla-cycles', this)">
        <i class="fa fa-refresh"></i> &nbsp;Cycle Reports
    </div>
    <div class="ec-nav-tab" onclick="ecTab('aesa-sessions', this)">
        <i class="fa fa-leaf"></i> &nbsp;AESA Sessions
    </div>
    <div class="ec-nav-tab" onclick="ecTab('ffs-trainings', this)">
        <i class="fa fa-graduation-cap"></i> &nbsp;FFS Trainings
    </div>
</div>

{{-- ══════════════════════════════════════
     TAB 1: VSLA MEETINGS
══════════════════════════════════════ --}}
<div id="tab-vsla-meetings" class="ec-tab-pane active">

    {{-- Bulk CSV --}}
    <div class="ec-panel">
        <div class="ec-panel-header">
            <div class="ec-panel-title">
                <i class="fa fa-file-excel-o" style="color:#00a65a"></i>
                Bulk CSV Export — All Meetings
            </div>
            <span class="ec-count">{{ number_format($meetingCount) }} total</span>
        </div>
        <div class="ec-panel-body">
            <div class="ec-info">
                <i class="fa fa-info-circle"></i>
                Filter then click <strong>Generate CSV</strong>. Includes attendance rate, savings, loans disbursed/repaid, fines, and net cash per meeting row.
            </div>
            <div class="ec-filter-bar">
                <form method="GET" action="{{ admin_url('export-centre/meetings-csv') }}" target="_blank">
                    <div class="ec-filter-row">
                        <div class="form-group">
                            <label>Group</label>
                            <select name="group_id" class="form-control">
                                <option value="">All Groups</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cycle</label>
                            <select name="cycle_id" class="form-control">
                                <option value="">All Cycles</option>
                                @foreach($cycles as $c)
                                    <option value="{{ $c->id }}">{{ $c->cycle_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-download"></i> Generate CSV
                                <i class="fa fa-external-link"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Per-meeting PDF table --}}
    <div class="ec-panel">
        <div class="ec-panel-header">
            <div class="ec-panel-title">
                <i class="fa fa-file-pdf-o" style="color:#dd4b39"></i>
                Per-Meeting PDF Reports
            </div>
            <span style="font-size:11px;color:#999">Latest 50 completed meetings</span>
        </div>
        <div class="ec-panel-body" style="padding:0">
            @if($recentMeetings->isEmpty())
                <div class="ec-empty"><i class="fa fa-inbox fa-2x"></i><br>No completed meetings found.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover ec-table" style="margin-bottom:0">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>Meeting</th>
                            <th>Group</th>
                            <th>Cycle</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th width="110" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($recentMeetings as $i => $m)
                    <tr>
                        <td class="text-muted text-center">{{ $i + 1 }}</td>
                        <td><strong>Meeting {{ $m->meeting_number }}</strong></td>
                        <td>{{ $m->group->name ?? '—' }}</td>
                        <td>{{ $m->cycle->cycle_name ?? '—' }}</td>
                        <td>{{ $m->meeting_date ? \Carbon\Carbon::parse($m->meeting_date)->format('d M Y') : '—' }}</td>
                        <td>
                            @php $st = strtolower($m->processing_status ?? 'unknown'); @endphp
                            <span class="label lbl-{{ $st }}">{{ ucfirst($st) }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ admin_url('export-centre/meeting-pdf/' . $m->id) }}"
                               target="_blank" rel="noopener"
                               class="btn btn-danger btn-xs">
                                <i class="fa fa-file-pdf-o"></i> PDF
                                <i class="fa fa-external-link"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     TAB 2: VSLA CYCLE REPORTS
══════════════════════════════════════ --}}
<div id="tab-vsla-cycles" class="ec-tab-pane">

    <div class="ec-info">
        <i class="fa fa-info-circle"></i>
        Cycle reports compile all completed meetings: total savings, loan portfolio, member savings leaderboard, attendance heat-map, and repayment rates.
    </div>

    <div class="ec-panel">
        <div class="ec-panel-header">
            <div class="ec-panel-title">
                <i class="fa fa-file-pdf-o" style="color:#dd4b39"></i>
                Cycle Summary Reports
            </div>
            <span style="font-size:11px;color:#999">Latest 50 cycles</span>
        </div>
        <div class="ec-panel-body" style="padding:0">
            @if($cyclesWithStats->isEmpty())
                <div class="ec-empty"><i class="fa fa-inbox fa-2x"></i><br>No VSLA cycles found.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover ec-table" style="margin-bottom:0">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>Cycle Name</th>
                            <th>Group</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Completed Meetings</th>
                            <th class="text-right">Share Value (UGX)</th>
                            <th width="140" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($cyclesWithStats as $i => $c)
                    <tr>
                        <td class="text-muted text-center">{{ $i + 1 }}</td>
                        <td><strong>{{ $c->cycle_name ?? 'Cycle ' . ($i + 1) }}</strong></td>
                        <td>{{ $c->group->name ?? '—' }}</td>
                        <td class="text-center">
                            @if($c->is_active_cycle === 'Yes')
                                <span class="label label-success">Active</span>
                            @else
                                <span class="label label-default">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($c->completed_meetings > 0)
                                <span class="ec-count green">{{ $c->completed_meetings }}</span>
                            @else
                                <span class="text-muted" style="font-size:11px;font-style:italic">none yet</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($c->share_value ?? 0) }}</td>
                        <td class="text-center">
                            @if($c->completed_meetings > 0)
                                <a href="{{ admin_url('export-centre/cycle-report/' . $c->id) }}"
                                   target="_blank" rel="noopener"
                                   class="btn btn-primary btn-xs">
                                    <i class="fa fa-file-pdf-o"></i> Cycle Report
                                    <i class="fa fa-external-link"></i>
                                </a>
                            @else
                                <span class="text-muted" style="font-size:11px">No data yet</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     TAB 3: AESA SESSIONS
══════════════════════════════════════ --}}
<div id="tab-aesa-sessions" class="ec-tab-pane">

    {{-- Bulk CSV --}}
    <div class="ec-panel">
        <div class="ec-panel-header">
            <div class="ec-panel-title">
                <i class="fa fa-file-excel-o" style="color:#00a65a"></i>
                Bulk CSV Export — AESA Observations
            </div>
            <span class="ec-count">{{ number_format($aesaCount) }} total</span>
        </div>
        <div class="ec-panel-body">
            <div class="ec-info">
                <i class="fa fa-info-circle"></i>
                Choose <strong>Animals</strong>, <strong>Crops</strong>, or <strong>All</strong> to export animal health observations, crop plot observations, or both sections in a single file.
            </div>
            <div class="ec-filter-bar">
                <form method="GET" action="{{ admin_url('export-centre/aesa-csv') }}" target="_blank">
                    <div class="ec-filter-row">
                        <div class="form-group">
                            <label>Group</label>
                            <select name="group_id" class="form-control">
                                <option value="">All Groups</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="submitted">Submitted</option>
                                <option value="pending">Pending</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Data Type</label>
                            <select name="type" class="form-control">
                                <option value="animals">Animals Only</option>
                                <option value="crops">Crops Only</option>
                                <option value="all">Animals + Crops</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-download"></i> Generate CSV
                                <i class="fa fa-external-link"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <hr class="ec-divider">
            <div class="ec-quick-strip">
                <span class="ec-strip-label">Quick downloads &mdash; all data, no filter:</span>
                <a href="{{ admin_url('export-centre/aesa-csv?type=animals') }}"
                   target="_blank" rel="noopener"
                   class="btn btn-warning btn-xs">
                    <i class="fa fa-paw"></i> All Animal Observations
                    <i class="fa fa-external-link"></i>
                </a>
                <a href="{{ admin_url('export-centre/aesa-csv?type=crops') }}"
                   target="_blank" rel="noopener"
                   class="btn btn-info btn-xs">
                    <i class="fa fa-leaf"></i> All Crop Observations
                    <i class="fa fa-external-link"></i>
                </a>
                <a href="{{ admin_url('export-centre/aesa-csv?type=all') }}"
                   target="_blank" rel="noopener"
                   class="btn btn-primary btn-xs">
                    <i class="fa fa-database"></i> Full AESA Dataset
                    <i class="fa fa-external-link"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Per-session PDF table --}}
    <div class="ec-panel">
        <div class="ec-panel-header">
            <div class="ec-panel-title">
                <i class="fa fa-file-pdf-o" style="color:#dd4b39"></i>
                Per-Session PDF Field Reports
            </div>
            <span style="font-size:11px;color:#999">Latest 50 sessions</span>
        </div>
        <div class="ec-panel-body" style="padding:0">
            @if($recentAesaSessions->isEmpty())
                <div class="ec-empty"><i class="fa fa-inbox fa-2x"></i><br>No AESA sessions found.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover ec-table" style="margin-bottom:0">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>Data Sheet #</th>
                            <th>Group</th>
                            <th>Facilitator</th>
                            <th>Date</th>
                            <th class="text-center">Animals</th>
                            <th class="text-center">Crops</th>
                            <th>Status</th>
                            <th width="110" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($recentAesaSessions as $i => $s)
                    <tr>
                        <td class="text-muted text-center">{{ $i + 1 }}</td>
                        <td><strong>{{ $s->data_sheet_number ?? 'DS-' . $s->id }}</strong></td>
                        <td>{{ $s->group_name_display ?? '—' }}</td>
                        <td>{{ $s->facilitator_name_display ?? '—' }}</td>
                        <td>{{ $s->formatted_date ?? '—' }}</td>
                        <td class="text-center">
                            @if($s->observations_count > 0)
                                <span class="ec-count orange">{{ $s->observations_count }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($s->crop_observations_count > 0)
                                <span class="ec-count green">{{ $s->crop_observations_count }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php $st = strtolower($s->status ?? 'unknown'); @endphp
                            <span class="label lbl-{{ $st }}">{{ ucfirst($st) }}</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ admin_url('export-centre/aesa-session-pdf/' . $s->id) }}"
                               target="_blank" rel="noopener"
                               class="btn btn-danger btn-xs">
                                <i class="fa fa-file-pdf-o"></i> PDF
                                <i class="fa fa-external-link"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     TAB 4: FFS TRAINING SESSIONS
══════════════════════════════════════ --}}
<div id="tab-ffs-trainings" class="ec-tab-pane">

    {{-- Sessions CSV --}}
    <div class="ec-panel">
        <div class="ec-panel-header">
            <div class="ec-panel-title">
                <i class="fa fa-file-excel-o" style="color:#00a65a"></i>
                Bulk CSV Export — Training Sessions
            </div>
            <span class="ec-count" style="background:#f39c12">{{ number_format($trainingCount) }} total</span>
        </div>
        <div class="ec-panel-body">
            <div class="ec-info">
                <i class="fa fa-info-circle"></i>
                One row per session. Includes session type, date, venue, attendance figures, facilitator, topic, notes, challenges, and recommendations.
            </div>
            <div class="ec-filter-bar">
                <form method="GET" action="{{ admin_url('export-centre/trainings-csv') }}" target="_blank">
                    <div class="ec-filter-row">
                        <div class="form-group">
                            <label>Group</label>
                            <select name="group_id" class="form-control">
                                <option value="">All Groups</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Session Type</label>
                            <select name="session_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="classroom">Classroom</option>
                                <option value="field">Field</option>
                                <option value="demonstration">Demonstration</option>
                                <option value="workshop">Workshop</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Report</label>
                            <select name="report_status" class="form-control">
                                <option value="">All Reports</option>
                                <option value="draft">Draft</option>
                                <option value="submitted">Submitted</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-download"></i> Sessions CSV
                                <i class="fa fa-external-link"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <hr class="ec-divider">

            {{-- GAP Resolutions CSV --}}
            <div class="ec-panel-title" style="margin-bottom:8px">
                <i class="fa fa-file-excel-o" style="color:#f39c12"></i>
                &nbsp;GAP Resolutions CSV Export
            </div>
            <div class="ec-info" style="margin-bottom:8px">
                <i class="fa fa-info-circle"></i>
                One row per resolution/action item. Includes session context, GAP category, responsible person, target date, status, and follow-up notes.
            </div>
            <div class="ec-filter-bar">
                <form method="GET" action="{{ admin_url('export-centre/training-gaps-csv') }}" target="_blank">
                    <div class="ec-filter-row">
                        <div class="form-group">
                            <label>Group</label>
                            <select name="group_id" class="form-control">
                                <option value="">All Groups</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>GAP Category</label>
                            <select name="gap_category" class="form-control">
                                <option value="">All Categories</option>
                                <option value="soil">Soil</option>
                                <option value="water">Water</option>
                                <option value="seeds">Seeds</option>
                                <option value="pest">Pest</option>
                                <option value="harvest">Harvest</option>
                                <option value="storage">Storage</option>
                                <option value="marketing">Marketing</option>
                                <option value="livestock">Livestock</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Resolution Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Session Date From</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Session Date To</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fa fa-download"></i> GAP Resolutions CSV
                                <i class="fa fa-external-link"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <hr class="ec-divider">
            <div class="ec-quick-strip">
                <span class="ec-strip-label">Quick downloads &mdash; all data, no filter:</span>
                <a href="{{ admin_url('export-centre/trainings-csv') }}"
                   target="_blank" rel="noopener"
                   class="btn btn-success btn-xs">
                    <i class="fa fa-graduation-cap"></i> All Sessions
                    <i class="fa fa-external-link"></i>
                </a>
                <a href="{{ admin_url('export-centre/training-gaps-csv') }}"
                   target="_blank" rel="noopener"
                   class="btn btn-warning btn-xs">
                    <i class="fa fa-list-alt"></i> All GAP Resolutions
                    <i class="fa fa-external-link"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Recent sessions table --}}
    <div class="ec-panel">
        <div class="ec-panel-header">
            <div class="ec-panel-title">
                <i class="fa fa-table" style="color:#f39c12"></i>
                Recent Training Sessions
            </div>
            <span style="font-size:11px;color:#999">Latest 50 sessions</span>
        </div>
        <div class="ec-panel-body" style="padding:0">
            @if($recentTrainingSessions->isEmpty())
                <div class="ec-empty"><i class="fa fa-inbox fa-2x"></i><br>No training sessions found.</div>
            @else
            <div class="table-responsive">
                <table class="table table-hover ec-table" style="margin-bottom:0">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>Session Title</th>
                            <th>Group</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th class="text-center">Participants</th>
                            <th class="text-center">GAPs</th>
                            <th>Status</th>
                            <th>Report</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($recentTrainingSessions as $i => $s)
                    <tr>
                        <td class="text-muted text-center">{{ $i + 1 }}</td>
                        <td><strong>{{ $s->title ?? 'Session ' . ($i + 1) }}</strong></td>
                        <td>{{ $s->group->name ?? '—' }}</td>
                        <td>{{ $s->session_type_text ?? ucfirst($s->session_type ?? '—') }}</td>
                        <td>{{ $s->session_date ? $s->session_date->format('d M Y') : '—' }}</td>
                        <td class="text-center">
                            @php $pc = $s->participants_count ?? 0; @endphp
                            @if($pc > 0)
                                <span class="ec-count" style="background:#f39c12">{{ $pc }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $rc = $s->resolutions_count ?? 0; @endphp
                            @if($rc > 0)
                                <span class="ec-count green">{{ $rc }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php $st = strtolower($s->status ?? 'unknown'); @endphp
                            <span class="label lbl-{{ $st }}">{{ ucfirst($st) }}</span>
                        </td>
                        <td>
                            @php $rs = strtolower($s->report_status ?? 'draft'); @endphp
                            <span class="label lbl-{{ $rs }}">{{ ucfirst($rs) }}</span>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function ecTab(name, el) {
    document.querySelectorAll('.ec-nav-tab').forEach(function(t){ t.classList.remove('active'); });
    document.querySelectorAll('.ec-tab-pane').forEach(function(p){ p.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('tab-' + name).classList.add('active');
    history.replaceState(null, '', '#' + name);
}
(function(){
    var hash = window.location.hash.replace('#','');
    var map  = {'vsla-meetings': 0, 'vsla-cycles': 1, 'aesa-sessions': 2, 'ffs-trainings': 3};
    if (hash in map) {
        var tabs = document.querySelectorAll('.ec-nav-tab');
        if (tabs[map[hash]]) ecTab(hash, tabs[map[hash]]);
    }
})();
</script>
