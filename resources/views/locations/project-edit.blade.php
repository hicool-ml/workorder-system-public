@extends('layouts.app')
@section('title', '编辑项目')
@section('content')
@include('locations._topbar', [
    'active' => 'base',
    'title' => '编辑项目',
    'subtitle' => '行政区划不可修改，仅门牌/路段可编辑',
])

@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="max-w-2xl">
    <div class="card p-6">
        <div class="mb-5 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
            前 4 级（省/市/区/街道）不可修改，仅「门牌/路段」可编辑。
        </div>

        <div class="mb-4">
            <label class="label">行政区划</label>
            <div class="px-4 py-3 rounded-lg bg-surface-muted text-sm text-ink flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" style="color: var(--c-ink-muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                <span class="font-medium">
                    @php
                        $parts = [];
                        foreach (['province', 'city', 'district', 'street'] as $code) {
                            $lv = $baseLevels->firstWhere('code', $code);
                            if ($lv && isset($chain[$lv->id])) $parts[] = $chain[$lv->id]->name;
                        }
                    @endphp
                    {{ implode(' / ', $parts) }}
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('locations.projects.update', $root->id) }}">
            @csrf @method('PUT')
            @php($roadLv = $baseLevels->firstWhere('code', 'road'))
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div class="sm:col-span-2">
                    <label class="label" for="name_road">
                        {{ $roadLv?->name ?? '门牌/路段' }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" class="input" id="name_road" name="name_road"
                           value="{{ old('name_road', $root->name) }}"
                           required maxlength="255"
                           placeholder="路段+门牌号，如：成洛大道 2025 号">
                    @error('name_road') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="code_road">代码（可选）</label>
                    <input type="text" class="input" id="code_road" name="code_road"
                           value="{{ old('code_road', $root->code ?? '') }}"
                           maxlength="50" placeholder="可留空">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('locations.base-address') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">保存修改</button>
            </div>
        </form>
    </div>
</div>
@endsection
