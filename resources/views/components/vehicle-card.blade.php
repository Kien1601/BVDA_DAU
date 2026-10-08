{{-- Thẻ xe phía khách: nghiêng theo chuột (data-tilt), không bao giờ hiện IMEI hay vị trí GPS. --}}
@props(['vehicle'])

@php
    $rented = $vehicle->status === \App\Enums\VehicleStatus::Rented;
@endphp

<a href="{{ route('vehicles.show', $vehicle) }}" data-tilt="7"
   {{ $attributes->merge(['class' => 'group relative block overflow-hidden rounded-xl border border-line bg-ink-2 transition-colors duration-500 hover:border-bone/30']) }}>

    <div class="relative aspect-[4/3] overflow-hidden bg-[radial-gradient(70%_60%_at_50%_80%,rgba(224,35,28,.14),transparent_70%),linear-gradient(#0d141a,#0a0e12)]">
        @if ($vehicle->imageUrl())
            <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-700 ease-soft group-hover:scale-105">
        @else
            <div class="flex h-full items-center justify-center text-4xl font-light uppercase tracking-[.2em] text-bone/10">
                {{ $vehicle->type }}
            </div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-ink-2 via-transparent to-transparent"></div>

        <span class="absolute left-4 top-4 rounded-full border border-line bg-ink/70 px-3 py-1 text-[10px] font-medium uppercase tracking-label text-bone-dim backdrop-blur">
            {{ $vehicle->type }}
        </span>

        @if ($rented)
            <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-ink/70 px-3 py-1 text-[10px] font-medium uppercase tracking-label text-ember backdrop-blur">
                <span class="h-1.5 w-1.5 rounded-full bg-ember"></span>Đang có khách
            </span>
        @endif
    </div>

    <div class="relative p-5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h3 class="truncate text-lg font-normal text-bone">{{ $vehicle->name }}</h3>
                <p class="mt-1 truncate text-xs text-muted">{{ $vehicle->brand ?: 'Xe máy' }} · {{ $vehicle->store->name }}</p>
            </div>
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-line transition-colors duration-500 group-hover:border-bone group-hover:bg-bone">
                <svg viewBox="0 0 14 14" fill="none" class="h-3 w-3 transition-transform duration-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5" aria-hidden="true">
                    <path d="M3 11 11 3M5 3h6v6" stroke="currentColor" stroke-width="1.3" class="text-bone group-hover:text-ink"/>
                </svg>
            </span>
        </div>

        <div class="mt-5 flex items-end justify-between border-t border-line-soft pt-4">
            <div>
                <div class="text-[10px] font-medium uppercase tracking-label text-muted">Giá ngày</div>
                <div class="mt-1 text-xl font-light tabular-nums text-bone">{{ \App\Support\Money::vnd($vehicle->price_per_day) }}</div>
            </div>
            <div class="text-right text-xs tabular-nums text-muted">
                {{ \App\Support\Money::vnd($vehicle->price_per_hour) }} / giờ
            </div>
        </div>
    </div>

    <span class="tilt-glare"></span>
</a>