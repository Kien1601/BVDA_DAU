{{-- Phân trang tông tối cho trang khách. Gọi: $paginator->links('components.pagination') --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Phân trang"
         class="flex items-center justify-between gap-4 text-[11px] font-medium uppercase tracking-label">
        @if ($paginator->onFirstPage())
            <span class="text-muted/50">← Trước</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="text-bone-dim transition-colors hover:text-bone">← Trước</a>
        @endif

        <div class="flex items-center gap-1">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid h-8 min-w-[2rem] place-items-center rounded-full bg-bone px-2 tabular-nums text-ink">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="grid h-8 min-w-[2rem] place-items-center rounded-full px-2 tabular-nums text-bone-dim transition-colors hover:text-bone">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="text-bone-dim transition-colors hover:text-bone">Sau →</a>
        @else
            <span class="text-muted/50">Sau →</span>
        @endif
    </nav>
@endif