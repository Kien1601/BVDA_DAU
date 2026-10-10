{{--
    Nút dùng chung.
    variant (màu theo vai trò nên tự đúng ở cả chế độ sáng và tối):
      primary       nền đặc (bg-solid), rê chuột thì đỏ son trượt lên
      secondary     viền mảnh
      link          chữ
      link-danger   chữ đỏ, cho thao tác xóa
      light         giống primary, giữ tên cho các trang đang dùng
      outline-light giống secondary, giữ tên cho các trang đang dùng
    Có href thì thành thẻ <a>, không có thì là <button>.
--}}
@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $base = 'group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-full '
          . 'text-[11px] font-medium uppercase tracking-label transition-colors duration-300 ease-soft '
          . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-vermilion/50 '
          . 'disabled:opacity-50 disabled:pointer-events-none';

    // chữ trắng khi rê chuột: lúc đó nền là đỏ son, cố định ở cả hai chế độ
    $solid = 'px-5 py-2.5 bg-solid text-on-solid hover:text-white';
    $outline = 'px-5 py-2.5 border border-edge-strong text-fg hover:border-fg/40';

    $variants = [
        'primary'       => $solid,
        'secondary'     => $outline,
        'link'          => 'text-fg-soft hover:text-fg',
        'link-danger'   => 'text-vermilion hover:text-ember',
        'light'         => $solid,
        'outline-light' => $outline,
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);

    // các kiểu có nền đỏ son trượt lên khi rê chuột
    $hasSlide = in_array($variant, ['primary', 'light'], true);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
@endif
        @if ($hasSlide)
            <span class="absolute inset-0 translate-y-full bg-vermilion transition-transform duration-500 ease-soft group-hover:translate-y-0"
                  aria-hidden="true"></span>
        @endif
        <span class="relative">{{ $slot }}</span>
@if ($href)
    </a>
@else
    </button>
@endif