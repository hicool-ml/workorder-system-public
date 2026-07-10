@extends('layouts.app')

@section('title', '编辑校区')

@section('content')
<div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">编辑校区</h1>
    <div class="flex gap-2">
        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回列表
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <div class="card p-5">
            <div>
                <form action="{{ route('locations.update-campus', $campus->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="label">校区名称 <span class="text-red-500">*</span></label>
                        <input type="text" class="input @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $campus->name) }}" required>
                        @error('name')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="label">校区描述</label>
                        <textarea class="input @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3">{{ old('description', $campus->description) }}</textarea>
                        @error('description')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                        <div>
                            <label for="sort_order" class="label">排序顺序</label>
                            <input type="number" class="input @error('sort_order') is-invalid @enderror"
                                   id="sort_order" name="sort_order" value="{{ old('sort_order', $campus->sort_order) }}" min="0">
                            @error('sort_order')
                                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                            <div class="text-xs text-ink-muted mt-1">数字越小排序越前</div>
                        </div>
                        <div>
                            <label for="status" class="label">状态 <span class="text-red-500">*</span></label>
                            <select class="input @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status', $campus->status) == 'active' ? 'selected' : '' }}>启用</option>
                                <option value="inactive" {{ old('status', $campus->status) == 'inactive' ? 'selected' : '' }}>禁用</option>
                            </select>
                            @error('status')
                                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary mr-2">取消</a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8"/></svg> 更新校区
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection