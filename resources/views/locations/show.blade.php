@extends('layouts.app')
@section('title', '地址详情')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-ink">地址详情</h1>
    <a href="{{ route('locations.index') }}" class="btn btn-secondary">返回列表</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- 左：基本信息 --}}
    <div class="lg:col-span-1">
        <div class="card p-6">
            <h2 class="text-lg font-semibold text-ink mb-4">{{ $location->name }}</h2>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-ink-muted">完整地址</dt>
                    <dd class="text-ink font-medium mt-0.5">{{ $location->full_address }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">所属层级</dt>
                    <dd class="text-ink mt-0.5">{{ $location->level?->name ?? '未设置' }}（第{{ $location->level?->level ?? '-' }}层）</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">状态</dt>
                    <dd class="mt-0.5">
                        @if($location->status === 'active')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">启用</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500">禁用</span>
                        @endif
                    </dd>
                </div>
                @if($location->code)
                    <div><dt class="text-ink-muted">编码</dt><dd class="text-ink mt-0.5 font-mono text-xs">{{ $location->code }}</dd></div>
                @endif
                @if($location->description)
                    <div><dt class="text-ink-muted">描述</dt><dd class="text-ink mt-0.5">{{ $location->description }}</dd></div>
                @endif
            </dl>
            <div class="mt-6 flex gap-3">
                <a href="{{ route('locations.edit', $location) }}" class="btn btn-primary">编辑</a>
                <a href="{{ route('locations.create', ['parent_id' => $location->id]) }}" class="btn btn-secondary">添加子节点</a>
            </div>
        </div>
    </div>

    {{-- 右：子节点 --}}
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h3 class="text-sm font-semibold text-ink">下级地址（{{ $location->children->count() }}）</h3>
            </div>
            @if($location->children->isNotEmpty())
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-border">
                        @foreach($location->children as $child)
                            <tr class="hover:bg-surface-muted/50">
                                <td class="px-4 py-3 font-medium text-ink">{{ $child->name }}</td>
                                <td class="px-4 py-3 text-ink-muted">{{ $child->level?->name ?? '' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('locations.show', $child) }}" class="text-brand-600 hover:underline">查看</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-8 text-center text-ink-muted text-sm">该节点下暂无子地址</div>
            @endif
        </div>
    </div>
</div>
@endsection