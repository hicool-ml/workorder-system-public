@extends('layouts.app')

@section('title', '申报故障')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-ink">申报故障</h1>
        <p class="text-sm mt-1" style="color: var(--c-ink-muted);">填写故障信息，提交后系统将尽快安排处理</p>
    </div>

    <form method="POST" action="{{ route('workorders.report.store') }}" enctype="multipart/form-data" id="reportForm" data-prevent-double-submit>
        @csrf
        <input type="hidden" name="submission_token" value="{{ \Illuminate\Support\Str::uuid() }}">

        {{-- 报修人信息 --}}
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
                    <p class="text-xs text-orange-600 mt-1">建议在个人设置中补充手机号以便接收通知</p>
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
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
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
                @if(!empty($addressPrefix))
                    <span class="ml-2 text-xs font-normal" style="color: var(--c-ink-subtle);">前缀：{{ $addressPrefix }}</span>
                @endif
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="label">区域 <span class="text-red-500">*</span></label>
                    <select name="campus_id" id="campus_id" class="input" required onchange="updateBuildings(this.value)">
                        <option value="">请选择</option>
                        @foreach($campusOptions as $campusLocationId => $campusName)
                            <option value="{{ $campusLocationId }}">{{ $campusName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">楼栋 <span class="text-red-500">*</span></label>
                    <select name="building" id="building" class="input" required disabled>
                        <option value="">请先选择区域</option>
                    </select>
                </div>
                <div>
                    <label class="label">门牌号</label>
                    <input type="text" name="location_detail" id="location_detail" class="input" placeholder="如：303室" maxlength="500" autocomplete="off">
                </div>
            </div>
        </div>

        {{-- 附件上传（含拍照） --}}
        <div class="card p-5 mb-4">
            <h3 class="text-sm font-semibold text-ink mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                附件上传（可选）
            </h3>
            <div class="flex gap-2 mb-2">
                <button type="button" onclick="openCameraModal('attachments')" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 0 1 2-2h.93a2 2 0 0 0 1.66-.9l.82-1.2A2 2 0 0 1 11.07 4h1.86a2 2 0 0 1 1.66.9l.82 1.2a2 2 0 0 0 1.66.9H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
                    拍照
                </button>
                <button type="button" onclick="document.getElementById('attachments').click()" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    选择文件
                </button>
            </div>
            <input type="file" class="hidden" id="attachments" name="attachments[]" multiple
                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" autocomplete="off">
            <div id="attachmentPreview" class="mt-2"></div>
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

        <div class="flex gap-3 justify-end">
            <a href="{{ route('workorders.index') }}" class="btn btn-secondary">取消</a>
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                提交申报
            </button>
        </div>
    </form>
</div>

{{-- 拍照组件 --}}
@include('workorders._camera')
@endsection

@section('scripts')
@include('partials._double_submit_guard')
<script src="{{ asset('vendor/browser-image-compression.min.js') }}" defer></script>
<script>
var subCategories = @json($categories['sub']);
var campusBuildings = @json($campusBuildings);
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

function updateBuildings(campusId) {
    var sel = document.getElementById('building');
    sel.innerHTML = '<option value="">请选择楼栋</option>';
    var data = campusBuildings[campusId];
    if (data && data.buildings && data.buildings.length > 0) {
        data.buildings.forEach(function(b) {
            var opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.name;
            sel.appendChild(opt);
        });
        sel.disabled = false;
    } else {
        sel.innerHTML = '<option value="">该区域暂无楼栋数据</option>';
        sel.disabled = false;
    }
}

// 附件处理（含图片压缩）
var attachmentFiles = [];
var fileCounter = 0;

document.getElementById('attachments').addEventListener('change', handleAttachmentSelect);

function handleAttachmentSelect(input) {
    if (!input || !input.files) return;
    var files = Array.from(input.files);
    processFiles(files, input);
}

async function processFiles(files, input) {
    for (var i = 0; i < files.length; i++) {
        if (attachmentFiles.length >= 5) { alert('最多上传5个文件'); break; }
        var file = files[i];
        if (file.size > 10 * 1024 * 1024) { alert(file.name + ' 超过10MB限制'); continue; }

        // 图片压缩
        var processedFile = file;
        var compressed = false;
        if (file.type.startsWith('image/') && file.size > 500 * 1024 && typeof imageCompression !== 'undefined') {
            try {
                processedFile = await imageCompression(file, {
                    maxSizeMB: 1.5,
                    maxWidthOrHeight: 1920,
                    useWebWorker: true
                });
                compressed = true;
            } catch(e) { processedFile = file; }
        }

        var idx = fileCounter++;
        attachmentFiles.push({ file: processedFile, index: idx, originalName: file.name });
        renderPreview(idx, processedFile, file.name, compressed);
    }
    // 重置 input 以允许重复选择
    if (input) input.value = '';
    syncToInput();
}

function renderPreview(idx, file, originalName, compressed) {
    var container = document.getElementById('attachmentPreview');
    var div = document.createElement('div');
    div.className = 'attachment-item mb-2 p-3 border border-border rounded-lg flex items-start gap-3';
    div.dataset.fileIndex = idx;

    var thumb = '';
    if (file.type.startsWith('image/')) {
        thumb = '<img src="' + URL.createObjectURL(file) + '" class="w-12 h-12 rounded object-cover shrink-0">';
    } else {
        thumb = '<div class="w-12 h-12 rounded flex items-center justify-center bg-surface shrink-0"><svg class="w-6 h-6" style="color: var(--c-ink-muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></div>';
    }

    var sizeStr = (file.size / 1024 / 1024).toFixed(1) + ' MB';
    if (compressed) sizeStr = sizeStr + ' (已压缩)';

    div.innerHTML = thumb +
        '<div class="flex-1 min-w-0">' +
            '<p class="text-sm font-medium text-ink truncate">' + originalName + '</p>' +
            '<p class="text-xs" style="color: var(--c-ink-subtle);">' + sizeStr + '</p>' +
        '</div>' +
        '<button type="button" class="btn btn-ghost btn-icon btn-sm text-red-500 shrink-0" onclick="removeAttachment(' + idx + ')">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
        '</button>';
    container.appendChild(div);
}

function removeAttachment(idx) {
    attachmentFiles = attachmentFiles.filter(function(f) { return f.index !== idx; });
    var el = document.querySelector('[data-file-index="' + idx + '"]');
    if (el) el.remove();
    syncToInput();
}

function syncToInput() {
    var dt = new DataTransfer();
    attachmentFiles.forEach(function(f) { dt.items.add(f.file); });
    document.getElementById('attachments').files = dt.files;
}
</script>
@endsection