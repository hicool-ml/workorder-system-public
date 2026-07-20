@extends('layouts.app')

@section('title', '无权限访问')

@section('content')
<div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
    <div class="w-16 h-16 rounded-full bg-red-50 text-red-500 flex items-center justify-center mb-5">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 0 0 1.7-3L13.7 4a2 2 0 0 0-3.4 0L3.3 16A2 2 0 0 0 5 19z"/></svg>
    </div>
    <h1 class="text-xl font-semibold text-ink mb-2">您没有访问权限</h1>
    <p class="text-sm text-ink-muted max-w-md mb-1">
        @php
            // abort(403, '消息') 抛出的 HttpException 消息，例如 RoleMiddleware 与各控制器的权限检查文案。
            $detail = $exception->getMessage() ?? '';
            // 过滤掉 Laravel 默认的纯状态码文本，只显示有意义的中文说明
            $detail = trim($detail);
            if ($detail === '' || $detail === '403' || $detail === 'Forbidden' || $detail === 'This action is unauthorized.') {
                $detail = '';
            }
        @endphp
        @if($detail)
            {{ $detail }}
        @else
            您当前的角色无权访问此页面或执行此操作。如确属需要，请联系管理员开通相应权限。
        @endif
    </p>
    <div class="flex flex-wrap items-center justify-center gap-2 mt-6">
        <a href="javascript:history.back()" class="btn btn-secondary">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg>
            <span>返回上一页</span>
        </a>
        <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" class="btn btn-primary">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/></svg>
            <span>返回工作台</span>
        </a>
    </div>
</div>
@endsection
