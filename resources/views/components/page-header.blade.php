{{-- Tiêu đề trang: nhãn nhỏ phía trên, tiêu đề, các nút thao tác bên phải (slot "actions"). --}}
@props(['title', 'eyebrow' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        @if ($eyebrow)
            <div class="flex items-center gap-2 text-[10px] font-medium uppercase tracking-label text-muted">
                <span class="h-1 w-1 rounded-full bg-vermilion"></span>{{ $eyebrow }}
            </div>
        @endif
        <h1 class="mt-1.5 text-2xl font-normal text-ink">{{ $title }}</h1>
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endisset
</div>