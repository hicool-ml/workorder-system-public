@extends('layouts.app')

@section('title', '申报故障')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-ink">申报故障</h1>
        <p class="text-sm mt-1" style="color: var(--c-ink-muted);">填写故障信息，提交后系统将自动派单处理</p>
    </div>

    <form method="POST" action="{{ route('workorders.report.store') }}" enctype="multipart/form-data" id="reportForm">
        @csrf

        {{-- 报修人信息（自动填充，只读） --}}
        <div class="card p-5 mb-4">
            <h3 class="text-sm font-semibold text-ink mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/></svg>
                报修人信息
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="label">姓名</label>
                    <input type="text" class="input bg-surface" value="{{ auth()->user()->name }}" readonly>
                </div>
                <div>
                    <label class="label">联系电话</label>
                    <input type="text" class="input bg-surface" value="{{ auth()->user()->phone ?? '未填写' }}" readonly>
                    @if(!auth()->user()->phone)
                    <p class="text-xs text-orange-600 mt-1">建议在个人设置中补充手机号以便接收处理通知</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- 故障类别 --}}
        <div class="card p-5 mb-4">
            <h3 class="text-sm font-semibold text-ink mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                故障类别
            </h3>
            <div class="mb-3">
                <label class="label">大类 <span class="text-red-500">*</span></label>
                <select name="category_main" id="category_main" class="input" required onchange="loadSubCategories(this.value)">
                    <option value="">请选择</option>
                    @foreach($categories['main'] as $cat)
                        @if(in_array($cat->id, [1, 2]))
                        <option value="{{ $cat->id }}" data-prefix="{{ $cat->getTicketPrefix() }}">{{ $cat->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">二级分类 <span class="text-red-500">*</span></label>
                <select name="category_sub" id="category_sub" class="input" required>
                    <option value="">请先选择大类</option>
                </select>
            </div>
        </div>

        {{-- 故障描述 --}}
        <div class="card p-5 mb-4">
            <h3 class="text-sm font-semibold text-ink mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                故障描述 <span class="text-red-500">*</span>
            </h3>
            <textarea name="description" id="description" class="input" rows="4" required maxlength="2000" placeholder="请详细描述故障现象，例如：大屏幕无法开机、投影仪无信号等"></textarea>
            <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">最多2000字</p>
        </div>

        {{-- 故障地点 --}}
        <div class="card p-5 mb-4">
            <h3 class="text-sm font-semibold text-ink mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                故障地点
            </h3>
            <div class="mb-3">
                <label class="label">校区 <span class="text-red-500">*</span></label>
                <select name="campus_id" id="campus_id" class="input" required>
                    <option value="">请选择校区</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="label">楼栋 <span class="text-red-500">*</span></label>
                <input type="text" name="building" id="building" class="input" required placeholder="如：教学楼A栋" list="building-list">
                <datalist id="building-list">
                    @foreach($buildings as $b)
                        <option value="{{ $b->name }}">
                    @endforeach
                </datalist>
            </div>
            <div>
                <label class="label">详细地址</label>
                <input type="text" name="location_detail" id="location_detail" class="input" placeholder="如：3楼301教室">
            </div>
        </div>

        {{-- 附件 --}}
        <div class="card p-5 mb-4">
            <h3 class="text-sm font-semibold text-ink mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                附件上传（可选）
            </h3>
            <input type="file" name="attachments[]" id="attachments" multiple
                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" autocomplete="off"
                   class="input" style="padding: 0.5rem;">
            <div id="file-list" class="mt-2"></div>
        </div>

        {{-- 备注 --}}
        <div class="card p-5 mb-4">
            <label class="label">备注（可选）</label>
            <textarea name="other_reason" class="input" rows="2" maxlength="500" placeholder="补充说明"></textarea>
        </div>

        {{-- 隐藏字段 --}}
        <input type="hidden" name="contact_name" value="{{ auth()->user()->name }}">
        <input type="hidden" name="contact_phone" value="{{ auth()->user()->phone ?? '' }}">
        <input type="hidden" name="source" value="本台">
        <input type="hidden" name="priority" value="medium">
        <input type="hidden" name="department_name" value="{{ auth()->user()->remarks ?? '' }}">

        <div class="flex gap-3 justify-end">
            <a href="{{ route('workorders.index') }}" class="btn btn-secondary">取消</a>
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                提交申报
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
var subCategories = @json($categories['sub']);

function loadSubCategories(mainId) {
    var sel = document.getElementById('category_sub');
    sel.innerHTML = '<option value="">请选择</option>';

    var subs = subCategories[mainId] || [];
    subs.forEach(function(sub) {
        var opt = document.createElement('option');
        opt.value = sub.id;
        opt.textContent = sub.name;
        sel.appendChild(opt);
    });

    if (subs.length === 0) {
        sel.innerHTML = '<option value="">该大类暂无子分类</option>';
    }
}

// 文件预览
document.getElementById('attachments').addEventListener('change', function(e) {
    var list = document.getElementById('file-list');
    list.innerHTML = '';
    var files = Array.from(e.target.files);
    if (files.length > 5) {
        alert('最多上传5个文件');
        e.target.value = '';
        return;
    }
    files.forEach(function(f) {
        if (f.size > 10 * 1024 * 1024) {
            alert(f.name + ' 超过10MB限制');
            return;
        }
        var div = document.createElement('div');
        div.className = 'text-xs mt-1';
        div.style.color = 'var(--c-ink-muted)';
        div.textContent = f.name + ' (' + (f.size / 1024 / 1024).toFixed(1) + ' MB)';
        list.appendChild(div);
    });
});
</script>
@endsection
