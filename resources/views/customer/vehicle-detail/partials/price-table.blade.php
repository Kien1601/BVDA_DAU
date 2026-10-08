<div data-reveal style="--reveal-delay: 200ms" class="mt-8 grid grid-cols-3 divide-x divide-edge-soft rounded-xl border border-edge">
    @foreach ([
        'Theo giờ' => $vehicle->price_per_hour,
        'Theo ngày' => $vehicle->price_per_day,
        'Theo tuần' => $vehicle->price_per_week,
    ] as $label => $price)
        <div class="p-4">
            <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">{{ $label }}</div>
            <div class="mt-2 text-lg font-light tabular-nums {{ $label === 'Theo ngày' ? 'text-ember' : 'text-fg' }}">
                {{ \App\Support\Money::vnd($price) }}
            </div>
        </div>
    @endforeach
</div>