<!-- Batch Actions Toolbar (hidden by default, shown when items selected) -->
<div id="batch-toolbar" class="batch-toolbar" style="display: none;">
    <div class="batch-toolbar-inner">
        <span class="selected-count"><strong><span id="selected-count">0</span></strong> items selected</span>
        <div class="batch-actions">
            <!-- Group Actions -->
            <div class="btn-group group-actions" style="display: none;">
                <button type="button" class="btn btn-sm btn-danger" onclick="HealthCheck.batchDeleteGroups()">
                    <i class="fa fa-trash"></i> Delete Groups
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="HealthCheck.showAssignFacilitatorModal()">
                    <i class="fa fa-user-plus"></i> Assign Facilitator
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="HealthCheck.batchUpdateStatus('Active')">
                    <i class="fa fa-check"></i> Activate
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="HealthCheck.batchUpdateStatus('Inactive')">
                    <i class="fa fa-pause"></i> Deactivate
                </button>
            </div>
            <!-- User Actions -->
            <div class="btn-group user-actions" style="display: none;">
                <button type="button" class="btn btn-sm btn-danger" onclick="HealthCheck.batchDeleteUsers()">
                    <i class="fa fa-trash"></i> Delete Users
                </button>
                <button type="button" class="btn btn-sm btn-primary" onclick="HealthCheck.showAssignIpModal()">
                    <i class="fa fa-building"></i> Assign IP
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="HealthCheck.batchClearField('phone_number')">
                    <i class="fa fa-phone-slash"></i> Clear Phone
                </button>
                <button type="button" class="btn btn-sm btn-info" onclick="HealthCheck.batchClearField('email')">
                    <i class="fa fa-envelope-open"></i> Clear Email
                </button>
                <button type="button" class="btn btn-sm btn-success" onclick="HealthCheck.showMergeModal()">
                    <i class="fa fa-compress"></i> Merge Users
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-default" onclick="HealthCheck.clearSelection()">
                <i class="fa fa-times"></i> Clear
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fa fa-exclamation-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Critical Issues</span>
                <span class="info-box-number">{{ $summary['critical_issues'] }}</span>
                <div class="progress"><div class="progress-bar" style="width: {{ $summary['total_issues'] > 0 ? ($summary['critical_issues'] / $summary['total_issues'] * 100) : 0 }}%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa fa-warning"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Warnings</span>
                <span class="info-box-number">{{ $summary['warning_issues'] }}</span>
                <div class="progress"><div class="progress-bar" style="width: {{ $summary['total_issues'] > 0 ? ($summary['warning_issues'] / $summary['total_issues'] * 100) : 0 }}%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-aqua">
            <span class="info-box-icon"><i class="fa fa-info-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Info Items</span>
                <span class="info-box-number">{{ $summary['info_issues'] }}</span>
                <div class="progress"><div class="progress-bar" style="width: {{ $summary['total_issues'] > 0 ? ($summary['info_issues'] / $summary['total_issues'] * 100) : 0 }}%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fa fa-clipboard-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Issues</span>
                <span class="info-box-number">{{ $summary['total_issues'] }}</span>
                <div class="progress"><div class="progress-bar" style="width: 100%"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row" style="margin-bottom: 15px;">
    <div class="col-md-12">
        <div class="btn-group">
            <button class="btn btn-default btn-sm" onclick="HealthCheck.expandAll()"><i class="fa fa-expand"></i> Expand All</button>
            <button class="btn btn-default btn-sm" onclick="HealthCheck.collapseAll()"><i class="fa fa-compress"></i> Collapse All</button>
            <button class="btn btn-default btn-sm" onclick="location.reload()"><i class="fa fa-refresh"></i> Refresh</button>
        </div>
        <div class="btn-group pull-right">
            <button class="btn btn-danger btn-sm filter-btn active" data-filter="critical"><i class="fa fa-exclamation-circle"></i> Critical</button>
            <button class="btn btn-warning btn-sm filter-btn active" data-filter="warning"><i class="fa fa-warning"></i> Warning</button>
            <button class="btn btn-info btn-sm filter-btn active" data-filter="info"><i class="fa fa-info-circle"></i> Info</button>
        </div>
    </div>
</div>

<!-- Health Checks -->
<div class="row" id="health-checks-container">
    @foreach($checks as $checkKey => $check)
        <div class="col-md-6 health-check-card" data-severity="{{ $check['severity'] }}">
            <div class="box box-{{ $check['color'] }} {{ count($check['items']) === 0 ? 'collapsed-box' : '' }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa {{ $check['icon'] }}"></i> {{ $check['title'] }}
                    </h3>
                    <div class="box-tools pull-right">
                        @if(count($check['items']) > 0)
                            <span class="label label-{{ $check['color'] }}" style="margin-right: 10px;">
                                {{ count($check['items']) }} issue(s)
                            </span>
                            <label class="select-all-label" style="margin-right: 10px; font-weight: normal; cursor: pointer;">
                                <input type="checkbox" class="select-all-check" data-check="{{ $checkKey }}" data-entity="{{ $check['entity'] ?? 'group' }}"> Select All
                            </label>
                        @else
                            <span class="label label-success" style="margin-right: 10px;">
                                <i class="fa fa-check"></i> All clear
                            </span>
                        @endif
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa {{ count($check['items']) === 0 ? 'fa-plus' : 'fa-minus' }}"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body" style="{{ count($check['items']) === 0 ? 'display: none;' : '' }}">
                    <p class="text-muted small">{{ $check['description'] }}</p>

                    @if(count($check['items']) > 0)
                        <div class="health-check-items" data-check="{{ $checkKey }}" data-entity="{{ $check['entity'] ?? 'group' }}">
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
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> No issues detected
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Legend -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-default collapsed-box">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-info-circle"></i> Legend & Help</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                </div>
            </div>
            <div class="box-body" style="display: none;">
                <div class="row">
                    <div class="col-md-4">
                        <h5><strong>Severity Levels</strong></h5>
                        <ul class="list-unstyled">
                            <li><span class="label label-danger">CRITICAL</span> - Must be fixed immediately</li>
                            <li><span class="label label-warning">WARNING</span> - Should be reviewed</li>
                            <li><span class="label label-info">INFO</span> - For your information</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5><strong>Batch Operations</strong></h5>
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check-square-o"></i> Select items with checkboxes</li>
                            <li><i class="fa fa-tasks"></i> Use toolbar for batch actions</li>
                            <li><i class="fa fa-compress"></i> Merge duplicates to keep one</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h5><strong>Key Rules</strong></h5>
                        <ul class="list-unstyled">
                            <li>Phone numbers must be unique</li>
                            <li>Emails must be unique</li>
                            <li>All users need an IP assigned</li>
                            <li>Groups need a facilitator</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Facilitator Modal -->
<div class="modal fade" id="assignFacilitatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-user-plus"></i> Assign Facilitator</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select Facilitator</label>
                    <select class="form-control" id="facilitator-select">
                        <option value="">-- Select Facilitator --</option>
                        @foreach($facilitators as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-muted">Selected groups: <strong id="facilitator-group-count">0</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="HealthCheck.assignFacilitator()">
                    <i class="fa fa-check"></i> Assign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assign IP Modal -->
<div class="modal fade" id="assignIpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-building"></i> Assign Implementing Partner</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select IP</label>
                    <select class="form-control" id="ip-select">
                        <option value="">-- Select IP --</option>
                        @foreach($ips as $ip)
                            <option value="{{ $ip->id }}">{{ $ip->name }} ({{ $ip->short_name }})</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-muted">Selected users: <strong id="ip-user-count">0</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="HealthCheck.assignIp()">
                    <i class="fa fa-check"></i> Assign
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Merge Users Modal -->
<div class="modal fade" id="mergeUsersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-compress"></i> Merge Duplicate Users</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> <strong>Warning:</strong> This will delete all selected users except the one you choose to keep.
                </div>
                <div class="form-group">
                    <label>Select user to KEEP (others will be deleted)</label>
                    <select class="form-control" id="keep-user-select">
                        <!-- Populated dynamically -->
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="HealthCheck.mergeUsers()">
                    <i class="fa fa-compress"></i> Merge
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .batch-toolbar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #333;
        color: #fff;
        padding: 12px 20px;
        z-index: 1050;
        box-shadow: 0 -3px 10px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease;
    }
    @keyframes slideUp {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
    .batch-toolbar-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
    }
    .batch-toolbar .selected-count {
        font-size: 14px;
    }
    .batch-toolbar .btn { margin-left: 5px; }

    .health-check-items { margin-top: 10px; }
    .health-check-item {
        padding: 12px;
        margin-bottom: 8px;
        border-left: 4px solid #ddd;
        background-color: #f9f9f9;
        border-radius: 3px;
        transition: all 0.2s;
    }
    .health-check-item:hover { background-color: #f0f0f0; }
    .health-check-item.selected { background-color: #e8f4fc; border-left-color: #3498db; }
    .health-check-item.critical { border-left-color: #dd4b39; background-color: #fdf5f5; }
    .health-check-item.warning { border-left-color: #f39c12; background-color: #fffdf5; }
    .health-check-item.info { border-left-color: #3498db; background-color: #f5f9fd; }

    .health-check-item-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .health-check-item-header .item-checkbox { margin-right: 8px; }
    .health-check-item-details { font-size: 12px; color: #666; margin-top: 8px; }
    .health-check-item-details table { width: 100%; margin-top: 5px; }
    .health-check-item-details table th { background: #f5f5f5; padding: 6px 8px; font-size: 11px; }
    .health-check-item-details table td { padding: 6px 8px; font-size: 12px; border-bottom: 1px solid #eee; }
    .health-check-item-details table tr:hover { background: #fafafa; }

    .info-box { box-shadow: 0 2px 5px rgba(0,0,0,0.1); cursor: default; }
    .info-box .progress { margin: 5px 0 0 0; height: 3px; }
    .box { box-shadow: 0 1px 3px rgba(0,0,0,0.12); }

    .filter-btn { opacity: 0.5; }
    .filter-btn.active { opacity: 1; }

    .select-all-label { font-size: 12px; }
    .action-link { color: #3c8dbc; cursor: pointer; }
    .action-link:hover { text-decoration: underline; }

    .user-row { border-bottom: 1px solid #eee; padding: 5px 0; }
    .user-row:last-child { border-bottom: none; }
    .user-row .user-checkbox { margin-right: 8px; }

    .badge-type { font-size: 10px; padding: 2px 6px; }
</style>

<script>
var HealthCheck = {
    selectedGroups: new Set(),
    selectedUsers: new Set(),
    csrfToken: '{{ csrf_token() }}',
    baseUrl: '{{ admin_url("system-health-check") }}',

    init: function() {
        this.bindEvents();
    },

    bindEvents: function() {
        var self = this;

        // Item checkbox change
        $(document).on('change', '.item-checkbox', function() {
            var $item = $(this).closest('.health-check-item');
            var entity = $item.data('entity');
            var id = parseInt($(this).val());

            if (this.checked) {
                $item.addClass('selected');
                if (entity === 'group') self.selectedGroups.add(id);
                else self.selectedUsers.add(id);
            } else {
                $item.removeClass('selected');
                if (entity === 'group') self.selectedGroups.delete(id);
                else self.selectedUsers.delete(id);
            }
            self.updateToolbar();
        });

        // User row checkbox (for duplicates)
        $(document).on('change', '.user-checkbox', function() {
            var id = parseInt($(this).val());
            if (this.checked) {
                self.selectedUsers.add(id);
            } else {
                self.selectedUsers.delete(id);
            }
            self.updateToolbar();
        });

        // Select all in a check category
        $(document).on('change', '.select-all-check', function() {
            var checkKey = $(this).data('check');
            var entity = $(this).data('entity');
            var $container = $('.health-check-items[data-check="' + checkKey + '"]');

            if (entity === 'group') {
                $container.find('.item-checkbox').prop('checked', this.checked).trigger('change');
            } else {
                $container.find('.user-checkbox, .item-checkbox').prop('checked', this.checked).trigger('change');
            }
        });

        // Filter buttons
        $('.filter-btn').on('click', function() {
            $(this).toggleClass('active');
            self.applyFilters();
        });
    },

    updateToolbar: function() {
        var groupCount = this.selectedGroups.size;
        var userCount = this.selectedUsers.size;
        var total = groupCount + userCount;

        $('#selected-count').text(total);

        if (total > 0) {
            $('#batch-toolbar').slideDown(200);
            $('.group-actions').toggle(groupCount > 0);
            $('.user-actions').toggle(userCount > 0);
        } else {
            $('#batch-toolbar').slideUp(200);
        }
    },

    clearSelection: function() {
        this.selectedGroups.clear();
        this.selectedUsers.clear();
        $('.item-checkbox, .user-checkbox, .select-all-check').prop('checked', false);
        $('.health-check-item').removeClass('selected');
        this.updateToolbar();
    },

    applyFilters: function() {
        var activeFilters = [];
        $('.filter-btn.active').each(function() {
            activeFilters.push($(this).data('filter'));
        });

        $('.health-check-card').each(function() {
            var severity = $(this).data('severity');
            $(this).toggle(activeFilters.includes(severity));
        });
    },

    expandAll: function() {
        $('.box.collapsed-box').each(function() {
            $(this).removeClass('collapsed-box');
            $(this).find('.box-body').slideDown();
            $(this).find('.btn-box-tool i').removeClass('fa-plus').addClass('fa-minus');
        });
    },

    collapseAll: function() {
        $('.box').each(function() {
            $(this).addClass('collapsed-box');
            $(this).find('.box-body').slideUp();
            $(this).find('.btn-box-tool i').removeClass('fa-minus').addClass('fa-plus');
        });
    },

    ajax: function(endpoint, data, callback) {
        var self = this;
        $.ajax({
            url: this.baseUrl + '/' + endpoint,
            type: 'POST',
            data: $.extend({_token: this.csrfToken}, data),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    if (callback) callback(res);
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    toastr.error(res.message || 'Operation failed');
                }
            },
            error: function(xhr) {
                toastr.error('Request failed: ' + (xhr.responseJSON?.message || xhr.statusText));
            }
        });
    },

    batchDeleteGroups: function() {
        if (!confirm('Delete ' + this.selectedGroups.size + ' group(s)? This cannot be undone.')) return;
        this.ajax('batch-delete-groups', {ids: Array.from(this.selectedGroups)});
    },

    batchDeleteUsers: function() {
        if (!confirm('Delete ' + this.selectedUsers.size + ' user(s)? This cannot be undone.')) return;
        this.ajax('batch-delete-users', {ids: Array.from(this.selectedUsers)});
    },

    showAssignFacilitatorModal: function() {
        $('#facilitator-group-count').text(this.selectedGroups.size);
        $('#assignFacilitatorModal').modal('show');
    },

    assignFacilitator: function() {
        var facilitatorId = $('#facilitator-select').val();
        if (!facilitatorId) { toastr.warning('Please select a facilitator'); return; }
        $('#assignFacilitatorModal').modal('hide');
        this.ajax('batch-assign-facilitator', {
            ids: Array.from(this.selectedGroups),
            facilitator_id: facilitatorId
        });
    },

    showAssignIpModal: function() {
        $('#ip-user-count').text(this.selectedUsers.size);
        $('#assignIpModal').modal('show');
    },

    assignIp: function() {
        var ipId = $('#ip-select').val();
        if (!ipId) { toastr.warning('Please select an IP'); return; }
        $('#assignIpModal').modal('hide');
        this.ajax('batch-assign-ip', {
            ids: Array.from(this.selectedUsers),
            ip_id: ipId
        });
    },

    batchClearField: function(field) {
        var fieldName = field === 'phone_number' ? 'phone numbers' : 'emails';
        if (!confirm('Clear ' + fieldName + ' for ' + this.selectedUsers.size + ' user(s)?')) return;
        this.ajax('batch-clear-field', {
            ids: Array.from(this.selectedUsers),
            field: field
        });
    },

    batchUpdateStatus: function(status) {
        if (!confirm('Set ' + this.selectedGroups.size + ' group(s) to ' + status + '?')) return;
        this.ajax('batch-update-group-status', {
            ids: Array.from(this.selectedGroups),
            status: status
        });
    },

    showMergeModal: function() {
        var $select = $('#keep-user-select').empty();
        $select.append('<option value="">-- Select user to keep --</option>');

        this.selectedUsers.forEach(function(id) {
            var $checkbox = $('.user-checkbox[value="' + id + '"], .item-checkbox[value="' + id + '"]').first();
            var name = $checkbox.data('name') || 'User #' + id;
            $select.append('<option value="' + id + '">' + name + '</option>');
        });

        $('#mergeUsersModal').modal('show');
    },

    mergeUsers: function() {
        var keepId = $('#keep-user-select').val();
        if (!keepId) { toastr.warning('Please select a user to keep'); return; }
        if (!confirm('Keep user #' + keepId + ' and DELETE all others?')) return;
        $('#mergeUsersModal').modal('hide');
        this.ajax('merge-duplicate-users', {
            ids: Array.from(this.selectedUsers),
            keep_id: keepId
        });
    }
};

$(document).ready(function() {
    HealthCheck.init();
});
</script>
