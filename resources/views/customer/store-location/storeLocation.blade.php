@extends('layouts.customer')

@section('title', 'Cửa hàng')

@section('content')
    <section class="mx-auto max-w-7xl px-5 pb-24 pt-32 lg:px-8">
        <div data-reveal class="flex items-center gap-2.5 text-[10px] font-medium uppercase tracking-label text-bone-dim">
            <span class="h-1.5 w-1.5 rounded-full bg-vermilion"></span>Nhận và trả xe
        </div>
        <h1 data-reveal style="--reveal-delay: 80ms" class="mt-4 text-4xl font-normal uppercase tracking-tight sm:text-5xl">Cửa hàng</h1>
        <p data-reveal style="--reveal-delay: 160ms" class="mt-4 max-w-xl text-bone-dim">
            Chọn cửa hàng gần bạn để xem xe sẵn sàng. Bấm vào một cửa hàng để xem vị trí trên bản đồ.
        </p>

        <div class="mt-10 grid gap-6 lg:grid-cols-[22rem_1fr]">
            <ul class="space-y-3">
                @forelse ($stores as $i => $store)
                    <li data-reveal style="--reveal-delay: {{ $i * 70 }}ms" class="rounded-xl border border-line bg-ink-2 transition-colors duration-500 hover:border-bone/30">
                        <button type="button" data-store-index="{{ $i }}" class="block w-full p-5 text-left">
                            <div class="text-[10px] font-medium uppercase tracking-label text-muted">{{ $store->vehicles_count }} xe sẵn sàng</div>
                            <div class="mt-2 text-base text-bone">{{ $store->name }}</div>
                            <div class="mt-1 text-sm text-bone-dim">{{ $store->address }}</div>
                            @if ($store->phone)
                                <div class="mt-1 text-sm tabular-nums text-muted">{{ $store->phone }}</div>
                            @endif
                        </button>
                        <a href="{{ route('vehicles.index', ['store' => $store->id]) }}"
                           class="block border-t border-line-soft px-5 py-3 text-[11px] font-medium uppercase tracking-label text-bone-dim transition-colors hover:text-ember">
                            Xem xe tại đây →
                        </a>
                    </li>
                @empty
                    <li class="text-bone-dim">Chưa có cửa hàng nào.</li>
                @endforelse
            </ul>

            <div id="store-map" data-reveal style="--reveal-delay: 120ms"
                 class="z-0 h-[32rem] overflow-hidden rounded-xl border border-line lg:sticky lg:top-28"
                 data-stores="{{ json_encode($mapData) }}"></div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/views/customer/store-location/storeLocation.page.js')
@endpush