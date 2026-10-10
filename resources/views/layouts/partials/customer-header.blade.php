@php
    $links = [
        ['route' => 'home',           'match' => 'home',       'label' => 'Trang chủ'],
        ['route' => 'vehicles.index', 'match' => 'vehicles.*', 'label' => 'Xe cho thuê'],
        ['route' => 'stores.index',   'match' => 'stores.*',   'label' => 'Cửa hàng'],
    ];
@endphp

{{-- Trang chủ: khi đầu trang còn trong suốt nằm trên màn hình mở đầu (luôn tối) thì header dùng màu tối
     để chữ đọc được ở cả chế độ sáng; cuộn xuống hoặc mở menu thì theo chế độ chung. --}}
@php $overHero = request()->routeIs('home'); @endphp
<header x-data="{ open: false, stuck: false, overHero: @js($overHero) }"
        x-init="stuck = window.scrollY > 40"
        @scroll.window.passive="stuck = window.scrollY > 40"
        :class="stuck || open ? 'bg-page/80 backdrop-blur-md border-edge-soft' : 'border-transparent'"
        @if ($overHero) data-theme="dark" @endif
        :data-theme="overHero && !stuck && !open ? 'dark' : null"
        class="fixed inset-x-0 top-0 z-50 border-b transition-colors duration-500">
    <div class="mx-auto flex h-20 max-w-7xl items-center gap-8 px-5 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="h-2 w-2 rounded-full bg-vermilion shadow-[0_0_12px_#e0231c]"></span>
            <span class="leading-none">
                <span class="block text-xs font-medium tracking-[.26em]">RENTAL GPS</span>
                <span class="mt-1 block text-[8px] tracking-[.34em] text-fg-muted">THUÊ XE · ĐỊNH VỊ</span>
            </span>
        </a>

        <nav class="ms-auto hidden items-center gap-8 md:flex" aria-label="Điều hướng chính">
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['match']); @endphp
                <a href="{{ route($link['route']) }}" @if ($active) aria-current="page" @endif
                   class="relative py-1 text-[11px] font-medium uppercase tracking-label transition-colors
                          {{ $active ? 'text-fg' : 'text-fg-soft hover:text-fg' }}">
                    {{ $link['label'] }}
                    @if ($active)
                        <span class="absolute -bottom-1 left-0 h-px w-full bg-vermilion"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-5 md:flex">
            <x-theme-toggle />
            @include('layouts.partials.customer-auth')
        </div>

        <div class="ms-auto flex items-center gap-2 md:hidden">
            <x-theme-toggle />

            <button type="button" class="grid h-10 w-10 place-items-center"
                    @click="open = !open" :aria-expanded="open" aria-label="Mở menu">
                <span class="relative block h-3 w-6">
                    <span class="absolute right-0 top-0 h-px bg-solid transition-all duration-300" :class="open ? 'top-1.5 w-6 rotate-45' : 'w-6'"></span>
                    <span class="absolute right-0 bottom-0 h-px bg-solid transition-all duration-300" :class="open ? 'bottom-1.5 w-6 -rotate-45' : 'w-4'"></span>
                </span>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak x-transition.opacity class="border-t border-edge-soft px-5 pb-8 pt-4 md:hidden">
        <nav class="flex flex-col" aria-label="Điều hướng chính">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="border-b border-edge-soft py-4 text-lg {{ request()->routeIs($link['match']) ? 'text-fg' : 'text-fg-soft' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="mt-6 flex flex-wrap items-center gap-5">
            @include('layouts.partials.customer-auth')
        </div>
    </div>
</header>