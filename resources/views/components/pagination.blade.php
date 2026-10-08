{{-- Phân trang trang khách (màu theo vai trò). Gọi: $paginator->links('components.pagination') --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang"
         class="flex items-center justify-between gap-4 text-[11px] font-medium uppercase tracking-label">
        @if ($paginator->onFirstPage())
            <span class="text-fg-muted/50">← Trước</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="text-fg-soft transition-colors hover:text-fg">← Trước</a>
        @endif

        <div class="flex items-center gap-1">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-fg-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid h-8 min-w-[2rem] place-items-center rounded-full bg-solid px-2 tabular-nums text-on-solid">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="grid h-8 min-w-[2rem] place-items-center rounded-full px-2 tabular-nums text-fg-soft transition-colors hover:text-fg">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="text-fg-soft transition-colors hover:text-fg">Sau →</a>
        @else
            <span class="text-fg-muted/50">Sau →</span>
        @endif
    </nav>
@endif