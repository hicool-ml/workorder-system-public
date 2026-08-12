<!DOCTYPE html>
<html lang="zh-CN" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">
<title>@yield('title', \App\Helpers\SystemHelper::getSystemName())</title>

    {{-- Dark mode: apply before paint to prevent flash --}}
    <script>
        (function() {
            var stored = localStorage.getItem('theme') || 'system';
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = stored === 'dark' || (stored === 'system' && prefersDark);
            if (isDark) document.documentElement.classList.add('dark');
        })();
    </script>

   @vite(['resources/css/app.css', 'resources/js/app.js'])


    {{-- Service Worker 自动更新：检测到新版立即接管并刷新，避免用户停留在旧缓存 --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    if (reg.waiting) { reg.waiting.postMessage({ type: 'SKIP_WAITING' }); }
                    reg.addEventListener('updatefound', function() {
                        var nw = reg.installing;
                        if (!nw) return;
                        nw.addEventListener('statechange', function() {
                            if (nw.state === 'installed' && navigator.serviceWorker.controller) {
                                nw.postMessage({ type: 'SKIP_WAITING' });
                            }
                        });
                    });
                });
                var refreshed = false;
                navigator.serviceWorker.addEventListener('controllerchange', function() {
                    if (!refreshed) { refreshed = true; window.location.reload(); }
                });
            });
        }
    </script>

    @yield('head')
</head>
<body class="min-h-screen antialiased" style="background-color: var(--c-bg); color: var(--c-ink);">
    @if(auth()->check())

    {{-- Mobile top bar --}}
    <header class="lg:hidden sticky top-0 z-40 bg-brand-600 text-white shadow-sm" style="padding-top: env(safe-area-inset-top);">
        <div class="flex items-center justify-between px-4 h-14">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleDrawer()" class="p-2 -ml-2 rounded-lg hover:bg-brand-700 transition-colors" aria-label="菜单">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('workorders.index') }}" class="flex items-center gap-2 font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
<span class="text-sm">{{ \App\Helpers\SystemHelper::getSystemName() }}</span>
                </a>
            </div>
            <div class="flex items-center gap-1">
    <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg hover:bg-brand-700 transition-colors" aria-label="通知">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 0 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
                    <span id="notif-badge-mobile" class="hidden absolute top-1 right-1 min-w-4 h-4 px-1 text-[10px] font-bold leading-4 text-center text-white bg-red-500 rounded-full">{{ '' }}</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Mobile drawer + desktop sidebar --}}
    <div id="drawer-overlay" onclick="toggleDrawer()" class="hidden lg:hidden fixed inset-0 bg-black/40 z-40"></div>
    <aside id="sidebar" class="fixed top-0 left-0 z-50 lg:z-40 h-full lg:h-screen w-64 shrink-0 bg-brand-800 text-white flex flex-col transition-transform duration-200 -translate-x-full lg:translate-x-0" style="padding-top: env(safe-area-inset-top);">
        {{-- Logo --}}
        <div class="flex items-center justify-between px-5 h-14 border-b border-brand-700 shrink-0">
            <a href="{{ route('workorders.index') }}" class="flex items-center gap-2 font-semibold">
                <svg class="w-5 h-5 text-brand-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
<span>{{ \App\Helpers\SystemHelper::getSystemName() }}</span>
            </a>
            <button type="button" onclick="toggleDrawer()" class="lg:hidden p-1.5 rounded-lg hover:bg-brand-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm" id="nav-scroll">
            @if(!auth()->user()->isUser())
            <p class="px-3 pt-2 pb-1 text-xs font-medium text-brand-300 uppercase tracking-wide">工单</p>
            <a href="{{ route('workorders.index') }}" class="nav-item {{ request()->routeIs('workorders.index') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>工单列表</span>
            </a>
            @endif
            @if(!auth()->user()->isUser())
            <a href="{{ route('workorders.create') }}" class="nav-item {{ request()->routeIs('workorders.create') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span>创建工单</span>
            </a>
            @else
            <p class="px-3 pt-2 pb-1 text-xs font-medium text-brand-300 uppercase tracking-wide">报修</p>
            <a href="{{ route('workorders.report.create') }}" class="nav-item {{ request()->routeIs('workorders.report.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                <span>我要报修</span>
            </a>
            @endif

            @if(auth()->user()->canManageWorkorderTypes())
            <a href="{{ route('workorder-templates.index') }}" class="nav-item {{ request()->routeIs('workorder-templates.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8"/></svg>
                <span>工单模板</span>
            </a>
            @endif

            @if(auth()->user()->canViewReports())
            <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18 M7 14l4-4 4 4 5-5"/></svg>
                <span>统计报表</span>
            </a>
            @endif

            @if(!auth()->user()->isUser())
            <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 0 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
                <span>通知中心</span>
            </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isWorkorderManager())
            <p class="px-3 pt-4 pb-1 text-xs font-medium text-brand-300 uppercase tracking-wide">系统管理</p>

            <a href="{{ route('locations.index') }}" class="nav-item {{ request()->routeIs('locations.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                <span>地址管理</span>
            </a>

            <a href="{{ route('workorder-categories.index') }}" class="nav-item {{ request()->routeIs('workorder-categories.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M3 12h12 M3 18h6"/></svg>
                <span>工单分类</span>
            </a>

            <a href="{{ route('departments.index') }}" class="nav-item {{ request()->routeIs('departments.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18 M5 21V7l8-4v18 M19 21V11l-6-4"/></svg>
                <span>部门管理</span>
            </a>
            @endif

            @if(auth()->user()->isAdmin())
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87 M9 20H4v-2a4 4 0 0 1 3-3.87 M16 3.13a4 4 0 0 1 0 7.75 M12 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/></svg>
                <span>用户管理</span>
            </a>
                        <div class="settings-nav-group">
                <button type="button" class="nav-item w-full justify-between {{ request()->routeIs('settings.page', 'system-settings.*') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}" onclick="document.getElementById('settingsSubnav').classList.toggle('hidden')">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>设置</span>
                    </span>
                    <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="settingsSubnav" class="{{ request()->routeIs('settings.page', 'system-settings.*') ? '' : 'hidden' }} pl-7 mt-1 space-y-1">
                    <a href="{{ route('settings.page', 'system') }}" class="nav-item text-sm {{ request()->is('settings/system') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}"><span class="w-1 h-1 rounded-full bg-current"></span><span>系统设置</span></a>
                    <a href="{{ route('settings.page', 'backup') }}" class="nav-item text-sm {{ request()->is('settings/backup') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}"><span class="w-1 h-1 rounded-full bg-current"></span><span>备份&恢复</span></a>
                    <a href="{{ route('settings.page', 'messaging') }}" class="nav-item text-sm {{ request()->is('settings/messaging') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}"><span class="w-1 h-1 rounded-full bg-current"></span><span>消息设置</span></a>
                    <a href="{{ route('settings.page', 'all') }}" class="nav-item text-sm {{ request()->is('settings/all') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}"><span class="w-1 h-1 rounded-full bg-current"></span><span>详细设置</span></a>
                    <a href="{{ route('system-settings.cas') }}" class="nav-item text-sm {{ request()->routeIs('system-settings.cas') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}"><span class="w-1 h-1 rounded-full bg-current"></span><span>CAS 认证</span></a>
                    <a href="{{ route('system-settings.oidc') }}" class="nav-item text-sm {{ request()->routeIs('system-settings.oidc') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}"><span class="w-1 h-1 rounded-full bg-current"></span><span>OIDC 认证</span></a>
                    <a href="{{ route('system-settings.wechat-oauth') }}" class="nav-item text-sm {{ request()->routeIs('system-settings.wechat-oauth') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}"><span class="w-1 h-1 rounded-full bg-current"></span><span>微信登录</span></a>
                </div>
            </div>
            @endif

            {{-- User section --}}
            <div class="!mt-auto pt-4 border-t border-brand-700 space-y-1">
                <p class="px-3 py-1 text-xs text-brand-300 truncate">{{ auth()->user()->name }}</p>
                <a href="{{ route('profile') }}" class="nav-item {{ request()->routeIs('profile') ? 'nav-active' : 'text-brand-100 hover:bg-brand-700' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                    <span>个人资料</span>
                </a>
                <form method="POST" action="{{ route('logout.get') }}" class="inline-block w-full">
                    @csrf
                    <button type="submit" class="nav-item w-full text-left text-brand-100 hover:bg-brand-700">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9"/></svg>
                        <span>退出登录</span>
                    </button>
                </form>
            </div>
        </nav>
        {{-- Copyright footer --}}
        <div class="px-4 py-3 border-t border-brand-700 shrink-0">
            <p class="text-[11px] text-brand-300 leading-relaxed">
                {{ \App\Helpers\SystemHelper::getSystemCopyright() }}<br>
                <span class="text-brand-400">© {{ date("Y") }} hicool</span>
            </p>
        </div>
    </aside>

    {{-- Desktop top bar --}}
    <header class="hidden lg:flex sticky top-0 z-30 h-14 items-center justify-between px-6 lg:ml-64 lg:ml-64 bg-card border-b border-border">
        <div class="flex items-center gap-3">
            @isset($breadcrumbs)
                <nav class="flex items-center gap-2 text-sm text-ink-muted">
                    @foreach($breadcrumbs as $crumb)
                        @if(!$loop->last)
                            <a href="{{ $crumb['url'] ?? '#' }}" class="hover:text-brand-600">{{ $crumb['title'] }}</a>
                            <span class="text-ink-subtle">/</span>
                        @else
                            <span class="text-ink font-medium">{{ $crumb['title'] }}</span>
                        @endif
                    @endforeach
                </nav>
            @endisset
        </div>
        <div class="flex items-center gap-3">
    <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg hover:bg-surface-muted transition-colors" aria-label="通知">
                <svg class="w-5 h-5 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 0 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
                <span id="notif-badge-desktop" class="hidden absolute top-0.5 right-0.5 min-w-4 h-4 px-1 text-[10px] font-bold leading-4 text-center text-white bg-red-500 rounded-full"></span>
            </a>
            {{-- Theme switcher --}}
            <div class="relative">
                <button id="theme-toggle" class="p-2 rounded-lg hover:bg-surface-muted transition-colors" aria-label="主题切换" style="color: var(--c-ink-muted);">
                    <svg class="w-5 h-5 theme-icon-light" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    <svg class="w-5 h-5 theme-icon-dark hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <svg class="w-5 h-5 theme-icon-system hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4"/></svg>
                </button>
                <div id="theme-menu" class="hidden absolute right-0 mt-2 w-36 card shadow-lg py-1 z-50">
                    <button data-theme="light" class="theme-option flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-surface-muted" style="color: var(--c-ink);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                        <span>浅色</span>
                    </button>
                    <button data-theme="dark" class="theme-option flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-surface-muted" style="color: var(--c-ink);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <span>深色</span>
                    </button>
                    <button data-theme="system" class="theme-option flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-surface-muted" style="color: var(--c-ink);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4"/></svg>
                        <span>跟随系统</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <div class="lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8" style="padding-bottom: calc(5rem + env(safe-area-inset-bottom));">

    @else
    {{-- Guest layout (login/register) --}}
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
    @endif

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="flash-msg flex items-center gap-3 px-4 py-3 mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm" role="alert">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4 M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20z"/></svg>
        <span class="flex-1">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="flash-msg flex items-center gap-3 px-4 py-3 mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm" role="alert">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4 M12 16h.01 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
        <span class="flex-1">{{ session('error') }}</span>
    </div>
    @endif
    @if(session('warning'))
    <div class="flash-msg flex items-center gap-3 px-4 py-3 mb-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm" role="alert">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4 M12 17h.01 M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
        <span class="flex-1">{{ session('warning') }}</span>
    </div>
    @endif
    @if(isset($errors) && $errors->any())
    <div class="flash-msg px-4 py-3 mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @yield('content')

    @if(!auth()->check())
        </div>
    </div>
    @else

        </main>
    </div>

    {{-- Mobile bottom action bar (for workorder list quick actions) --}}
    {{-- This is controlled by JS, shown only when workorders are selected --}}
    <div id="mobile-action-bar" class="hidden lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-card border-t border-border shadow-lg" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex items-center justify-around px-4 py-2" id="mobile-action-content"></div>
    </div>

    @endif

    {{-- jQuery for pages that use it (create/edit workorder) --}}
    <script src="{{ asset('js/jquery.min.js') }}"></script>

    @yield('scripts')


    @if(!auth()->check())
    <script>
    {{-- Theme switcher (works for guest pages too) --}}
    (function() {
        var toggle = document.getElementById('theme-toggle');
        var menu = document.getElementById('theme-menu');
        if (!toggle) return;
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });
        document.addEventListener('click', function() { menu.classList.add('hidden'); });
        document.querySelectorAll('.theme-option').forEach(function(opt) {
            opt.addEventListener('click', function() {
                var theme = this.getAttribute('data-theme');
                localStorage.setItem('theme', theme);
                applyTheme(theme);
                menu.classList.add('hidden');
            });
        });
        function applyTheme(theme) {
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = theme === 'dark' || (theme === 'system' && prefersDark);
            document.documentElement.classList.toggle('dark', isDark);
            ['light','dark','system'].forEach(function(t) {
                var icon = document.querySelector('.theme-icon-' + t);
                if (icon) icon.classList.toggle('hidden', t !== theme);
            });
        }
        applyTheme(localStorage.getItem('theme') || 'system');
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            if ((localStorage.getItem('theme') || 'system') === 'system') applyTheme('system');
        });
    })();
    </script>
    @endif

    @if(auth()->check())
    <script>
        function toggleDrawer() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('drawer-overlay');
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('drawer-open');
            } else {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('drawer-open');
            }
        }

        // Auto-close drawer when clicking a nav link on mobile
        document.querySelectorAll('#sidebar .nav-item').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024 && this.tagName !== 'BUTTON') {
                    setTimeout(toggleDrawer, 50);
                }
            });
        });

        // Auto-hide flash messages
        setTimeout(function() {
            document.querySelectorAll('.flash-msg').forEach(function(el) {
                el.style.transition = 'opacity 0.3s';
                el.style.opacity = '0';
                setTimeout(function() { el.style.display = 'none'; }, 300);
            });
        }, 5000);

        // Notification badge
        function loadNotifBadge() {
            fetch('{{ route("notifications.unread-count") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); })
              .then(function(data) {
                  var count = data.count || 0;
                  ['notif-badge-mobile', 'notif-badge-desktop'].forEach(function(id) {
                      var el = document.getElementById(id);
                      if (el) {
                          if (count > 0) {
                              el.textContent = count > 99 ? '99+' : count;
                              el.classList.remove('hidden');
                          } else {
                              el.classList.add('hidden');
                          }
                      }
                  });
              }).catch(function() {});
        }
        loadNotifBadge();
        setInterval(loadNotifBadge, 30000);

        // Theme switcher
        var themeToggle = document.getElementById('theme-toggle');
        var themeMenu = document.getElementById('theme-menu');
        if (themeToggle) {
            themeToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                themeMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', function() { if (themeMenu) themeMenu.classList.add('hidden'); });
            document.querySelectorAll('.theme-option').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    var theme = this.getAttribute('data-theme');
                    localStorage.setItem('theme', theme);
                    applyTheme(theme);
                    themeMenu.classList.add('hidden');
                });
            });
            function applyTheme(theme) {
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var isDark = theme === 'dark' || (theme === 'system' && prefersDark);
                document.documentElement.classList.toggle('dark', isDark);
                ['light','dark','system'].forEach(function(t) {
                    var icon = document.querySelector('.theme-icon-' + t);
                    if (icon) icon.classList.toggle('hidden', t !== theme);
                });
                // Update theme-color meta
                var meta = document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', isDark ? '#1e293b' : '#2563eb');
            }
            applyTheme(localStorage.getItem('theme') || 'system');
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
                if ((localStorage.getItem('theme') || 'system') === 'system') applyTheme('system');
            });
        }

        // View mode preference (table/card on workorder list)
        // Read on page load, stored as localStorage 'viewMode'
        window.getViewMode = function() {
            return localStorage.getItem('viewMode') || 'auto';
        };
        window.setViewMode = function(mode) {
            localStorage.setItem('viewMode', mode);
        };
    </script>
    <style>
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: background-color 0.15s;
        }
        .nav-active {
            background-color: rgba(255,255,255,0.12);
            color: white;
            font-weight: 500;
        }
    </style>
    @endif
</body>
</html>
