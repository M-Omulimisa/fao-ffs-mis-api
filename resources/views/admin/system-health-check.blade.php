@php
    $checksWithIssues = collect($checks)->filter(fn($c) => count($c['items']) > 0)->count();
    $checksClean = collect($checks)->filter(fn($c) => count($c['items']) === 0)->count();
@endphp

<!-- Summary Stats Row -->
<div class="row shc-stats">
    <div class="col-lg-3 col-sm-6">
        <div class="shc-stat-card shc-stat-danger">
            <div class="shc-stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="shc-stat-body">
                <div class="shc-stat-number">{{ $summary['critical_issues'] }}</div>
                <div class="shc-stat-label">Critical</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="shc-stat-card shc-stat-warning">
            <div class="shc-stat-icon"><i class="fa fa-exclamation-circle"></i></div>
            <div class="shc-stat-body">
                <div class="shc-stat-number">{{ $summary['warning_issues'] }}</div>
                <div class="shc-stat-label">Warnings</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="shc-stat-card shc-stat-info">
            <div class="shc-stat-icon"><i class="fa fa-info-circle"></i></div>
            <div class="shc-stat-body">
                <div class="shc-stat-number">{{ $summary['info_issues'] }}</div>
                <div class="shc-stat-label">Info</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="shc-stat-card shc-stat-success">
            <div class="shc-stat-icon"><i class="fa fa-check-circle"></i></div>
            <div class="shc-stat-body">
                <div class="shc-stat-number">{{ $checksClean }}/{{ count($checks) }}</div>
                <div class="shc-stat-label">Checks Passed</div>
            </div>
        </div>
    </div>
</div>

@if($isSuperAdmin)
<!-- IP Filter -->
<div class="shc-ip-filter">
    <i class="fa fa-building" style="color:#888;font-size:12px;"></i>
    <select id="shc-ip-filter" class="form-control input-sm" style="width:auto;display:inline-block;min-width:200px;font-size:12px;height:26px;padding:2px 8px;">
        <option value="">All Implementing Partners</option>
        @foreach($ips as $ip)
            <option value="{{ $ip->id }}" {{ $filterIpId == $ip->id ? 'selected' : '' }}>{{ $ip->name }}{{ $ip->short_name ? " ({$ip->short_name})" : '' }}</option>
        @endforeach
    </select>
    @if($filterIpId)
        <a href="{{ admin_url('system-health-check') }}" class="btn btn-default btn-xs" title="Clear filter" style="margin-left:4px;"><i class="fa fa-times"></i> Clear</a>
        @php $selectedIp = $ips->firstWhere('id', $filterIpId); @endphp
        <span class="label label-primary" style="font-size:11px;margin-left:4px;">Filtered: {{ $selectedIp->name ?? 'IP #'.$filterIpId }}</span>
    @endif
</div>
@endif

<!-- Toolbar -->
<div class="shc-toolbar">
    <div class="shc-toolbar-left">
        <div class="btn-group btn-group-xs">
            <button class="btn btn-default" onclick="HC.expandAll()" title="Expand All"><i class="fa fa-plus-square-o"></i></button>
            <button class="btn btn-default" onclick="HC.collapseAll()" title="Collapse All"><i class="fa fa-minus-square-o"></i></button>
        </div>
        <div class="btn-group btn-group-xs" style="margin-left:4px;">
            <button class="btn btn-default" onclick="location.reload()" title="Refresh"><i class="fa fa-refresh"></i></button>
        </div>
        <span class="shc-toolbar-summary">{{ $summary['total_issues'] }} issue{{ $summary['total_issues'] !== 1 ? 's' : '' }} across {{ $checksWithIssues }} check{{ $checksWithIssues !== 1 ? 's' : '' }}</span>
    </div>
    <div class="shc-toolbar-right">
        <div class="btn-group btn-group-xs shc-filter-group">
            <button class="btn btn-danger shc-filter active" data-filter="critical" title="Toggle Critical"><i class="fa fa-exclamation-triangle"></i> {{ $summary['critical_issues'] }}</button>
            <button class="btn btn-warning shc-filter active" data-filter="warning" title="Toggle Warnings"><i class="fa fa-exclamation-circle"></i> {{ $summary['warning_issues'] }}</button>
            <button class="btn btn-info shc-filter active" data-filter="info" title="Toggle Info"><i class="fa fa-info-circle"></i> {{ $summary['info_issues'] }}</button>
        </div>
    </div>
</div>

<!-- Health Check Cards -->
<div class="row" id="shc-container">
    @foreach($checks as $checkKey => $check)
        @php $count = count($check['items']); $empty = $count === 0; $resolvedCount = $check['resolved_count'] ?? 0; @endphp
        <div class="col-md-6 shc-card-wrap" data-severity="{{ $check['severity'] }}">
            <div class="shc-card {{ $empty ? 'shc-card-clean' : 'shc-card-'.$check['color'] }}">
                <div class="shc-card-header" onclick="HC.toggle(this)">
                    <div class="shc-card-title">
                        <i class="fa {{ $check['icon'] }} shc-card-icon"></i>
                        <span>{{ $check['title'] }}</span>
                    </div>
                    <div class="shc-card-meta">
                        @if($count > 0)
                            <span class="shc-badge shc-badge-{{ $check['color'] }}">{{ $count }}</span>
                            @if($resolvedCount > 0)
                                <span class="shc-badge shc-badge-resolved" title="{{ $resolvedCount }} resolved (hidden)">{{ $resolvedCount }} <i class="fa fa-check-circle"></i></span>
                            @endif
                            <label class="shc-select-all" onclick="event.stopPropagation();">
                                <input type="checkbox" class="select-all-check" data-check="{{ $checkKey }}" data-entity="{{ $check['entity'] ?? 'group' }}"> All
                            </label>
                        @else
                            @if($resolvedCount > 0)
                                <span class="shc-badge shc-badge-resolved" title="{{ $resolvedCount }} resolved (hidden)">{{ $resolvedCount }} <i class="fa fa-check-circle"></i></span>
                            @endif
                            <span class="shc-badge shc-badge-success"><i class="fa fa-check"></i></span>
                        @endif
                        <i class="fa {{ $empty ? 'fa-chevron-right' : 'fa-chevron-down' }} shc-chevron"></i>
                    </div>
                </div>
                <div class="shc-card-body" style="{{ $empty ? 'display:none;' : '' }}">
                    <div class="shc-card-desc">{{ $check['description'] }}</div>

                    @if($count > 0)
                        {{-- Auto-fix toolbar for specific checks --}}
                        <div class="shc-autofix-bar">
                            @if($checkKey === 'orphaned_members')
                                <button class="btn btn-success btn-xs" onclick="HC.autoFix('orphaned_members')"><i class="fa fa-magic"></i> Intelligent Fix</button>
                                <button class="btn btn-danger btn-xs" onclick="HC.quickDeleteAll('orphaned_members', {{ $count }})"><i class="fa fa-trash"></i> Delete All Orphans</button>
                            @elseif($checkKey === 'users_no_ip')
                                <button class="btn btn-success btn-xs" onclick="HC.autoFix('users_no_ip')"><i class="fa fa-magic"></i> Auto-Assign IP from Group</button>
                            @elseif($checkKey === 'groups_no_facilitator')
                                <button class="btn btn-success btn-xs" onclick="HC.autoFix('groups_no_facilitator')"><i class="fa fa-magic"></i> Auto-Assign Facilitators</button>
                            @elseif($checkKey === 'groups_empty')
                                <button class="btn btn-danger btn-xs" onclick="HC.quickDeleteAllEmpty({{ $count }})"><i class="fa fa-trash"></i> Delete All Empty Groups</button>
                            @elseif($checkKey === 'inactive_groups_with_members')
                                <button class="btn btn-success btn-xs" onclick="HC.quickActivateAll({{ $count }})"><i class="fa fa-check"></i> Activate All</button>
                            @endif
                        </div>

                        <div class="shc-items" data-check="{{ $checkKey }}" data-entity="{{ $check['entity'] ?? 'group' }}">
                            @foreach($check['items'] as $idx => $item)
                                @include('admin.health-check-item', [
                                    'item' => $item,
                                    'checkKey' => $checkKey,
                                    'severity' => $check['severity'],
                                    'entity' => $check['entity'] ?? 'group',
                                    'idx' => $idx
                                ])
                            @endforeach
                        </div>
                    @else
                        <div class="shc-clean-msg"><i class="fa fa-check-circle"></i> No issues found</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Batch Actions Toolbar (fixed bottom) -->
<div id="shc-batch" class="shc-batch" style="display:none;">
    <div class="shc-batch-inner">
        <span class="shc-batch-count"><strong id="shc-sel-count">0</strong> selected</span>
        <div class="shc-batch-actions">
            <div class="btn-group btn-group-xs group-actions" style="display:none;">
                <button class="btn btn-danger" onclick="HC.batchDeleteGroups()"><i class="fa fa-trash"></i> Delete</button>
                <button class="btn btn-primary" onclick="HC.showAssignFacilitatorModal()"><i class="fa fa-user-plus"></i> Facilitator</button>
                <button class="btn btn-success" onclick="HC.batchUpdateStatus('Active')"><i class="fa fa-check"></i> Activate</button>
                <button class="btn btn-warning" onclick="HC.batchUpdateStatus('Inactive')"><i class="fa fa-pause"></i> Deactivate</button>
            </div>
            <div class="btn-group btn-group-xs user-actions" style="display:none;">
                <button class="btn btn-danger" onclick="HC.batchDeleteUsers()"><i class="fa fa-trash"></i> Delete</button>
                <button class="btn btn-primary" onclick="HC.showAssignIpModal()"><i class="fa fa-building"></i> Assign IP</button>
                <button class="btn btn-warning" onclick="HC.batchClearField('phone_number')"><i class="fa fa-phone"></i> Clear Phone</button>
                <button class="btn btn-info" onclick="HC.batchClearField('email')"><i class="fa fa-envelope-o"></i> Clear Email</button>
                <button class="btn btn-success" onclick="HC.showMergeModal()"><i class="fa fa-compress"></i> Merge</button>
                <button class="btn btn-danger" onclick="HC.deleteAllOrphans()" title="Delete all orphaned members (no group, excluding admins & facilitators)"><i class="fa fa-user-times"></i> Delete All Orphans</button>
            </div>
            <button class="btn btn-default btn-xs" onclick="HC.clearSelection()"><i class="fa fa-times"></i></button>
        </div>
    </div>
</div>

<!-- Assign Facilitator Modal -->
<div class="modal fade" id="assignFacilitatorModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc;color:#fff;padding:10px 15px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h5 class="modal-title"><i class="fa fa-user-plus"></i> Assign Facilitator</h5>
            </div>
            <div class="modal-body" style="padding:12px 15px;">
                <select class="form-control input-sm" id="facilitator-select">
                    <option value="">-- Select --</option>
                    @foreach($facilitators as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </select>
                <p class="text-muted small" style="margin-top:6px;">Applies to <strong id="facilitator-group-count">0</strong> group(s)</p>
            </div>
            <div class="modal-footer" style="padding:8px 15px;">
                <button class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-sm" onclick="HC.assignFacilitator()"><i class="fa fa-check"></i> Assign</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign IP Modal -->
<div class="modal fade" id="assignIpModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#3c8dbc;color:#fff;padding:10px 15px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h5 class="modal-title"><i class="fa fa-building"></i> Assign IP</h5>
            </div>
            <div class="modal-body" style="padding:12px 15px;">
                <select class="form-control input-sm" id="ip-select">
                    <option value="">-- Select --</option>
                    @foreach($ips as $ip)
                        <option value="{{ $ip->id }}">{{ $ip->name }} ({{ $ip->short_name }})</option>
                    @endforeach
                </select>
                <p class="text-muted small" style="margin-top:6px;">Applies to <strong id="ip-user-count">0</strong> user(s)</p>
            </div>
            <div class="modal-footer" style="padding:8px 15px;">
                <button class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-sm" onclick="HC.assignIp()"><i class="fa fa-check"></i> Assign</button>
            </div>
        </div>
    </div>
</div>

<!-- Merge Users Modal -->
<div class="modal fade" id="mergeUsersModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#00a65a;color:#fff;padding:10px 15px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h5 class="modal-title"><i class="fa fa-compress"></i> Merge Users</h5>
            </div>
            <div class="modal-body" style="padding:12px 15px;">
                <div class="shc-alert-warn"><i class="fa fa-exclamation-triangle"></i> Others will be <strong>deleted</strong>.</div>
                <label class="small">Keep this user:</label>
                <select class="form-control input-sm" id="keep-user-select"></select>
            </div>
            <div class="modal-footer" style="padding:8px 15px;">
                <button class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-sm" onclick="HC.mergeUsers()"><i class="fa fa-compress"></i> Merge</button>
            </div>
        </div>
    </div>
</div>

<!-- Auto-Fix Modal -->
<div class="modal fade" id="autoFixModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#00a65a;color:#fff;padding:10px 15px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h5 class="modal-title"><i class="fa fa-magic"></i> <span id="auto-fix-title">Intelligent Fix</span></h5>
            </div>
            <div class="modal-body" style="padding:12px 15px;max-height:500px;overflow-y:auto;">
                <div id="auto-fix-loading" class="text-center" style="padding:30px;">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color:#00a65a;"></i>
                    <p style="margin-top:10px;color:#666;">Scanning for potential fixes&hellip;</p>
                </div>
                <div id="auto-fix-results" style="display:none;">
                    <div id="auto-fix-summary" class="shc-alert-info" style="margin-bottom:10px;"></div>
                    <div id="auto-fix-table-wrap"></div>
                </div>
            </div>
            <div class="modal-footer" id="auto-fix-footer" style="display:none;padding:8px 15px;">
                <button class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                <button class="btn btn-success btn-sm" id="auto-fix-apply-btn" onclick="HC.applyAutoFixAction()">
                    <i class="fa fa-check"></i> Apply Selected (<span id="auto-fix-count">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Action Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background:#dd4b39;color:#fff;padding:10px 15px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h5 class="modal-title" id="confirm-action-title"><i class="fa fa-exclamation-triangle"></i> Confirm</h5>
            </div>
            <div class="modal-body" style="padding:12px 15px;">
                <p id="confirm-action-msg"></p>
            </div>
            <div class="modal-footer" style="padding:8px 15px;">
                <button class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm" id="confirm-action-btn" onclick="HC.executeConfirmedAction()">
                    <i class="fa fa-check"></i> <span id="confirm-action-label">Confirm</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ═══════════════  STAT CARDS  ═══════════════ */
.shc-stats { margin-bottom: 10px; }
.shc-stats .col-lg-3 { padding-left: 5px; padding-right: 5px; }

/* ═══════════════  IP FILTER  ═══════════════ */
.shc-ip-filter {
    display: flex; align-items: center; gap: 6px;
    margin-bottom: 8px; padding: 6px 10px;
    background: #f9f9f9; border: 1px solid #e8e8e8; border-radius: 3px;
}
.shc-stat-card {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-radius: 4px;
    background: #fff; border-left: 4px solid #ddd;
    box-shadow: 0 1px 2px rgba(0,0,0,.08);
    margin-bottom: 8px;
}
.shc-stat-icon { font-size: 22px; opacity: .7; width: 28px; text-align: center; }
.shc-stat-number { font-size: 20px; font-weight: 700; line-height: 1.1; }
.shc-stat-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
.shc-stat-danger  { border-left-color: #dd4b39; }
.shc-stat-danger .shc-stat-icon, .shc-stat-danger .shc-stat-number { color: #dd4b39; }
.shc-stat-warning { border-left-color: #f39c12; }
.shc-stat-warning .shc-stat-icon, .shc-stat-warning .shc-stat-number { color: #f39c12; }
.shc-stat-info    { border-left-color: #00c0ef; }
.shc-stat-info .shc-stat-icon, .shc-stat-info .shc-stat-number { color: #00c0ef; }
.shc-stat-success { border-left-color: #00a65a; }
.shc-stat-success .shc-stat-icon, .shc-stat-success .shc-stat-number { color: #00a65a; }

/* ═══════════════  TOOLBAR  ═══════════════ */
.shc-toolbar {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 10px; padding: 6px 0;
}
.shc-toolbar-left { display: flex; align-items: center; gap: 8px; }
.shc-toolbar-summary { font-size: 12px; color: #888; margin-left: 4px; }
.shc-filter-group .btn { font-size: 11px; padding: 2px 8px; }
.shc-filter { opacity: .45; transition: opacity .15s; }
.shc-filter.active { opacity: 1; }

/* ═══════════════  CHECK CARDS  ═══════════════ */
.shc-card-wrap { padding-left: 5px; padding-right: 5px; margin-bottom: 8px; }
.shc-card {
    background: #fff; border-radius: 3px;
    border: 1px solid #e4e4e4; overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,.06);
}
.shc-card-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 12px; cursor: pointer; user-select: none;
    border-bottom: 1px solid transparent; transition: background .15s;
}
.shc-card-header:hover { background: #fafafa; }
.shc-card-title { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; }
.shc-card-icon { width: 16px; text-align: center; }
.shc-card-meta { display: flex; align-items: center; gap: 8px; font-size: 11px; }
.shc-chevron { font-size: 10px; color: #aaa; transition: transform .2s; }
.shc-card-header[aria-expanded="false"] .shc-chevron { transform: rotate(-90deg); }

.shc-card-danger  .shc-card-header { border-left: 3px solid #dd4b39; }
.shc-card-warning .shc-card-header { border-left: 3px solid #f39c12; }
.shc-card-info    .shc-card-header { border-left: 3px solid #00c0ef; }
.shc-card-clean   .shc-card-header { border-left: 3px solid #00a65a; }
.shc-card-clean   .shc-card-title { color: #888; }

.shc-card-body { padding: 0 12px 8px; }
.shc-card-desc { font-size: 11px; color: #999; padding: 4px 0 6px; line-height: 1.3; }

/* ═══════════════  BADGES  ═══════════════ */
.shc-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px; border-radius: 9px;
    font-size: 11px; font-weight: 600; padding: 0 6px; color: #fff;
}
.shc-badge-danger  { background: #dd4b39; }
.shc-badge-warning { background: #f39c12; }
.shc-badge-info    { background: #00c0ef; }
.shc-badge-success { background: #00a65a; }
.shc-badge-resolved { background: #8bc34a; font-size: 10px; }
.shc-badge-resolved i { margin-left: 2px; font-size: 9px; }

.shc-select-all { font-size: 11px; font-weight: normal; cursor: pointer; margin: 0; color: #888; }
.shc-select-all input { margin-right: 2px; }

/* ═══════════════  ITEMS  ═══════════════ */
.shc-items { margin-top: 2px; }
.shc-item {
    padding: 6px 8px; margin-bottom: 3px;
    border-left: 3px solid #ddd; border-radius: 2px;
    background: #fafafa; font-size: 12px;
    transition: background .15s, border-color .15s;
}
.shc-item:last-child { margin-bottom: 0; }
.shc-item:hover { background: #f0f0f0; }
.shc-item.selected { background: #e3f2fd; border-left-color: #2196f3; }
.shc-item.severity-critical { border-left-color: #dd4b39; }
.shc-item.severity-warning  { border-left-color: #f39c12; }
.shc-item.severity-info     { border-left-color: #00c0ef; }

.shc-item-row {
    display: flex; align-items: center; gap: 6px;
    flex-wrap: wrap;
}
.shc-item-row a { color: #3c8dbc; font-weight: 500; }
.shc-item-row a:hover { text-decoration: underline; }
.shc-item-row .label { font-size: 10px; padding: 1px 5px; }

.shc-item-detail {
    margin-top: 4px; padding-top: 4px;
    border-top: 1px solid #eee; font-size: 11px; color: #777;
}
.shc-item-detail .btn-xs { font-size: 10px; padding: 1px 6px; margin-right: 3px; }

/* ═══════════════  TABLES INSIDE ITEMS  ═══════════════ */
.shc-tbl { width: 100%; border-collapse: collapse; margin-top: 3px; font-size: 11px; }
.shc-tbl th { background: #f5f5f5; padding: 3px 6px; font-weight: 600; text-align: left; border-bottom: 1px solid #e0e0e0; font-size: 10px; text-transform: uppercase; color: #888; }
.shc-tbl td { padding: 3px 6px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.shc-tbl tr:last-child td { border-bottom: none; }
.shc-tbl tr:hover td { background: #f7f7f7; }
.shc-tbl a { color: #3c8dbc; }
.shc-tbl input[type="checkbox"] { margin: 0; }

/* Detail kv pairs */
.shc-kv { display: inline-flex; gap: 4px; margin-right: 10px; }
.shc-kv-label { font-weight: 600; color: #888; }

/* Resolve button */
.shc-resolve-btn { margin-left: auto; font-size: 10px !important; padding: 1px 6px !important; color: #888; border-color: #ddd; }
.shc-resolve-btn:hover { color: #fff; background: #8bc34a; border-color: #8bc34a; }
.shc-resolve-btn i { font-size: 10px; }

/* Auto-fix bar */
.shc-autofix-bar {
    display: flex; gap: 6px; padding: 6px 0 4px; margin-bottom: 4px;
    border-bottom: 1px dashed #e0e0e0; flex-wrap: wrap;
}
.shc-autofix-bar .btn { font-size: 11px; }
.shc-alert-info {
    background: #d9edf7; border: 1px solid #bce8f1; color: #31708f;
    padding: 8px 12px; border-radius: 3px; font-size: 12px;
}
.shc-alert-info strong { color: #286090; }

/* Confidence labels */
.shc-confidence-high { color: #00a65a; font-weight: 600; }
.shc-confidence-medium { color: #f39c12; font-weight: 600; }
.shc-confidence-low { color: #dd4b39; font-weight: 600; }

/* ═══════════════  CLEAN MESSAGE  ═══════════════ */
.shc-clean-msg { padding: 6px 0; font-size: 12px; color: #00a65a; }
.shc-clean-msg i { margin-right: 4px; }

/* ═══════════════  BATCH TOOLBAR  ═══════════════ */
.shc-batch {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #2c3e50; color: #fff;
    padding: 8px 16px; z-index: 1050;
    box-shadow: 0 -2px 8px rgba(0,0,0,.25);
    animation: shcSlideUp .25s ease-out;
}
@keyframes shcSlideUp {
    from { transform: translateY(100%); opacity:0; }
    to   { transform: translateY(0); opacity:1; }
}
.shc-batch-inner {
    display: flex; justify-content: space-between; align-items: center;
    max-width: 1200px; margin: 0 auto;
}
.shc-batch-count { font-size: 12px; }
.shc-batch-actions { display: flex; align-items: center; gap: 4px; }
.shc-batch-actions .btn { font-size: 11px; padding: 2px 8px; }

/* ═══════════════  MODAL TWEAKS  ═══════════════ */
.shc-alert-warn {
    background: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b;
    padding: 6px 10px; border-radius: 3px; font-size: 12px; margin-bottom: 8px;
}

/* ═══════════════  RESPONSIVE  ═══════════════ */
@media (max-width: 767px) {
    .shc-toolbar { flex-direction: column; gap: 6px; align-items: flex-start; }
    .shc-batch-inner { flex-direction: column; gap: 6px; }
    .shc-stat-card { padding: 8px 10px; }
    .shc-stat-number { font-size: 18px; }
}
</style>

<script>
var HC = {
    selGroups: new Set(),
    selUsers: new Set(),
    csrf: '{{ csrf_token() }}',
    url: '{{ admin_url("system-health-check") }}',

    init: function() {
        var self = this;

        // Toggle card expand/collapse
        // Filters
        $(document).on('click', '.shc-filter', function() {
            $(this).toggleClass('active');
            self.applyFilters();
        });

        // Item checkbox
        $(document).on('change', '.item-checkbox', function() {
            var $item = $(this).closest('.shc-item');
            var entity = $item.data('entity');
            var id = +$(this).val();
            if (this.checked) {
                $item.addClass('selected');
                (entity === 'group' ? self.selGroups : self.selUsers).add(id);
            } else {
                $item.removeClass('selected');
                (entity === 'group' ? self.selGroups : self.selUsers).delete(id);
            }
            self.updateBatch();
        });

        // User checkbox (duplicates table rows)
        $(document).on('change', '.user-checkbox', function() {
            var id = +$(this).val();
            this.checked ? self.selUsers.add(id) : self.selUsers.delete(id);
            self.updateBatch();
        });

        // Select all per check
        $(document).on('change', '.select-all-check', function() {
            var key = $(this).data('check');
            var $c = $('.shc-items[data-check="' + key + '"]');
            $c.find('.item-checkbox, .user-checkbox').prop('checked', this.checked).trigger('change');
        });

        // Select-all inside duplicate tables
        $(document).on('change', '.group-select-all, .user-select-all', function() {
            $(this).closest('table').find('.item-checkbox, .user-checkbox').prop('checked', this.checked).trigger('change');
        });
    },

    // Toggle card
    toggle: function(headerEl) {
        var $header = $(headerEl);
        var $body = $header.next('.shc-card-body');
        var $chev = $header.find('.shc-chevron');
        $body.slideToggle(150);
        $chev.toggleClass('fa-chevron-down fa-chevron-right');
    },

    expandAll: function() {
        $('.shc-card-body').slideDown(150);
        $('.shc-chevron').removeClass('fa-chevron-right').addClass('fa-chevron-down');
    },

    collapseAll: function() {
        $('.shc-card-body').slideUp(150);
        $('.shc-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-right');
    },

    applyFilters: function() {
        var active = [];
        $('.shc-filter.active').each(function() { active.push($(this).data('filter')); });
        $('.shc-card-wrap').each(function() {
            $(this).toggle(active.indexOf($(this).data('severity')) !== -1);
        });
    },

    updateBatch: function() {
        var gc = this.selGroups.size, uc = this.selUsers.size, total = gc + uc;
        $('#shc-sel-count').text(total);
        if (total > 0) {
            $('#shc-batch').slideDown(150);
            $('.group-actions').toggle(gc > 0);
            $('.user-actions').toggle(uc > 0);
        } else {
            $('#shc-batch').slideUp(150);
        }
    },

    clearSelection: function() {
        this.selGroups.clear(); this.selUsers.clear();
        $('.item-checkbox, .user-checkbox, .select-all-check, .group-select-all, .user-select-all').prop('checked', false);
        $('.shc-item').removeClass('selected');
        this.updateBatch();
    },

    ajax: function(endpoint, data, cb) {
        $.ajax({
            url: this.url + '/' + endpoint, type: 'POST',
            data: $.extend({_token: this.csrf}, data), dataType: 'json',
            success: function(r) {
                if (r.success) { toastr.success(r.message); if (cb) cb(r); setTimeout(function(){ location.reload(); }, 1200); }
                else toastr.error(r.message || 'Failed');
            },
            error: function(x) { toastr.error('Error: ' + (x.responseJSON?.message || x.statusText)); }
        });
    },

    batchDeleteGroups: function() {
        if (!confirm('Delete ' + this.selGroups.size + ' group(s)? Cannot be undone.')) return;
        this.ajax('batch-delete-groups', {ids: Array.from(this.selGroups)});
    },

    batchDeleteUsers: function() {
        if (!confirm('Delete ' + this.selUsers.size + ' user(s)? Cannot be undone.')) return;
        this.ajax('batch-delete-users', {ids: Array.from(this.selUsers)});
    },

    showAssignFacilitatorModal: function() {
        $('#facilitator-group-count').text(this.selGroups.size);
        $('#assignFacilitatorModal').modal('show');
    },

    assignFacilitator: function() {
        var v = $('#facilitator-select').val();
        if (!v) { toastr.warning('Select a facilitator'); return; }
        $('#assignFacilitatorModal').modal('hide');
        this.ajax('batch-assign-facilitator', { ids: Array.from(this.selGroups), facilitator_id: v });
    },

    showAssignIpModal: function() {
        $('#ip-user-count').text(this.selUsers.size);
        $('#assignIpModal').modal('show');
    },

    assignIp: function() {
        var v = $('#ip-select').val();
        if (!v) { toastr.warning('Select an IP'); return; }
        $('#assignIpModal').modal('hide');
        this.ajax('batch-assign-ip', { ids: Array.from(this.selUsers), ip_id: v });
    },

    batchClearField: function(field) {
        var name = field === 'phone_number' ? 'phone numbers' : 'emails';
        if (!confirm('Clear ' + name + ' for ' + this.selUsers.size + ' user(s)?')) return;
        this.ajax('batch-clear-field', { ids: Array.from(this.selUsers), field: field });
    },

    batchUpdateStatus: function(status) {
        if (!confirm('Set ' + this.selGroups.size + ' group(s) to ' + status + '?')) return;
        this.ajax('batch-update-group-status', { ids: Array.from(this.selGroups), status: status });
    },

    showMergeModal: function() {
        var $s = $('#keep-user-select').empty().append('<option value="">-- Select user to keep --</option>');
        this.selUsers.forEach(function(id) {
            var $cb = $('.user-checkbox[value="' + id + '"], .item-checkbox[value="' + id + '"]').first();
            $s.append('<option value="' + id + '">' + ($cb.data('name') || 'User #' + id) + '</option>');
        });
        $('#mergeUsersModal').modal('show');
    },

    mergeUsers: function() {
        var v = $('#keep-user-select').val();
        if (!v) { toastr.warning('Select a user to keep'); return; }
        if (!confirm('Keep user #' + v + ' and DELETE all others?')) return;
        $('#mergeUsersModal').modal('hide');
        this.ajax('merge-duplicate-users', { ids: Array.from(this.selUsers), keep_id: v });
    },

    deleteAllOrphans: function() {
        if (!confirm('Delete ALL orphaned members (no group assigned)? This excludes admins and facilitators. This action cannot be undone.')) return;
        this.ajax('delete-all-orphaned-members', {});
    },

    resolveItem: function(checkKey, entityType, entityIds) {
        var $btn = event ? $(event.target).closest('.shc-resolve-btn') : null;
        if ($btn) $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.ajax({
            url: this.url + '/resolve-item', type: 'POST',
            data: { _token: this.csrf, check_key: checkKey, entity_type: entityType, entity_ids: entityIds },
            dataType: 'json',
            success: function(r) {
                if (r.success) {
                    toastr.success(r.message);
                    if ($btn) $btn.closest('.shc-item').slideUp(200, function() { $(this).remove(); });
                } else {
                    toastr.error(r.message || 'Failed');
                    if ($btn) $btn.prop('disabled', false).html('<i class="fa fa-check"></i>');
                }
            },
            error: function(x) {
                toastr.error('Error: ' + (x.responseJSON?.message || x.statusText));
                if ($btn) $btn.prop('disabled', false).html('<i class="fa fa-check"></i>');
            }
        });
    },

    resolveCluster: function(checkKey, entityType, entityIds) {
        this.resolveItem(checkKey, entityType, entityIds);
    },

    // ─────────────────────────────────────────────────
    // AUTO-FIX (Intelligent Fix)
    // ─────────────────────────────────────────────────
    _autoFixType: null,
    _autoFixData: null,

    autoFix: function(checkKey) {
        var self = this;
        var endpoints = {
            'orphaned_members': 'auto-fix-orphaned-members',
            'users_no_ip': 'auto-fix-users-no-ip',
            'groups_no_facilitator': 'auto-fix-groups-no-facilitator'
        };
        var titles = {
            'orphaned_members': 'Fix Orphaned Members — Match to Groups',
            'users_no_ip': 'Fix Users Without IP',
            'groups_no_facilitator': 'Auto-Assign Facilitators'
        };

        self._autoFixType = checkKey;
        self._autoFixData = null;

        $('#auto-fix-title').text(titles[checkKey] || 'Intelligent Fix');
        $('#auto-fix-loading').show();
        $('#auto-fix-results, #auto-fix-footer').hide();
        $('#autoFixModal').modal('show');

        $.ajax({
            url: this.url + '/' + endpoints[checkKey], type: 'POST',
            data: { _token: this.csrf }, dataType: 'json',
            success: function(r) {
                if (!r.success) {
                    toastr.error(r.message || 'Scan failed');
                    $('#autoFixModal').modal('hide');
                    return;
                }
                self.showAutoFixResults(checkKey, r);
            },
            error: function(x) {
                toastr.error('Error: ' + (x.responseJSON?.message || x.statusText));
                $('#autoFixModal').modal('hide');
            }
        });
    },

    showAutoFixResults: function(checkKey, data) {
        var self = this;
        $('#auto-fix-loading').hide();
        $('#auto-fix-results, #auto-fix-footer').show();

        if (checkKey === 'orphaned_members') {
            self._autoFixData = data.fixes;
            var html = '';
            if (data.fixes.length > 0) {
                html += '<div style="margin-bottom:6px;"><label style="font-size:11px;cursor:pointer;"><input type="checkbox" id="auto-fix-select-all" checked> Select All</label></div>';
                html += '<table class="shc-tbl"><thead><tr>';
                html += '<th style="width:24px;"><input type="checkbox" id="af-toggle-all" checked></th>';
                html += '<th>Orphan</th><th>Phone</th><th>Match</th><th>Assign To Group</th><th>Confidence</th>';
                html += '</tr></thead><tbody>';
                data.fixes.forEach(function(f, i) {
                    html += '<tr><td><input type="checkbox" class="af-check" data-idx="' + i + '" checked></td>';
                    html += '<td><strong>' + self.esc(f.user_name) + '</strong></td>';
                    html += '<td>' + (f.user_phone || '-') + '</td>';
                    html += '<td><span class="label label-' + (f.match_type === 'phone' ? 'success' : 'warning') + '">' + f.match_type + '</span>';
                    html += ' <small>' + self.esc(f.matched_user_name) + '</small></td>';
                    html += '<td>' + self.esc(f.group_name) + '</td>';
                    html += '<td class="shc-confidence-' + f.confidence + '">' + f.confidence + '</td></tr>';
                });
                html += '</tbody></table>';
            }
            var summary = '<strong>' + data.fixes.length + '</strong> orphan(s) matched to existing groups';
            if (data.no_match_count > 0) {
                summary += ' &middot; <strong>' + data.no_match_count + '</strong> could not be matched (can be deleted manually)';
            }
            summary += ' &middot; Total: <strong>' + data.total + '</strong>';
            $('#auto-fix-summary').html(summary);
            $('#auto-fix-table-wrap').html(html);
            self.updateAutoFixCount();

        } else if (checkKey === 'users_no_ip') {
            self._autoFixData = data;
            var summary = '<strong>' + data.fixable + '</strong> user(s) have a group but no IP — we\'ll assign the IP of their group.';
            if (data.unfixable > 0) {
                summary += '<br><strong>' + data.unfixable + '</strong> user(s) have neither group nor IP (cannot be auto-fixed).';
            }
            $('#auto-fix-summary').html(summary);
            $('#auto-fix-table-wrap').html('');
            $('#auto-fix-count').text(data.fixable);
            if (data.fixable === 0) {
                $('#auto-fix-apply-btn').prop('disabled', true);
            }

        } else if (checkKey === 'groups_no_facilitator') {
            self._autoFixData = data.fixes;
            var html = '';
            if (data.fixes.length > 0) {
                html += '<table class="shc-tbl"><thead><tr>';
                html += '<th style="width:24px;"><input type="checkbox" id="af-toggle-all" checked></th>';
                html += '<th>Group</th><th>Suggested Facilitator</th><th>Currently Manages</th>';
                html += '</tr></thead><tbody>';
                data.fixes.forEach(function(f, i) {
                    html += '<tr><td><input type="checkbox" class="af-check" data-idx="' + i + '" checked></td>';
                    html += '<td><strong>' + self.esc(f.group_name) + '</strong></td>';
                    html += '<td>' + self.esc(f.facilitator_name) + '</td>';
                    html += '<td>' + f.facilitator_groups + ' group(s)</td></tr>';
                });
                html += '</tbody></table>';
            }
            var summary = '<strong>' + data.fixes.length + '</strong> group(s) can be auto-assigned facilitators';
            if (data.no_fix_count > 0) {
                summary += ' &middot; <strong>' + data.no_fix_count + '</strong> have no facilitator in their IP (assign manually)';
            }
            $('#auto-fix-summary').html(summary);
            $('#auto-fix-table-wrap').html(html);
            self.updateAutoFixCount();
        }

        // Bind toggle-all
        $(document).off('change', '#af-toggle-all').on('change', '#af-toggle-all', function() {
            $('.af-check').prop('checked', this.checked);
            self.updateAutoFixCount();
        });
        $(document).off('change', '.af-check').on('change', '.af-check', function() {
            self.updateAutoFixCount();
        });
    },

    updateAutoFixCount: function() {
        var count = this._autoFixType === 'users_no_ip'
            ? (this._autoFixData ? this._autoFixData.fixable : 0)
            : $('.af-check:checked').length;
        $('#auto-fix-count').text(count);
        $('#auto-fix-apply-btn').prop('disabled', count === 0);
    },

    applyAutoFixAction: function() {
        var self = this;
        var type = self._autoFixType;
        var btn = $('#auto-fix-apply-btn');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Applying...');

        if (type === 'orphaned_members') {
            var selectedFixes = [];
            $('.af-check:checked').each(function() {
                var idx = +$(this).data('idx');
                var f = self._autoFixData[idx];
                selectedFixes.push({ user_id: f.user_id, group_id: f.group_id, ip_id: f.ip_id });
            });
            if (selectedFixes.length === 0) { toastr.warning('No fixes selected'); btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply'); return; }

            $.ajax({
                url: self.url + '/auto-fix-orphaned-members', type: 'POST',
                data: { _token: self.csrf, apply: 1, fixes: selectedFixes }, dataType: 'json',
                success: function(r) {
                    $('#autoFixModal').modal('hide');
                    if (r.success) { toastr.success(r.message); setTimeout(function(){ location.reload(); }, 1200); }
                    else { toastr.error(r.message); btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply'); }
                },
                error: function(x) {
                    toastr.error('Error: ' + (x.responseJSON?.message || x.statusText));
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply');
                }
            });

        } else if (type === 'users_no_ip') {
            $.ajax({
                url: self.url + '/auto-fix-users-no-ip', type: 'POST',
                data: { _token: self.csrf, apply: 1 }, dataType: 'json',
                success: function(r) {
                    $('#autoFixModal').modal('hide');
                    if (r.success) { toastr.success(r.message); setTimeout(function(){ location.reload(); }, 1200); }
                    else { toastr.error(r.message); btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply'); }
                },
                error: function(x) {
                    toastr.error('Error: ' + (x.responseJSON?.message || x.statusText));
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply');
                }
            });

        } else if (type === 'groups_no_facilitator') {
            var selectedFixes = [];
            $('.af-check:checked').each(function() {
                var idx = +$(this).data('idx');
                var f = self._autoFixData[idx];
                selectedFixes.push({ group_id: f.group_id, facilitator_id: f.facilitator_id });
            });
            if (selectedFixes.length === 0) { toastr.warning('No fixes selected'); btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply'); return; }

            $.ajax({
                url: self.url + '/auto-fix-groups-no-facilitator', type: 'POST',
                data: { _token: self.csrf, apply: 1, fixes: selectedFixes }, dataType: 'json',
                success: function(r) {
                    $('#autoFixModal').modal('hide');
                    if (r.success) { toastr.success(r.message); setTimeout(function(){ location.reload(); }, 1200); }
                    else { toastr.error(r.message); btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply'); }
                },
                error: function(x) {
                    toastr.error('Error: ' + (x.responseJSON?.message || x.statusText));
                    btn.prop('disabled', false).html('<i class="fa fa-check"></i> Apply');
                }
            });
        }
    },

    // ─────────────────────────────────────────────────
    // QUICK ACTIONS (with confirmation modals)
    // ─────────────────────────────────────────────────
    _confirmedAction: null,

    showConfirm: function(title, msg, label, headerColor, action) {
        this._confirmedAction = action;
        $('#confirm-action-title').html('<i class="fa fa-exclamation-triangle"></i> ' + title);
        $('#confirm-action-msg').html(msg);
        $('#confirm-action-label').text(label);
        var $btn = $('#confirm-action-btn');
        $btn.removeClass('btn-danger btn-success btn-warning');
        $btn.addClass(headerColor === 'success' ? 'btn-success' : headerColor === 'warning' ? 'btn-warning' : 'btn-danger');
        $('#confirmActionModal .modal-header').css('background', headerColor === 'success' ? '#00a65a' : headerColor === 'warning' ? '#f39c12' : '#dd4b39');
        $('#confirmActionModal').modal('show');
    },

    executeConfirmedAction: function() {
        $('#confirmActionModal').modal('hide');
        if (typeof this._confirmedAction === 'function') {
            this._confirmedAction();
        }
    },

    quickDeleteAll: function(checkKey, count) {
        var self = this;
        self.showConfirm(
            'Delete All Orphaned Members',
            'This will <strong>permanently delete ' + count + ' orphaned member(s)</strong> who have no group assigned.<br><br>' +
            '<span style="color:#dd4b39;"><i class="fa fa-exclamation-triangle"></i> This action cannot be undone.</span><br><br>' +
            'Admins and facilitators will be excluded from deletion.',
            'Delete ' + count + ' Members',
            'danger',
            function() { self.ajax('delete-all-orphaned-members', {}); }
        );
    },

    quickDeleteAllEmpty: function(count) {
        var self = this;
        // Collect all empty group IDs from checkboxes
        var ids = [];
        $('.shc-items[data-check="groups_empty"] .item-checkbox').each(function() {
            ids.push(+$(this).val());
        });
        self.showConfirm(
            'Delete All Empty Groups',
            'This will <strong>permanently delete ' + ids.length + ' group(s)</strong> that have zero members.<br><br>' +
            '<span style="color:#dd4b39;"><i class="fa fa-exclamation-triangle"></i> This action cannot be undone.</span>',
            'Delete ' + ids.length + ' Groups',
            'danger',
            function() { self.ajax('batch-delete-groups', { ids: ids }); }
        );
    },

    quickActivateAll: function(count) {
        var self = this;
        var ids = [];
        $('.shc-items[data-check="inactive_groups_with_members"] .item-checkbox').each(function() {
            ids.push(+$(this).val());
        });
        self.showConfirm(
            'Activate All Inactive Groups',
            'This will set <strong>' + ids.length + ' group(s)</strong> to <span class="label label-success">Active</span> status.',
            'Activate ' + ids.length + ' Groups',
            'success',
            function() { self.ajax('batch-update-group-status', { ids: ids, status: 'Active' }); }
        );
    },

    esc: function(s) { return $('<span>').text(s || '').html(); }
};

$(document).ready(function() {
    HC.init();

    // IP Filter — navigate on change
    $('#shc-ip-filter').on('change', function() {
        var val = $(this).val();
        var base = '{{ admin_url("system-health-check") }}';
        window.location.href = val ? (base + '?filter_ip_id=' + val) : base;
    });
});
</script>
