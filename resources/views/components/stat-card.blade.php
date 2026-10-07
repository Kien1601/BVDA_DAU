{{-- Thẻ số liệu: nhãn nhỏ viết hoa, số lớn nét mảnh, số thứ tự ở góc. --}}
@props(['label', 'value', 'index' => null, 'accent' => false])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-ink/10 bg-white p-5']) }}>
    <div class="flex items-baseline justify-between gap-3">
        <span class="text-[10px] font-medium uppercase tracking-label text-muted">{{ $label }}</span>
        @if ($index)
            <span class="text-[10px] tabular-nums tracking-label text-muted/70">{{ $index }}</span>
        @endif
    </div>

    <div class="mt-3 text-3xl font-light tabular-nums tracking-tight {{ $accent ? 'text-vermilion' : 'text-ink' }}">
        {{ $value }}
    </div>

    @isset($foot)
        <div class="mt-2 text-xs text-muted">{{ $foot }}</div>
    @endisset
</div>