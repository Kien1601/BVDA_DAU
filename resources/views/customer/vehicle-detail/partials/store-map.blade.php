<section class="border-y border-line-soft bg-ink-2/60">
    <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div data-reveal class="mb-10 flex items-baseline gap-4">
            <span class="text-[10px] font-medium uppercase tracking-label text-muted">
                <b class="font-medium text-vermilion">01</b> — Nhận xe tại
            </span>
            <span class="h-px flex-1 bg-line-soft"></span>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_1.5fr]">
            <div data-reveal>
                <h2 class="text-2xl font-normal text-bone">{{ $vehicle->store->name }}</h2>
                <p class="mt-3 text-bone-dim">{{ $vehicle->store->address }}</p>
                @if ($vehicle->store->phone)
                    <p class="mt-2 tabular-nums text-bone-dim">{{ $vehicle->store->phone }}</p>
                @endif

                <div class="mt-8 flex flex-wrap gap-4">
                    <x-button variant="outline-light" :href="route('vehicles.index', ['store' => $vehicle->store_id])">Xe tại cửa hàng này</x-button>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $vehicle->store->latitude }},{{ $vehicle->store->longitude }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center text-[11px] font-medium uppercase tracking-label text-bone-dim transition-colors hover:text-bone">
                        Chỉ đường ↗
                    </a>
                </div>
            </div>

            <div id="store-map" data-reveal style="--reveal-delay: 120ms"
                 class="z-0 h-80 overflow-hidden rounded-xl border border-line"
                 data-stores="{{ json_encode($storeMap) }}"></div>
        </div>
    </div>
</section>