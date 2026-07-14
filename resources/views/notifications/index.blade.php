@extends('layouts.app')

@section('title', '通知中心')

@section('content')

<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink flex items-center gap-2">
            通知中心
            @if ($unreadCount > 0)<span class="badge bg-red-100 text-red-700">{{ $unreadCount }}</span>@endif
        </h1>
    </div>
    <div class="flex items-center gap-2">
        @if ($unreadCount > 0)
        <button type="button" id="markAllAsReadBtn" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4 M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20z"/></svg>
            <span>全部已读</span>
        </button>
        @endif
        @if (Auth::user()->isAdmin())
        <button type="button" onclick="openModal('announcementModal')" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l18-5v12L3 14v-3z M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
            <span>发布公告</span>
        </button>
        @endif
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('notifications.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="is_read">状态</label>
            <select class="input" name="is_read" id="is_read">
                <option value="">全部</option>
                <option value="0" {{ request('is_read') == '0' ? 'selected' : '' }}>未读</option>
                <option value="1" {{ request('is_read') == '1' ? 'selected' : '' }}>已读</option>
            </select>
        </div>
        <div>
            <label class="label" for="type">类型</label>
            <select class="input" name="type" id="type">
                <option value="">全部</option>
                <option value="workorder_created" {{ request('type') == 'workorder_created' ? 'selected' : '' }}>工单创建</option>
                <option value="workorder_assigned" {{ request('type') == 'workorder_assigned' ? 'selected' : '' }}>工单分配</option>
                <option value="workorder_resolved" {{ request('type') == 'workorder_resolved' ? 'selected' : '' }}>工单解决</option>
                <option value="workorder_closed" {{ request('type') == 'workorder_closed' ? 'selected' : '' }}>工单关闭</option>
                <option value="system_announcement" {{ request('type') == 'system_announcement' ? 'selected' : '' }}>系统公告</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
                <span>筛选</span>
            </button>
            <a href="{{ route('notifications.index') }}" class="btn btn-secondary btn-sm">重置</a>
            @if ($notifications->count() > 0)
            <div class="w-px h-6 bg-border mx-1"></div>
            <button type="button" id="batchDeleteBtn" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                <span>批量删除</span>
            </button>
            <button type="button" id="batchReadBtn" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>批量已读</span>
            </button>
            @endif
        </div>
    </form>
</div>

{{-- Notifications list --}}
<div class="card">
    @if ($notifications->count() > 0)
    {{-- Mobile cards --}}
    <div class="md:hidden divide-y divide-border">
        @foreach ($notifications as $notification)
        <div class="p-4 {{ !$notification->is_read ? 'bg-blue-50/40' : '' }}">
            <div class="flex items-start justify-between gap-2 mb-1">
                <p class="text-sm font-medium text-ink">{{ $notification->title }}</p>
                <span class="text-xs whitespace-nowrap shrink-0" style="color: var(--c-ink-subtle);">{{ $notification->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-sm mb-2" style="color: var(--c-ink-muted);">{{ $notification->content }}</p>
            <div class="flex items-center gap-2 flex-wrap">
                @if (!$notification->is_read)<span class="badge bg-blue-100 text-blue-700">未读</span>@else<span class="badge bg-slate-100 text-slate-600">已读</span>@endif
                @if ($notification->data && isset($notification->data['action_url']))<a href="{{ $notification->data['action_url'] }}" class="text-sm text-brand-600 hover:underline">查看详情</a>@endif
                <div class="ml-auto flex items-center gap-1">
                    @if (!$notification->is_read)<button class="btn btn-ghost btn-sm mark-read-btn" data-id="{{ $notification->id }}">已读</button>@endif
                    <button class="btn btn-ghost btn-sm text-red-500 delete-btn" data-id="{{ $notification->id }}">删除</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Desktop table --}}
    <div class="hidden md:block">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left">
                    <th class="px-4 py-3 w-10"><input type="checkbox" id="selectAll" class="rounded border-border-strong"></th>
                    <th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">内容</th>
                    <th class="px-4 py-3 font-medium w-24" style="color: var(--c-ink-muted);">状态</th>
                    <th class="px-4 py-3 font-medium w-32" style="color: var(--c-ink-muted);">时间</th>
                    <th class="px-4 py-3 font-medium w-24 text-right" style="color: var(--c-ink-muted);">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notifications as $notification)
                <tr class="border-b border-border {{ !$notification->is_read ? 'bg-blue-50/40' : '' }}">
                    <td class="px-4 py-3"><input type="checkbox" class="notif-checkbox rounded border-border-strong" value="{{ $notification->id }}"></td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-ink">{{ $notification->title }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">{{ $notification->content }}</p>
                        @if ($notification->data && isset($notification->data['action_url']))<a href="{{ $notification->data['action_url'] }}" class="text-xs text-brand-600 hover:underline mt-1 inline-block">查看详情</a>@endif
                    </td>
                    <td class="px-4 py-3">@if (!$notification->is_read)<span class="badge bg-blue-100 text-blue-700">未读</span>@else<span class="badge bg-slate-100 text-slate-600">已读</span>@endif</td>
                    <td class="px-4 py-3 text-xs" style="color: var(--c-ink-subtle);">{{ $notification->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            @if (!$notification->is_read)<button class="btn btn-ghost btn-sm mark-read-btn" data-id="{{ $notification->id }}">已读</button>@endif
                            <button class="btn btn-ghost btn-sm text-red-500 delete-btn" data-id="{{ $notification->id }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-end gap-3 p-4 border-t border-border">
        <div>{{ $notifications->appends(request()->query())->links() }}</div>
    </div>
    @else
    <div class="p-12 text-center">
        <svg class="w-12 h-12 mx-auto text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 0 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
        <p class="text-sm mt-2" style="color: var(--c-ink-muted);">暂无通知</p>
    </div>
    @endif
</div>

@if (Auth::user()->isAdmin())
{{-- Announcement modal --}}
<div id="announcementModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">发布系统公告</h3>
            <button type="button" onclick="closeModal('announcementModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('notifications.create-announcement') }}" id="announcementForm">
            @csrf
            <div class="mb-4">
                <label class="label" for="ann_title">标题 <span class="text-red-500">*</span></label>
                <input type="text" class="input" id="ann_title" name="title" required>
            </div>
            <div class="mb-4">
                <label class="label" for="ann_content">内容 <span class="text-red-500">*</span></label>
                <textarea class="input" id="ann_content" name="content" rows="4" required></textarea>
            </div>
            <div class="mb-4">
                <label class="label" for="target_type">发布范围 <span class="text-red-500">*</span></label>
                <select class="input" id="target_type" name="target_type" required>
                    <option value="all">所有用户</option>
                    <option value="users">指定用户</option>
                    <option value="roles">指定角色</option>
                </select>
            </div>
            <label class="flex items-center gap-2 mb-4 cursor-pointer">
                <input type="checkbox" id="is_important" name="is_important" value="true" class="rounded border-border-strong w-4 h-4">
                <span class="text-sm" style="color: var(--c-ink-muted);">重要公告</span>
            </label>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('announcementModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">发布</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
function openModal(id) { var m = document.getElementById(id); if (m) { m.classList.remove('hidden'); m.classList.add('flex'); document.body.classList.add('overflow-hidden'); } }
function closeModal(id) { var m = document.getElementById(id); if (m) { m.classList.add('hidden'); m.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); } }
document.querySelectorAll('[data-modal]').forEach(function(modal) { modal.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); }); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') document.querySelectorAll('[data-modal]').forEach(function(m) { if (!m.classList.contains('hidden')) closeModal(m.id); }); });

function apiCall(url, method, body) {
    return fetch(url, {
        method: method,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        body: body ? JSON.stringify(body) : undefined
    }).then(function(r) { return r.json(); });
}

// Mark single as read
document.querySelectorAll('.mark-read-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        apiCall('/notifications/' + this.dataset.id + '/read', 'POST').then(function(d) { if (d.success) location.reload(); else alert(d.message || '操作失败'); }).catch(function() { alert('操作失败'); });
    });
});

// Mark all as read
document.getElementById('markAllAsReadBtn')?.addEventListener('click', function() {
    if (!confirm('确定全部标记为已读？')) return;
    apiCall('/notifications/read-all', 'POST').then(function(d) { if (d.success) location.reload(); else alert(d.message || '失败'); }).catch(function() { alert('失败'); });
});

// Delete single
document.querySelectorAll('.delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('确定删除？')) return;
        apiCall('/notifications/' + this.dataset.id, 'DELETE').then(function(d) { if (d.success) location.reload(); else alert(d.message || '失败'); }).catch(function() { alert('失败'); });
    });
});

// Select all
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.notif-checkbox').forEach(function(cb) { cb.checked = document.getElementById('selectAll').checked; });
});

// Batch operations
function getSelected() { return Array.from(document.querySelectorAll('.notif-checkbox:checked')).map(function(cb) { return parseInt(cb.value); }); }

document.getElementById('batchDeleteBtn')?.addEventListener('click', function() {
    var ids = getSelected();
    if (ids.length === 0) { alert('请选择通知'); return; }
    if (!confirm('删除 ' + ids.length + ' 条？')) return;
    apiCall('/notifications/batch', 'DELETE', { notification_ids: ids }).then(function(d) { if (d.success) location.reload(); else alert(d.message); }).catch(function() { alert('失败'); });
});

document.getElementById('batchReadBtn')?.addEventListener('click', function() {
    var ids = getSelected();
    if (ids.length === 0) { alert('请选择通知'); return; }
    if (!confirm('标记 ' + ids.length + ' 条为已读？')) return;
    apiCall('/notifications/batch-read', 'POST', { notification_ids: ids }).then(function(d) { if (d.success) location.reload(); else alert(d.message); }).catch(function() { alert('失败'); });
});

// Announcement form
document.getElementById('announcementForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    fetch(this.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.success) { closeModal('announcementModal'); location.reload(); } else alert(d.message || '失败'); })
        .catch(function() { alert('失败'); });
});
</script>
@endsection
