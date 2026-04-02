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
        @php $count = count($check['items']); $empty = $count === 0; @endphp
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
                            <label class="shc-select-all" onclick="event.stopPropagation();">
                                <input type="checkbox" class="select-all-check" data-check="{{ $checkKey }}" data-entity="{{ $check['entity'] ?? 'group' }}"> All
                            </label>
                        @else
                            <span class="shc-badge shc-badge-success"><i class="fa fa-check"></i></span>
                        @endif
                        <i class="fa {{ $empty ? 'fa-chevron-right' : 'fa-chevron-down' }} shc-chevron"></i>
                    </div>
                </div>
                <div class="shc-card-body" style="{{ $empty ? 'display:none;' : '' }}">
                    <div class="shc-card-desc">{{ $check['description'] }}</div>

                    @if($count > 0)
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
    }
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
