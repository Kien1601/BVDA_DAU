{{--
    Nút dùng chung.
    variant: primary (nền tối, rê chuột thì đỏ son trượt lên) | secondary (viền mảnh)
             | link (chữ) | link-danger (chữ đỏ, cho thao tác xóa)
    Có href thì thành thẻ <a>, không có thì là <button>.
--}}
@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $base = 'group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full '
          . 'text-[11px] font-medium uppercase tracking-label transition-colors duration-300 ease-soft '
          . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-vermilion/50 disabled:opacity-50';

    $variants = [
        'primary'     => 'px-5 py-2.5 bg-ink text-bone',
        'secondary'   => 'px-5 py-2.5 border border-ink/15 text-ink hover:border-ink/40',
        'link'        => 'text-ink/70 hover:text-ink',
        'link-danger' => 'text-vermilion hover:text-ember',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
@endif
        @if ($variant === 'primary')
            <span class="absolute inset-0 translate-y-full bg-vermilion transition-transform duration-500 ease-soft group-hover:translate-y-0"
                  aria-hidden="true"></span>
        @endif
        <span class="relative">{{ $slot }}</span>
@if ($href)
    </a>
@else
    </button>
@endif