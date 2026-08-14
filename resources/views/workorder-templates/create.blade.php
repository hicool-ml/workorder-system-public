@extends('layouts.app')
@section('title', '创建工单模板')
@section('content')
<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">创建工单模板</h1>
</div>

<div class="card p-6 max-w-3xl">
    <form method="POST" action="{{ route('workorder-templates.store') }}" id="templateForm">
        @csrf
        <div class="mb-6">
            <label class="label" for="name">模板名称 <span class="text-red-500">*</span></label>
            <input type="text" class="input" id="name" name="name" required maxlength="200" placeholder="如：投影仪故障">
        </div>

        <div class="mb-6">
            <label class="label" for="category_main_id">绑定工单大类（创建工单时选该大类自动应用此模板）</label>
            <select class="input" id="category_main_id" name="category_main_id">
                <option value="">-- 不绑定 --</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            @foreach($boundCategoryIds as $bid)
            <span class="hidden bound-cat-id" data-id="{{ $bid }}"></span>
            @endforeach
        </div>

        <input type="hidden" name="fields" id="fieldsJson">

        {{-- 必要字段 --}}
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-ink mb-3 pb-2 border-b border-border">必要字段（不可禁用）</h3>
            <div class="space-y-3">
                @foreach(\App\Models\WorkorderTemplate::ESSENTIAL_FIELDS as $field)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <div class="sm:col-span-1">
                            <span class="text-sm font-medium text-ink">{{ $field['label'] }} <span class="text-red-500">*</span></span>
                        </div>
                        <div class="sm:col-span-2">
                            @if($field['type'] === 'textarea')
                                <textarea class="input field-input" data-field="{{ $field['name'] }}" rows="2" placeholder="预填内容（可留空）"></textarea>
                            @elseif($field['type'] === 'select' && $field['name'] === 'category_main')
                                <select class="input field-input" data-field="category_main">
                                    <option value="">-- 不预填 --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            @elseif($field['type'] === 'select' && $field['name'] === 'category_sub')
                                <select class="input field-input" data-field="category_sub" disabled>
                                    <option value="">先选择大类</option>
                                </select>
                            @else
                                <input type="text" class="input field-input" data-field="{{ $field['name'] }}" placeholder="预填内容（可留空）">
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 建议字段 --}}
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-ink mb-3 pb-2 border-b border-border">建议字段（勾选启用）</h3>
            <div class="space-y-3" id="suggestedFields">
                @foreach(\App\Models\WorkorderTemplate::SUGGESTED_FIELDS as $field)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center field-row" data-field="{{ $field['name'] }}" data-category="suggested">
                        <div class="sm:col-span-1 flex items-center gap-2">
                            <input type="checkbox" class="field-enable rounded border-border-strong w-4 h-4" data-field="{{ $field['name'] }}">
                            <span class="text-sm text-ink">{{ $field['label'] }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            @if($field['type'] === 'select')
                                <select class="input field-input" data-field="{{ $field['name'] }}" disabled>
                                    <option value="">-- 不预填 --</option>
                                    @foreach($field['options'] as $opt)
                                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            @elseif($field['type'] === 'checkbox')
                                <input type="checkbox" class="field-input rounded border-border-strong w-4 h-4" data-field="{{ $field['name'] }}" data-type="checkbox" disabled>
                            @else
                                <input type="{{ $field['type'] }}" class="input field-input" data-field="{{ $field['name'] }}" placeholder="预填内容（可留空）" disabled>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 自定义字段 --}}
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-ink mb-3 pb-2 border-b border-border">自定义字段</h3>
            <div class="space-y-3" id="customFields"></div>
            <button type="button" id="addCustomField" class="btn btn-secondary btn-sm mt-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span>添加自定义字段</span>
            </button>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 border-t border-border">
            <a href="{{ route('workorder-templates.index') }}" class="btn btn-secondary">取消</a>
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>保存模板</span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
(function () {
    // 工单分类级联
    var subCats = {};
    @foreach($categories as $cat)
    subCats[{{ $cat->id }}] = [
        @foreach(\App\Models\WorkorderCategorySimplified::getSubCategories($cat->id) as $sub)
        {id: {{ $sub->id }}, name: "{{ $sub->name }}"}@if(!$loop->last),@endif
        @endforeach
    ];
    @endforeach

    document.querySelector('[data-field="category_main"]').addEventListener('change', function () {
        var subSel = document.querySelector('[data-field="category_sub"]');
        var mainId = this.value;
        subSel.innerHTML = '<option value="">-- 不预填 --</option>';
        if (mainId && subCats[mainId]) {
            subCats[mainId].forEach(function (s) {
                subSel.innerHTML += '<option value="' + s.id + '">' + s.name + '</option>';
            });
            subSel.disabled = false;
        } else {
            subSel.disabled = true;
        }
    });

    // 建议字段勾选启用/禁用输入框
    document.querySelectorAll('.field-enable').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var row = this.closest('.field-row');
            var input = row.querySelector('.field-input');
            input.disabled = !this.checked;
        });
    });

    // 自定义字段添加
    var customIdx = 0;
    document.getElementById('addCustomField').addEventListener('click', function () {
        var container = document.getElementById('customFields');
        var div = document.createElement('div');
        div.className = 'grid grid-cols-1 sm:grid-cols-12 gap-2 items-center';
        div.innerHTML = `
            <input type="text" class="input sm:col-span-3 custom-label" placeholder="字段名称（如：设备型号）">
            <select class="input sm:col-span-2 custom-type">
                <option value="text">单行文本</option>
                <option value="textarea">多行文本</option>
                <option value="number">数字</option>
                <option value="date">日期</option>
            </select>
            <input type="text" class="input sm:col-span-6 custom-value" placeholder="预填内容（可留空）">
            <button type="button" class="btn btn-ghost btn-icon btn-sm text-red-500 sm:col-span-1 custom-remove">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;
        container.appendChild(div);
        div.querySelector('.custom-remove').addEventListener('click', function () { div.remove(); });
    });

    // 表单提交时收集 fields JSON
    document.getElementById('templateForm').addEventListener('submit', function (e) {
        var fields = [];

        // 必要字段
        document.querySelectorAll('[data-category="essential"], #templateForm > div .field-input[data-field]').forEach(function () {});
        // 简单做法：遍历必要字段定义
        var essentialNames = ['description', 'category_main', 'category_sub'];
        essentialNames.forEach(function (name) {
            var input = document.querySelector('.field-input[data-field="' + name + '"]');
            if (input) {
                var val = input.type === 'checkbox' ? input.checked : input.value;
                if (val !== '' && val !== false) {
                    fields.push({ name: name, category: 'essential', value: val });
                } else {
                    fields.push({ name: name, category: 'essential', value: null });
                }
            }
        });

        // 建议字段（仅勾选的）
        document.querySelectorAll('.field-row[data-category="suggested"]').forEach(function (row) {
            var enable = row.querySelector('.field-enable');
            if (!enable || !enable.checked) return;
            var name = enable.dataset.field;
            var input = row.querySelector('.field-input');
            var val = input.type === 'checkbox' ? input.checked : input.value;
            fields.push({ name: name, category: 'suggested', value: val });
        });

        // 自定义字段
        document.querySelectorAll('#customFields > div').forEach(function (row) {
            var label = row.querySelector('.custom-label').value.trim();
            if (!label) return;
            var type = row.querySelector('.custom-type').value;
            var val = row.querySelector('.custom-value').value;
            fields.push({ name: 'custom_' + label, label: label, type: type, category: 'custom', value: val });
        });

        document.getElementById('fieldsJson').value = JSON.stringify(fields);
    });
})();
</script>
@endsection
