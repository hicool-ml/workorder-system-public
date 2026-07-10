@extends('layouts.app')

@section('title', '地址详情')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">地址详情</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('locations.index') }}" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
        <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> 编辑
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">地址信息</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">地址名称：</div>
                    <div class="col-sm-9">{{ $location->name }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">校区：</div>
                    <div class="col-sm-9">
                        <span class="badge bg-info">{{ $location->campus_text }}</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">建筑类型：</div>
                    <div class="col-sm-9">
                        <span class="badge bg-secondary">{{ $location->building_type_text }}</span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">状态：</div>
                    <div class="col-sm-9">
                        @if($location->status === 'active')
                            <span class="badge bg-success">{{ $location->status_text }}</span>
                        @else
                            <span class="badge bg-danger">{{ $location->status_text }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">排序：</div>
                    <div class="col-sm-9">{{ $location->sort_order ?: 0 }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">描述：</div>
                    <div class="col-sm-9">{{ $location->description ?: '-' }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">创建时间：</div>
                    <div class="col-sm-9">{{ $location->created_at->format('Y-m-d H:i:s') }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">更新时间：</div>
                    <div class="col-sm-9">{{ $location->updated_at->format('Y-m-d H:i:s') }}</div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('locations.index') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                    <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 编辑
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 完整地址 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">完整地址</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $location->full_name }}</p>
            </div>
        </div>
        
        <!-- 校区说明 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">校区说明</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>老校区：</strong>包含1-7教学楼、1-10学生宿舍
                </div>
                <div class="mb-2">
                    <strong>新校区：</strong>包含8-14教学楼、11-18学生宿舍
                </div>
                <div class="mb-2">
                    <strong>东盟校区：</strong>包含A-J教学楼、19-20学生宿舍
                </div>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">操作</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 编辑地址
                    </a>
                    <form action="{{ route('locations.destroy', $location->id) }}" method="POST" onsubmit="return confirm('确定要删除这个地址吗？')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash"></i> 删除地址
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection