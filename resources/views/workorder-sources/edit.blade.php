@extends('layouts.app')

@section('title', '编辑工单来源')

@section('content')
<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <div class="card">
                <div class="px-4 py-3 border-b border-border bg-surface-muted rounded-t-xl">
                    <h3 class="text-sm font-semibold text-ink">编辑工单来源</h3>
                    <div class="card-tools">
                        <a href="{{ route('workorder-sources.index') }}" class="btn btn-default btn-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回
                        </a>
                    </div>
                </div>
                <form action="{{ route('workorder-sources.update', $workorderSource) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <div class="space-y-1">
                                    <label for="name">来源名称 <span class="text-red-600">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $workorderSource->name) }}" 
                                           placeholder="请输入来源名称" required autocomplete="off">
                                    @error('name')
                                        <span class="text-xs text-red-600 mt-1" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="col-md-8">
                                <div class="space-y-1">
                                    <label for="description">描述</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3" 
                                              placeholder="请输入来源描述" autocomplete="off">{{ old('description', $workorderSource->description) }}</textarea>
                                    @error('description')
                                        <span class="text-xs text-red-600 mt-1" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="space-y-1">
                                    <label for="sort_order">排序顺序</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $workorderSource->sort_order) }}" 
                                           min="0" autocomplete="off">
                                    @error('sort_order')
                                        <span class="text-xs text-red-600 mt-1" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">数字越小排序越靠前</small>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="rounded border-border-strong w-4 h-4" id="is_active" 
                                       name="is_active" value="1" {{ old('is_active', $workorderSource->is_active) ? 'checked' : '' }}>
                                <label class="text-sm" for="is_active">启用状态</label>
                            </div>
                            <small class="form-text text-muted">禁用后，创建工单时将无法选择此来源</small>
                        </div>

                        @if($workorderSource->workorders()->exists())
                            <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z M12 9v4 M12 17h.01"/></svg>
                                <strong>注意：</strong>已有工单使用了此来源，修改来源代码可能会影响历史数据的显示。
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8"/></svg> 更新
                        </button>
                        <a href="{{ route('workorder-sources.index') }}" class="btn btn-default ml-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg> 取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
