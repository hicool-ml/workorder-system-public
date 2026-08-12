@extends('layouts.app')
@section('title', '基础地址初始化')
@section('content')
@include('locations._topbar', [
    'active' => 'base',
    'title' => '基础地址',
    'subtitle' => '参照身份证行政区划代码（GB/T 2260）与物流结构化地址标准，一次性填写单位基础地址',
])

@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="max-w-2xl">
    <div class="card p-6">
        <div class="mb-5 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
            基础地址共 {{ $baseLevels->count() }} 级，是单位所在位置的固定地址（示例：XX省 → XX市 → XX区 → XX路 → XX号）。
            初始化完成后，日常只需在「地址树」中选择「区域/园区 → 楼栋 → 房间/工位」。省、市、区县可填写标准行政区划代码（与身份证前 6 位一致）。
        </div>

        <form method="POST" action="{{ route('locations.base-address.store') }}">
            @csrf
            <div class="space-y-4">
                @foreach($baseLevels as $lv)
                    @php($node = $existing[$lv->id] ?? null)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="label" for="name_{{ $lv->code }}">{{ $lv->name }} <span class="text-red-500">*</span></label>
                            <input type="text" class="input" id="name_{{ $lv->code }}" name="name_{{ $lv->code }}"
                                   value="{{ old('name_' . $lv->code, $node->name ?? '') }}" required maxlength="255"
                                   placeholder="如：{{ match($lv->code) { 'province' => 'XX省', 'city' => 'XX市', 'district' => 'XX区', 'street' => 'XX路', default => 'XX号' } }}">
                            @error('name_' . $lv->code) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="code_{{ $lv->code }}">标准代码（行政区划可填 GB/T 2260 代码）</label>
                            <input type="text" class="input" id="code_{{ $lv->code }}" name="code_{{ $lv->code }}"
                                   value="{{ old('code_' . $lv->code, $node->code ?? '') }}" maxlength="50"
                                   placeholder="{{ match($lv->code) { 'province' => '510000', 'city' => '510100', 'district' => '510112', default => '可留空' } }}">
                            @error('code_' . $lv->code) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">保存并初始化</button>
            </div>
        </form>
    </div>
</div>
@endsection
