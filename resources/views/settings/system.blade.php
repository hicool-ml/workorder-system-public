@extends('layouts.app')
@section('title', '系统设置')
@section('content')
<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">系统设置</h1>
</div>

<div class="space-y-6">
    <x-settings._card title="基础信息">
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="label" for="system_name">系统名称</label>
                    <input type="text" class="input" id="system_name" name="settings[system_name]" value="{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '校园网工单系统' }}">
                </div>
                <div>
                    <label class="label" for="system_version">版本号</label>
                    <input type="text" class="input" id="system_version" name="settings[system_version]" value="{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}">
                </div>
                <div>
                    <label class="label" for="system_release_date">发布日期</label>
                    <input type="date" class="input" id="system_release_date" name="settings[system_release_date]" value="{{ $groupedSettings['version']->firstWhere('key', 'system_release_date')?->typed_value ?? date('Y-m-d') }}">
                </div>
                <div class="sm:col-span-3">
                    <label class="label" for="system_url">系统访问地址</label>
                    <input type="url" class="input" id="system_url" name="settings[system_url]" value="{{ $groupedSettings['system']->firstWhere('key', 'system_url')?->typed_value ?? '' }}" placeholder="http://192.168.1.100:8099">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">企业微信通知中的工单链接会使用此地址，需填实际可访问的 IP/域名</p>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存设置</span>
                </button>
            </div>
        </form>
    </x-settings._card>
</div>
@endsection
