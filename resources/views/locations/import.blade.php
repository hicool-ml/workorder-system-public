@extends('layouts.app')
@section('title', '导入日常地址')
@section('content')
@include('locations._topbar', [
    'active' => 'import',
    'title' => '批量导入',
    'subtitle' => '批量导入「校区/园区 → 楼栋 → 房间」，自动挂载到基础地址之下',
])

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

@unless($baseInitialized)
    <div class="card p-6 mb-4">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h2 class="font-medium text-ink">基础地址尚未初始化</h2>
                <p class="text-sm text-ink-muted mt-1">请先在「基础地址」Tab 完成单位基础地址初始化，再回到此页导入日常地址。</p>
            </div>
            <a href="{{ route('locations.base-address') }}" class="btn btn-primary whitespace-nowrap">去初始化基础地址</a>
        </div>
    </div>
@else
    <div class="card p-6 mb-4">
        <h2 class="font-medium text-ink">导入预览</h2>
        <p class="text-sm text-ink-muted mt-1">
            当前基础地址：
            @if($root)
                <span class="font-medium text-ink">{{ $root->full_address_delimited }}</span>
            @else
                未找到基础地址，请重新初始化
            @endif
        </p>
        <p class="text-sm text-ink-muted mt-2">
            导入的日常层级（按序）：<span class="font-medium text-ink">{{ $dailyLevels->pluck('name')->implode(' → ') }}</span>
        </p>
    </div>

    <div class="card p-6">
        <h2 class="font-medium text-ink">上传 CSV 导入</h2>
        <ol class="text-sm text-ink-muted mt-2 mb-4 list-decimal list-inside space-y-1">
            <li>点击下方按钮下载导入模板（列 = 日常层级，如：校区/园区、楼栋、房间）</li>
            <li>按模板填写数据，每行一条完整地址（可只填到楼栋，房间留空）</li>
            <li>上传 CSV 文件，系统自动查找或创建各级节点</li>
        </ol>

        <form method="POST" action="{{ route('locations.import.store') }}" enctype="multipart/form-data" class="flex items-end gap-3 flex-wrap">
            @csrf
            <div>
                <label class="label" for="file">CSV 文件 <span class="text-red-500">*</span></label>
                <input type="file" class="input" id="file" name="file" accept=".csv,.txt" required>
                @error('file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary">开始导入</button>
            <a href="{{ route('locations.import-template') }}" class="btn btn-secondary">下载导入模板</a>
        </form>

        <p class="text-xs text-ink-muted mt-3">
            说明：重复名称复用已有节点（不重复创建）；每行从「校区/园区」层级开始，中间层级为空将整行跳过。
        </p>
    </div>
@endunless
@endsection
