@props(['title' => null])
<div class="card overflow-hidden">
    @if($title)
    <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3 flex-wrap">
        <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
        @isset($actions)<div class="flex items-center gap-2 flex-wrap">{{ $actions }}</div>@endisset
    </div>
    @endif
    {{ $slot }}
</div>
