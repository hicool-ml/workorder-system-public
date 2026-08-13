@extends('layouts.app')
@section('title', '新增项目')
@section('content')
@include('locations._topbar', [
    'active' => 'base',
    'title' => '新增项目',
    'subtitle' => '选择项目所在行政区划（省/市/区/街道），填写门牌/路段',
])

@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="max-w-2xl">
    <div class="card p-6">
        <div class="mb-5 p-3 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-sm">
            从行政区划库选择省/市/区/街道，手填门牌/路段。
            新建后可在该项目下添加区域/楼栋/房间。
        </div>

        <form method="POST" action="{{ route('locations.projects.store') }}">
            @csrf
            <div class="space-y-4">
                @foreach (['province', 'city', 'district', 'street'] as $code)
                    @php($lv = $baseLevels->firstWhere('code', $code))
                    @if($lv)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                            <div class="sm:col-span-2">
                                <label class="label" for="name_{{ $code }}">
                                    {{ $lv->name }} <span class="text-red-500">*</span>
                                </label>
                                <select class="input region-select" id="name_{{ $code }}"
                                        name="name_{{ $code }}" data-level="{{ $code }}"
                                        required @if($code !== 'province') disabled @endif>
                                    <option value="">-- 请选择 --</option>
                                </select>
                                @error('name_' . $code) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="code_{{ $code }}">代码（可选）</label>
                                <input type="text" class="input" id="code_{{ $code }}" name="code_{{ $code }}" maxlength="50" placeholder="可留空">
                            </div>
                        </div>
                    @endif
                @endforeach

                @php($roadLv = $baseLevels->firstWhere('code', 'road'))
                @if($roadLv)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <div class="sm:col-span-2">
                            <label class="label" for="name_road">
                                {{ $roadLv->name }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" class="input" id="name_road" name="name_road"
                                   required maxlength="255" placeholder="路段+门牌号，如：成洛大道 2025 号">
                            <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">填入路段名与门牌号，例如「成洛大道 2025 号」</p>
                            @error('name_road') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="code_road">代码（可选）</label>
                            <input type="text" class="input" id="code_road" name="code_road" maxlength="50" placeholder="可留空">
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('locations.base-address') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">创建项目</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var dataUrl = '{{ asset("data/china-regions.json") }}';
    var selects = document.querySelectorAll('select.region-select');
    if (selects.length === 0) return;

    var tree = null;

    function fillOptions(sel, names) {
        while (sel.options.length > 1) sel.remove(sel.options.length - 1);
        names.forEach(function (n) {
            var opt = document.createElement('option');
            opt.value = n; opt.textContent = n;
            sel.appendChild(opt);
        });
        sel.disabled = false;
    }

    function getChildNames(level, p, c, d) {
        if (!tree) return [];
        if (level === 'province') return Object.keys(tree);
        if (!p || !tree[p]) return [];
        if (level === 'city') return Object.keys(tree[p]);
        if (!c || !tree[p][c]) return [];
        if (level === 'district') return Object.keys(tree[p][c]);
        if (level === 'street') return (Array.isArray(tree[p][c][d]) ? tree[p][c][d] : []);
        return [];
    }

    function rebuild(level) {
        var sel = document.querySelector('select[data-level="' + level + '"]');
        if (!sel) return;
        var p = (document.querySelector('select[data-level="province"]') || {}).value || '';
        var c = (document.querySelector('select[data-level="city"]') || {}).value || '';
        var d = (document.querySelector('select[data-level="district"]') || {}).value || '';
        fillOptions(sel, getChildNames(level, p, c, d));
    }

    function init() {
        selects.forEach(function (sel) {
            sel.addEventListener('change', function () {
                var order = ['province', 'city', 'district', 'street'];
                var idx = order.indexOf(sel.dataset.level);
                for (var i = idx + 1; i < order.length; i++) rebuild(order[i]);
            });
        });
        ['province', 'city', 'district', 'street'].forEach(rebuild);
    }

    fetch(dataUrl, { cache: 'force-cache' })
        .then(function (r) { return r.json(); })
        .then(function (json) { tree = json; init(); })
        .catch(function () {
            selects.forEach(function (sel) {
                var input = document.createElement('input');
                input.type = 'text'; input.className = sel.className;
                input.id = sel.id; input.name = sel.name; input.required = sel.required;
                input.placeholder = '（行政区划库加载失败，请手动输入）';
                sel.parentNode.replaceChild(input, sel);
            });
        });
})();
</script>
@endsection
