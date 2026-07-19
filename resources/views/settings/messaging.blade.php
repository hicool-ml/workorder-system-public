@extends('layouts.app')
@section('title', '消息设置')
@section('content')
<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">消息设置</h1>
</div>

<div class="space-y-6">
    <x-settings._card title="消息通道">
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- 通知规则 --}}
            <a href="{{ route('system-settings.notification-rules') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50 dark:bg-blue-900/20">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 0 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">通知规则</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">按工单事件配置站内通知和短信通知</p>
            </a>

            {{-- 短信配置 --}}
            <a href="{{ route('system-settings.sms') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-green-50 dark:bg-green-900/20">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">短信配置</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">短信服务商密钥和模板配置</p>
            </a>

            {{-- 企业微信群机器人 --}}
            <a href="{{ route('system-settings.wecom') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0z M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0z M16 19a4 4 0 1 0-8 0 4 4 0 0 0 8 0z M24 19a4 4 0 1 0-8 0 4 4 0 0 0 8 0z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">企业微信</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">群机器人 Webhook 通知
                    @if(filter_var(\App\Models\SystemSetting::get('wecom_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN))
                    <span class="text-green-600 font-medium ml-1">已启用</span>
                    @else
                    <span class="text-orange-500 ml-1">未启用</span>
                    @endif
                </p>
            </a>
        </div>
    </x-settings._card>
</div>
@endsection
