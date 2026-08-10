@extends('layouts.app')
@section('title', '详细设置')
@section('content')
<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">详细设置</h1>
</div>

<div class="space-y-6">
    @foreach($categorizedSettings as $label => $items)
    <x-settings._card :title="$label">
        <div class="md:hidden divide-y divide-border">
            @foreach($items as $setting)
            <div class="p-4">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <code class="text-sm text-ink">{{ $setting->key }}</code>
                    @if($setting->type === 'boolean')<span class="badge {{ $setting->typed_value ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $setting->typed_value ? '是' : '否' }}</span>@endif
                </div>
                <p class="text-xs mb-2" style="color: var(--c-ink-subtle);">{{ $setting->description ?? '-' }}</p>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="editSetting('{{ $setting->key }}', '{{ $setting->value }}', '{{ $setting->type }}')">编辑</button>
                    <form method="POST" action="{{ route('system-settings.destroy', $setting) }}" class="inline" onsubmit="return confirm('确定要删除这个设置吗？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm text-red-500">删除</button></form>
                </div>
            </div>
            @endforeach
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-border text-left">
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">设置键</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">值</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">类型</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">描述</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">公开</th>
                    <th class="px-5 py-3 font-medium text-right" style="color: var(--c-ink-muted);">操作</th>
                </tr></thead>
                <tbody>
                @foreach($items as $setting)
                <tr class="border-b border-border">
                    <td class="px-5 py-3"><code class="text-ink">{{ $setting->key }}</code></td>
                    <td class="px-5 py-3 text-ink">@if($setting->type === 'boolean')<span class="badge {{ $setting->typed_value ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $setting->typed_value ? '是' : '否' }}</span>@else{{ Str::limit($setting->value, 50) }}@endif</td>
                    <td class="px-5 py-3"><span class="badge bg-blue-100 text-blue-700">{{ $setting->type }}</span></td>
                    <td class="px-5 py-3 text-ink">{{ $setting->description ?? '-' }}</td>
                    <td class="px-5 py-3">@if($setting->is_public)<span class="text-green-600">是</span>@else<span style="color: var(--c-ink-subtle);">否</span>@endif</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" title="编辑" onclick="editSetting('{{ $setting->key }}', '{{ $setting->value }}', '{{ $setting->type }}')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('system-settings.destroy', $setting) }}" class="inline" onsubmit="return confirm('确定要删除这个设置吗？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></form>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-settings._card>
    @endforeach
</div>

{{-- 编辑设置弹窗 --}}
<div id="editSettingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" data-modal onclick="if(event.target===this)closeModal('editSettingModal')">
    <div class="card max-w-md w-full">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <h3 class="text-sm font-semibold text-ink">编辑设置</h3>
            <button type="button" onclick="closeModal('editSettingModal')" class="btn btn-ghost btn-icon btn-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="label">设置键</label>
                <input type="text" class="input" id="edit_key" readonly>
            </div>
            <div>
                <label class="label">设置值</label>
                <input type="text" class="input" id="edit_value" name="settings[edit_key]">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">根据设置类型输入相应的值</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('editSettingModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script>
function openModal(id) {
    var el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}
function closeModal(id) {
    var el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal('editSettingModal');
});
function editSetting(key, value, type) {
    document.getElementById('edit_key').value = key;
    var valueInput = document.getElementById('edit_value');
    valueInput.name = 'settings[' + key + ']';
    valueInput.value = value;
    openModal('editSettingModal');
}
</script>
@endsection
