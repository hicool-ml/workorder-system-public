@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-1.5 mt-6" aria-label="分页导航">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-disabled" aria-hidden="true">&laquo;</span>
            @else
                <a class="pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="上一页">&laquo;</a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="pagination-dots">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-btn pagination-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a class="pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="下一页">&raquo;</a>
            @else
                <span class="pagination-btn pagination-disabled" aria-hidden="true">&raquo;</span>
            @endif
    </nav>
@endif
