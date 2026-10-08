@extends('layouts.customer')

@section('title', 'Thuê xe máy, theo dõi bằng GPS')

@section('content')
    {{-- ============================================ màn hình mở đầu (vùng luôn tối) --}}
    <section id="hero" data-theme="dark" class="relative h-[100svh] min-h-[640px] overflow-hidden">
        <div id="city-scene" class="scene-host absolute inset-0" data-quality="high" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-page/70 via-page/10 to-page"></div>

        <div class="relative z-10 mx-auto flex h-full max-w-7xl flex-col justify-end px-5 pb-14 lg:px-8">
            <div data-reveal class="flex items-center gap-2.5 text-[10px] font-medium uppercase tracking-label text-fg-soft">
                <span class="h-1.5 w-1.5 rounded-full bg-vermilion shadow-[0_0_10px_#e0231c]"></span>
                Thuê xe máy tại TP.HCM
            </div>

            <h1 data-reveal style="--reveal-delay: 100ms"
                class="mt-5 max-w-3xl text-4xl font-normal uppercase leading-[1.05] tracking-tight [text-shadow:0_2px_34px_rgba(3,6,8,.7)] sm:text-5xl lg:text-6xl">
                Thuê xe trong vài phút.<br>Biết xe ở đâu từng giây.
            </h1>

            <p data-reveal style="--reveal-delay: 200ms" class="mt-6 max-w-md leading-relaxed text-fg-soft">
                Chọn xe, đặt lịch, nhận xe tại cửa hàng gần bạn. Đội xe được gắn thiết bị định vị,
                mọi hành trình đều được theo dõi an toàn.
            </p>

            <div data-reveal style="--reveal-delay: 300ms" class="mt-8 flex flex-wrap items-center gap-4">
                <x-button variant="light" :href="route('vehicles.index')">Xem xe cho thuê</x-button>
                <x-button variant="outline-light" :href="route('stores.index')">Tìm cửa hàng</x-button>
            </div>

            <div class="mt-14 grid grid-cols-2 gap-6 border-t border-edge-soft pt-6 sm:grid-cols-4">
                @foreach ([
                    ['01', $stats['vehicles'], 'Xe sẵn sàng'],
                    ['02', $stats['stores'], 'Cửa hàng'],
                    ['03', $stats['types'], 'Loại xe'],
                    ['04', $stats['from'] ? \App\Support\Money::vnd($stats['from']) : '—', 'Giá ngày từ'],
                ] as $i => [$no, $value, $label])
                    <div data-reveal style="--reveal-delay: {{ 380 + $i * 90 }}ms">
                        <div class="text-[10px] tabular-nums tracking-label text-fg-muted">{{ $no }}</div>
                        <div class="mt-1 text-2xl font-light tabular-nums text-fg sm:text-3xl">{{ $value }}</div>
                        <div class="mt-1 text-[10px] font-medium uppercase tracking-label text-fg-soft">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================ 01 · xe nổi bật --}}
    <section class="mx-auto max-w-7xl px-5 py-24 lg:px-8">
        <div data-reveal class="mb-12 flex items-baseline gap-4">
            <span class="text-[10px] font-medium uppercase tracking-label text-fg-muted">
                <b class="font-medium text-vermilion">01</b> — Xe nổi bật
            </span>
            <span class="h-px flex-1 bg-edge-soft"></span>
            <a href="{{ route('vehicles.index') }}" class="text-[11px] font-medium uppercase tracking-label text-fg-soft transition-colors hover:text-fg">
                Xem tất cả →
            </a>
        </div>

        @if ($featured->isEmpty())
            <p class="text-fg-soft">Chưa có xe nào sẵn sàng.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $i => $vehicle)
                    <div data-reveal style="--reveal-delay: {{ ($i % 3) * 90 }}ms">
                        <x-vehicle-card :vehicle="$vehicle" />
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ============================================ 02 · cách thuê xe --}}
    <section class="border-y border-edge-soft bg-card/60">
        <div class="mx-auto max-w-7xl px-5 py-24 lg:px-8">
            <div data-reveal class="mb-12 flex items-baseline gap-4">
                <span class="text-[10px] font-medium uppercase tracking-label text-fg-muted">
                    <b class="font-medium text-vermilion">02</b> — Cách thuê xe
                </span>
                <span class="h-px flex-1 bg-edge-soft"></span>
            </div>

            <div class="grid gap-10 md:grid-cols-3">
                @foreach ([
                    ['01', 'Chọn xe và thời gian', 'Lọc theo loại xe, cửa hàng và giá. Hệ thống kiểm tra ngay xe còn trống trong khoảng thời gian bạn chọn.'],
                    ['02', 'Thanh toán, nhận xe', 'Đặt cọc online hoặc trả tại cửa hàng. Mang giấy tờ đến nhận xe đúng giờ hẹn.'],
                    ['03', 'Đi xe an tâm', 'Xe được theo dõi bằng GPS trong suốt thời gian thuê. Trả xe tại cửa hàng, tính tiền minh bạch.'],
                ] as $i => [$no, $title, $text])
                    <div data-reveal style="--reveal-delay: {{ $i * 110 }}ms">
                        <div class="text-4xl font-light tabular-nums text-ember">{{ $no }}</div>
                        <h3 class="mt-4 text-lg font-normal uppercase tracking-wide text-fg">{{ $title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-fg-soft">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================ 03 · cửa hàng --}}
    <section class="mx-auto max-w-7xl px-5 py-24 lg:px-8">
        <div data-reveal class="mb-12 flex items-baseline gap-4">
            <span class="text-[10px] font-medium uppercase tracking-label text-fg-muted">
                <b class="font-medium text-vermilion">03</b> — Cửa hàng
            </span>
            <span class="h-px flex-1 bg-edge-soft"></span>
            <a href="{{ route('stores.index') }}" class="text-[11px] font-medium uppercase tracking-label text-fg-soft transition-colors hover:text-fg">
                Xem bản đồ →
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stores as $i => $store)
                <a href="{{ route('vehicles.index', ['store' => $store->id]) }}" data-reveal style="--reveal-delay: {{ $i * 90 }}ms"
                   class="group rounded-xl border border-edge p-5 transition-colors duration-500 hover:border-fg/30">
                    <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">{{ $store->vehicles_count }} xe</div>
                    <h3 class="mt-3 text-base font-normal text-fg">{{ $store->name }}</h3>
                    <p class="mt-2 text-sm text-fg-soft">{{ $store->address }}</p>
                    <div class="mt-5 text-[11px] font-medium uppercase tracking-label text-fg-soft transition-colors group-hover:text-ember">
                        Xem xe tại đây →
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============================================ kết --}}
    <section class="border-t border-edge-soft">
        <div class="mx-auto flex max-w-7xl flex-col items-center px-5 py-28 text-center lg:px-8">
            <h2 data-reveal class="text-4xl font-normal uppercase leading-none tracking-tight sm:text-6xl">Sẵn sàng lên đường?</h2>
            <p data-reveal style="--reveal-delay: 120ms" class="mt-6 max-w-md text-fg-soft">
                Chọn một chiếc xe hợp với hành trình của bạn. Đặt trước, nhận xe đúng giờ.
            </p>
            <div data-reveal style="--reveal-delay: 220ms" class="mt-10">
                <x-button variant="light" :href="route('vehicles.index')">Chọn xe ngay</x-button>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/views/customer/home/home.page.js')
@endpush