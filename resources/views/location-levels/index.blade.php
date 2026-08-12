@extends('layouts.app')
@section('title', '地址层级定义')
@section('content')
@include('locations._topbar', [
    'active' => 'levels',
    'title' => '地址层级定义',
    'subtitle' => '自定义地址分级方案；"基础地址"层级（省市区街道门牌）初始化后固定存在，"日常"层级（校区/楼栋/房间）供工单级联选择',
    'actions' => '<a href="' . route('location-levels.create') . '" class="btn btn-primary">'
        . '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>'
        . '<span>新增层级</span></a>',
])

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="card overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-surface-muted text-ink-muted text-xs uppercase">
            <tr>
                <th class="text-left px-4 py-3 font-medium">层级深度</th>
                <th class="text-left px-4 py-3 font-medium">层级名称</th>
                <th class="text-left px-4 py-3 font-medium">代码</th>
                <th class="text-left px-4 py-3 font-medium">描述</th>
                <th class="text-left px-4 py-3 font-medium">类型</th>
                <th class="text-left px-4 py-3 font-medium">状态</th>
                <th class="text-right px-4 py-3 font-medium">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($levels as $level)
                <tr class="hover:bg-surface-muted/50">
                    <td class="px-4 py-3 text-ink-muted">{{ $level->level }}</td>
                    <td class="px-4 py-3 font-medium text-ink">{{ $level->name }}</td>
                    <td class="px-4 py-3 text-ink-muted font-mono text-xs">{{ $level->code }}</td>
                    <td class="px-4 py-3 text-ink-muted">{{ $level->description ?? '' }}</td>
                    <td class="px-4 py-3">
                        @if($level->is_daily_use)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">日常</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">基础地址</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($level->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">启用</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">禁用</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('location-levels.edit', $level) }}" class="text-brand-600 hover:underline text-sm">编辑</a>
                        <form action="{{ route('location-levels.destroy', $level) }}" method="POST" class="inline ml-3" onsubmit="return confirm('确定删除该层级？')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:underline text-sm">删除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-ink-muted">尚未定义任何层级，点击"新增层级"开始配置</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection