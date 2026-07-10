@extends('layouts.app')

@section('title', '工单来源管理')

@push('styles')
<link href="{{ asset('css/workorder-sources-enhanced.css?v=' . time()) }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">工单来源管理</h3>
                    <div class="card-tools">
                        <a href="{{ route('workorder-sources.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> 新增工单来源
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 8%">
                                        <i class="fas fa-hashtag"></i> ID
                                    </th>
                                    <th style="width: 35%">
                                        <i class="fas fa-tag"></i> 来源名称
                                    </th>
                                    <th style="width: 12%">
                                        <i class="fas fa-sort"></i> 排序
                                    </th>
                                    <th style="width: 15%">
                                        <i class="fas fa-toggle-on"></i> 状态
                                    </th>
                                    <th style="width: 30%">
                                        <i class="fas fa-cogs"></i> 操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sources as $source)
                                    <tr class="{{ $source->is_active ? '' : 'table-secondary' }}">
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $source->id }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $source->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $source->sort_order }}</span>
                                        </td>
                                        <td>
                                            @if($source->is_active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> 启用
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-times-circle"></i> 禁用
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group" style="flex-wrap: nowrap;">
                                                <a href="{{ route('workorder-sources.edit', $source) }}"
                                                   class="btn btn-outline-primary"
                                                   title="编辑来源：{{ $source->name }}"
                                                   style="white-space: nowrap;">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="d-none d-md-inline">编辑</span>
                                                </a>

                                                <form action="{{ route('workorder-sources.toggle-status', $source) }}"
                                                      method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="btn {{ $source->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                            title="{{ $source->is_active ? '禁用来源：' . $source->name : '启用来源：' . $source->name }}"
                                                            style="white-space: nowrap;">
                                                        <i class="fas {{ $source->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                        <span class="d-none d-md-inline">{{ $source->is_active ? '禁用' : '启用' }}</span>
                                                    </button>
                                                </form>

                                                @if(!$source->workorders()->exists())
                                                    <form action="{{ route('workorder-sources.destroy', $source) }}"
                                                          method="POST" style="display: inline-block;"
                                                          onsubmit="return confirm('确定要删除工单来源「' . $source->name . '」吗？此操作不可撤销！');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-outline-danger"
                                                                title="删除来源：{{ $source->name }}"
                                                                style="white-space: nowrap;">
                                                            <i class="fas fa-trash"></i>
                                                            <span class="d-none d-md-inline">删除</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="btn btn-outline-secondary" disabled
                                                            title="已有工单使用此来源，无法删除"
                                                            style="white-space: nowrap;">
                                                        <i class="fas fa-trash"></i>
                                                        <span class="d-none d-md-inline">删除</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <h5>暂无工单来源数据</h5>
                                                <p>请点击"新增工单来源"按钮添加第一个工单来源</p>
                                                <a href="{{ route('workorder-sources.create') }}" class="btn btn-primary mt-2">
                                                    <i class="fas fa-plus"></i> 新增工单来源
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                共 {{ $sources->count() }} 个工单来源，
                                {{ $sources->where('is_active', true)->count() }} 个已启用
                            </small>
                        </div>
                        <div>
                            {{ $sources->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection