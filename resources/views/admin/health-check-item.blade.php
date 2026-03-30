@php
    $isGroupEntity = ($entity ?? 'group') === 'group';
    $adminUrl = config('admin.route.prefix', 'admin');
@endphp

@if ($checkKey === 'groups_similar_names')
{{-- Similar Group Names --}}
<div class="health-check-item {{ $severity }}" data-entity="group">
    <div class="health-check-item-header">
        <i class="fa fa-clone text-warning"></i>
        <strong>{{ collect($item['groups'])->pluck('name')->implode(', ') }}</strong>
    </div>
    <div class="health-check-item-details">
        <table class="table table-condensed table-hover">
            <thead><tr><th><input type="checkbox" class="group-select-all"></th><th>Group Name</th><th>Type</th><th>Members</th><th>IP</th></tr></thead>
            <tbody>
            @foreach ($item['groups'] as $group)
                <tr>
                    <td><input type="checkbox" class="item-checkbox" value="{{ $group['id'] }}" data-entity="group" data-name="{{ $group['name'] }}"></td>
                    <td><a href="/{{ $adminUrl }}/ffs-all-groups/{{ $group['id'] }}" class="action-link">{{ $group['name'] }}</a></td>
                    <td><span class="label label-default">{{ $group['type'] }}</span></td>
                    <td>{{ $group['members'] ?? 0 }}</td>
                    <td>{{ $group['ip'] ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif (in_array($checkKey, ['groups_oversized', 'groups_empty', 'groups_no_facilitator', 'inactive_groups_with_members']))
{{-- Group-based checks --}}
<div class="health-check-item {{ $severity }}" data-entity="group">
    <div class="health-check-item-header">
        <input type="checkbox" class="item-checkbox" value="{{ $item['id'] }}" data-entity="group" data-name="{{ $item['name'] }}">
        <a href="/{{ $adminUrl }}/ffs-all-groups/{{ $item['id'] }}" class="action-link"><strong>{{ $item['name'] }}</strong></a>
        <span class="label label-default badge-type">{{ $item['type'] ?? 'Group' }}</span>
        @if(isset($item['members']))
            <span class="label label-info">{{ $item['members'] }} members</span>
        @endif
        @if(isset($item['status']))
            <span class="label label-{{ $item['status'] === 'Active' ? 'success' : 'warning' }}">{{ $item['status'] }}</span>
        @endif
    </div>
    <div class="health-check-item-details">
        <table class="table table-condensed">
            @if(isset($item['male']) && isset($item['female']))
            <tr><td width="120"><strong>Gender:</strong></td><td>{{ $item['male'] ?? 0 }} M / {{ $item['female'] ?? 0 }} F</td></tr>
            @endif
            @if(isset($item['registration_date']))
            <tr><td><strong>Registered:</strong></td><td>{{ $item['registration_date'] ?? 'N/A' }}</td></tr>
            @endif
            <tr><td><strong>IP:</strong></td><td>{{ $item['ip'] ?? '-' }}</td></tr>
        </table>
        <a href="/{{ $adminUrl }}/ffs-all-groups/{{ $item['id'] }}/edit" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</a>
        <a href="/{{ $adminUrl }}/ffs-all-groups/{{ $item['id'] }}" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> View</a>
    </div>
</div>

@elseif (in_array($checkKey, ['duplicate_chairperson', 'duplicate_phone', 'duplicate_email']))
{{-- Duplicate field checks (users) --}}
<div class="health-check-item {{ $severity }}" data-entity="user">
    <div class="health-check-item-header">
        <i class="fa fa-{{ $checkKey === 'duplicate_email' ? 'envelope' : 'phone' }} text-danger"></i>
        <strong>{{ $item['phone'] ?? $item['email'] ?? 'Unknown' }}</strong>
        <span class="label label-danger">{{ count($item['users']) }} users</span>
    </div>
    <div class="health-check-item-details">
        <table class="table table-condensed table-hover">
            <thead><tr><th><input type="checkbox" class="user-select-all"></th><th>Name</th><th>Email</th><th>Phone</th><th>Group</th></tr></thead>
            <tbody>
            @foreach ($item['users'] as $user)
                <tr>
                    <td><input type="checkbox" class="user-checkbox" value="{{ $user['id'] }}" data-name="{{ $user['name'] }}"></td>
                    <td><a href="/{{ $adminUrl }}/ffs-members/{{ $user['id'] }}" class="action-link">{{ $user['name'] }}</a></td>
                    <td>{{ $user['email'] ?? '-' }}</td>
                    <td>{{ $user['phone'] ?? '-' }}</td>
                    <td>
                        @if($user['group_id'])
                            <a href="/{{ $adminUrl }}/ffs-all-groups/{{ $user['group_id'] }}">{{ $user['group'] ?? 'View' }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif (in_array($checkKey, ['users_no_ip', 'orphaned_members']))
{{-- User-based checks --}}
<div class="health-check-item {{ $severity }}" data-entity="user">
    <div class="health-check-item-header">
        <input type="checkbox" class="item-checkbox" value="{{ $item['id'] }}" data-entity="user" data-name="{{ $item['name'] }}">
        <a href="/{{ $adminUrl }}/ffs-members/{{ $item['id'] }}" class="action-link"><strong>{{ $item['name'] }}</strong></a>
        @if($checkKey === 'users_no_ip')
            <span class="label label-danger">No IP</span>
        @else
            <span class="label label-warning">No Group</span>
        @endif
    </div>
    <div class="health-check-item-details">
        <table class="table table-condensed">
            <tr><td width="80"><strong>Email:</strong></td><td>{{ $item['email'] ?? '-' }}</td></tr>
            <tr><td><strong>Phone:</strong></td><td>{{ $item['phone'] ?? '-' }}</td></tr>
            @if(isset($item['group']))
            <tr><td><strong>Group:</strong></td><td>{{ $item['group'] ?? '-' }}</td></tr>
            @endif
        </table>
        <a href="/{{ $adminUrl }}/ffs-members/{{ $item['id'] }}/edit" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</a>
    </div>
</div>

@else
{{-- Fallback for unknown check types --}}
<div class="health-check-item {{ $severity }}">
    <div class="health-check-item-header">
        <i class="fa fa-question-circle"></i>
        <strong>Item #{{ $idx ?? 0 }}</strong>
    </div>
    <div class="health-check-item-details">
        <pre>{{ json_encode($item, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endif
