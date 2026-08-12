@php
    /**
     * 地址管理模块统一顶部菜单 + 状态条。
     *
     * 用法：
     * @include('locations._topbar', [
     *     'active' => 'tree'|'campuses'|'levels'|'import'|'base',
     *     'title'  => '页面标题',
     *     'subtitle' => '可选副标题',
     *     'actions' => '<a href="..." class="btn btn-primary">...</a>',  // 可选，raw HTML
     * ])
     */
    $active = $active ?? 'tree';
    $title = $title ?? '地址管理';
    $subtitle = $subtitle ?? null;
    $actions = $actions ?? null;

    $tabs = [
        'tree'     => ['route' => 'locations.index',         'label' => '地址树'],
        'campuses' => ['route' => 'locations.campuses',      'label' => '区域管理'],
        'levels'   => ['route' => 'location-levels.index',   'label' => '层级定义'],
        'import'   => ['route' => 'locations.import',        'label' => '批量导入'],
        'base'     => ['route' => 'locations.base-address',  'label' => '基础地址'],
    ];

    $baseInitialized = \App\Models\Location::isBaseAddressInitialized();
    $prefixLabel = \App\Models\Location::getPrefixLabel();
    $baseAddress = null;
    if ($baseInitialized) {
        $root = \App\Models\Location::getDailyRoot();
        $baseAddress = $root?->full_address_delimited;
    }
@endphp

<div class="mb-6">
    {{-- 标题 + 右侧操作 --}}
    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold text-ink">{{ $title }}</h1>
            @isset($subtitle)
            <p class="text-sm text-ink-muted mt-0.5">{{ $subtitle }}</p>
            @endisset
        </div>
        @if($actions)
        <div class="flex items-center gap-2 flex-wrap">
            {!! $actions !!}
        </div>
        @endif
    </div>

    {{-- 统一 tab 切换 --}}
    <div class="flex gap-1 mb-3 border-b border-border overflow-x-auto" role="tablist">
        @foreach($tabs as $key => $tab)
            @php($route = route($tab['route']))
            <a href="{{ $route }}"
               role="tab"
               aria-selected="{{ $active === $key ? 'true' : 'false' }}"
               class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors {{ $active === $key ? 'border-brand-600 text-brand-600' : 'border-transparent text-ink-muted hover:text-ink hover:bg-surface-muted' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    {{-- 统一状态条 --}}
    <div class="flex items-center gap-2 flex-wrap text-xs">
        @if($baseInitialized)
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                基础地址已初始化
            </span>
            @if($baseAddress)
            <span class="text-ink-muted">
                <span class="text-ink-subtle">路径：</span>
                <span class="font-medium text-ink">{{ $baseAddress }}</span>
            </span>
            @endif
        @else
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                基础地址未初始化
            </span>
            <a href="{{ route('locations.base-address') }}" class="text-brand-600 hover:underline">去初始化 →</a>
        @endif

        @if($prefixLabel)
        <span class="hidden sm:inline text-ink-subtle">·</span>
        <span class="text-ink-muted">
            <span class="text-ink-subtle">地址前缀：</span>
            <span class="font-medium text-ink">{{ $prefixLabel }}</span>
            <a href="{{ route('settings.page', 'system') }}" class="ml-1 text-brand-600 hover:underline" title="在系统设置中修改">[改]</a>
        </span>
        @endif
    </div>
</div>
