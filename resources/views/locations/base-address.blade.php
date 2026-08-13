@extends('layouts.app')
@section('title', '基础地址')
@section('content')
@include('locations._topbar', [
    'active' => 'base',
    'title' => '基础地址',
    'subtitle' => '管理多个项目/物业的基础地址，可跨省市；每个项目下独立维护区域/楼栋/房间',
])

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-ink-muted">共 {{ count($projectData) }} 个项目</p>
    <a href="{{ route('locations.projects.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        <span>新增项目</span>
    </a>
</div>

@if(empty($projectData))
    <div class="card p-8 text-center">
        <svg class="w-12 h-12 mx-auto mb-3 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
        <p class="text-sm text-ink-muted mb-4">尚未创建任何项目地址</p>
        <a href="{{ route('locations.projects.create') }}" class="btn btn-primary">创建第一个项目</a>
    </div>
@else
    <div class="space-y-3">
        @foreach($projectData as $idx => $project)
            <div class="card p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="badge bg-brand-100 text-brand-700">项目 {{ $idx + 1 }}</span>
                            @if($project['child_count'] > 0)
                                <span class="badge bg-blue-100 text-blue-700">{{ $project['child_count'] }} 个区域</span>
                            @else
                                <span class="badge bg-gray-100 text-gray-500">无区域</span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-ink break-all">{{ $project['full_address'] }}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('locations.projects.edit', $project['root']->id) }}" class="btn btn-ghost btn-icon btn-sm" title="编辑门牌/路段">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                        </a>
                        @if($project['child_count'] == 0)
                        <form action="{{ route('locations.projects.destroy', $project['root']->id) }}" method="POST" onsubmit="return confirm('确定删除该项目地址？')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除项目">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
