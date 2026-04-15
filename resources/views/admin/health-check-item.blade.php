@if ($checkKey === 'groups_similar_names')
{{-- Similar Group Names --}}
<div class="shc-item severity-{{ $severity }}" data-entity="group">
    <div class="shc-item-row">
        <i class="fa fa-clone" style="color:#f39c12;font-size:11px;"></i>
        <strong style="font-size:12px;">{{ collect($item['groups'])->pluck('name')->implode(' / ') }}</strong>
        <span class="label label-warning" style="font-size:10px;">{{ count($item['groups']) }} groups</span>
        <button class="btn btn-default btn-xs shc-resolve-btn" onclick="HC.resolveCluster('{{ $checkKey }}', 'group', {{ json_encode(collect($item['groups'])->pluck('id')) }})" title="Mark as resolved"><i class="fa fa-check"></i> Resolve</button>
    </div>
    <div class="shc-item-detail">
        <table class="shc-tbl">
            <thead><tr><th style="width:24px;"><input type="checkbox" class="group-select-all"></th><th>Name</th><th>Mem</th><th>District</th><th>IP</th></tr></thead>
            <tbody>
            @foreach ($item['groups'] as $g)
                <tr>
                    <td><input type="checkbox" class="item-checkbox" value="{{ $g['id'] }}" data-entity="group" data-name="{{ $g['name'] }}"></td>
                    <td><a href="{{ admin_url('ffs-all-groups/' . $g['id']) }}" target="_blank">{{ $g['name'] }}</a></td>
                    <td>{{ $g['members'] ?? 0 }}</td>
                    <td>{{ $g['district'] ?? 'N/A' }}</td>
                    <td>{{ $g['ip'] ?? 'No IP' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif (in_array($checkKey, ['groups_oversized', 'groups_empty', 'groups_no_facilitator', 'inactive_groups_with_members']))
{{-- Group-based checks --}}
<div class="shc-item severity-{{ $severity }}" data-entity="group">
    <div class="shc-item-row">
        <input type="checkbox" class="item-checkbox" value="{{ $item['id'] }}" data-entity="group" data-name="{{ $item['name'] }}">
        <a href="{{ admin_url('ffs-all-groups/' . $item['id']) }}" target="_blank"><strong>{{ $item['name'] }}</strong></a>
        <span class="label label-default">{{ $item['type'] ?? 'Group' }}</span>
        @if(isset($item['members']))
            <span class="label label-info">{{ $item['members'] }} mem</span>
        @endif
        @if(isset($item['status']))
            <span class="label label-{{ $item['status'] === 'Active' ? 'success' : 'warning' }}">{{ $item['status'] }}</span>
        @endif
        <button class="btn btn-default btn-xs shc-resolve-btn" onclick="HC.resolveItem('{{ $checkKey }}', 'group', [{{ $item['id'] }}])" title="Mark as resolved"><i class="fa fa-check"></i></button>
    </div>
    <div class="shc-item-detail">
        @if(isset($item['registration_date']))
            <span class="shc-kv"><span class="shc-kv-label">Reg:</span> {{ $item['registration_date'] }}</span>
        @endif
        <span class="shc-kv"><span class="shc-kv-label">District:</span> {{ $item['district'] ?? 'N/A' }}</span>
        <span class="shc-kv"><span class="shc-kv-label">IP:</span> {{ $item['ip'] ?? 'No IP' }}</span>
        <a href="{{ admin_url('ffs-all-groups/' . $item['id'] . '/edit') }}" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-edit"></i></a>
        <a href="{{ admin_url('ffs-all-groups/' . $item['id']) }}" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-eye"></i></a>
    </div>
</div>

@elseif (in_array($checkKey, ['duplicate_chairperson', 'duplicate_phone', 'duplicate_email']))
{{-- Duplicate field checks --}}
<div class="shc-item severity-{{ $severity }}" data-entity="user">
    <div class="shc-item-row">
        <i class="fa fa-{{ $checkKey === 'duplicate_email' ? 'envelope' : 'phone' }}" style="color:#dd4b39;font-size:11px;"></i>
        <strong>{{ $item['phone'] ?? $item['email'] ?? '—' }}</strong>
        <span class="label label-danger">{{ count($item['users']) }} users</span>
        <button class="btn btn-default btn-xs shc-resolve-btn" onclick="HC.resolveCluster('{{ $checkKey }}', 'user', {{ json_encode(collect($item['users'])->pluck('id')) }})" title="Mark as resolved"><i class="fa fa-check"></i> Resolve</button>
    </div>
    <div class="shc-item-detail">
        <table class="shc-tbl">
            <thead><tr><th style="width:24px;"><input type="checkbox" class="user-select-all"></th><th>Name</th><th>Email</th><th>Phone</th><th>Group</th></tr></thead>
            <tbody>
            @foreach ($item['users'] as $u)
                <tr>
                    <td><input type="checkbox" class="user-checkbox" value="{{ $u['id'] }}" data-name="{{ $u['name'] }}"></td>
                    <td><a href="{{ admin_url('ffs-members/' . $u['id']) }}" target="_blank">{{ $u['name'] }}</a></td>
                    <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $u['email'] ?? '-' }}</td>
                    <td>{{ $u['phone'] ?? '-' }}</td>
                    <td>
                        @if(!empty($u['group_id']))
                            <a href="{{ admin_url('ffs-all-groups/' . $u['group_id']) }}" target="_blank">{{ $u['group'] ?? 'View' }}</a>
                        @else - @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif (in_array($checkKey, ['users_no_ip', 'orphaned_members']))
{{-- User-based checks --}}
<div class="shc-item severity-{{ $severity }}" data-entity="user">
    <div class="shc-item-row">
        <input type="checkbox" class="item-checkbox" value="{{ $item['id'] }}" data-entity="user" data-name="{{ $item['name'] }}">
        <a href="{{ admin_url('ffs-members/' . $item['id']) }}" target="_blank"><strong>{{ $item['name'] }}</strong></a>
        @if($checkKey === 'users_no_ip')
            <span class="label label-danger">No IP</span>
        @else
            <span class="label label-warning">No Group</span>
        @endif
        @if(!empty($item['phone']))
            <span style="color:#888;font-size:11px;"><i class="fa fa-phone" style="font-size:10px;"></i> {{ $item['phone'] }}</span>
        @endif
        @if(!empty($item['email']))
            <span style="color:#888;font-size:11px;"><i class="fa fa-envelope-o" style="font-size:10px;"></i> {{ $item['email'] }}</span>
        @endif
        <button class="btn btn-default btn-xs shc-resolve-btn" onclick="HC.resolveItem('{{ $checkKey }}', 'user', [{{ $item['id'] }}])" title="Mark as resolved"><i class="fa fa-check"></i></button>
    </div>
    <div class="shc-item-detail">
        @if(isset($item['group']))
            <span class="shc-kv"><span class="shc-kv-label">Group:</span> {{ $item['group'] ?? '-' }}</span>
        @endif
        <a href="{{ admin_url('ffs-members/' . $item['id'] . '/edit') }}" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-edit"></i></a>
    </div>
</div>

@else
{{-- Fallback --}}
<div class="shc-item severity-{{ $severity }}">
    <div class="shc-item-row">
        <i class="fa fa-question-circle" style="color:#aaa;"></i>
        <strong>Item #{{ $idx ?? 0 }}</strong>
    </div>
    <div class="shc-item-detail">
        <pre style="font-size:10px;margin:0;max-height:100px;overflow:auto;">{{ json_encode($item, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endif
