<div data-reveal class="relative aspect-[4/3] overflow-hidden rounded-2xl border border-edge bg-card lg:aspect-auto lg:min-h-[30rem]">
    {{-- lớp 1: lưới mảnh, trôi chậm --}}
    <div data-parallax="0.12" class="absolute -inset-16 opacity-40
         bg-[linear-gradient(rgb(var(--t-fg)/.06)_1px,transparent_1px),linear-gradient(90deg,rgb(var(--t-fg)/.06)_1px,transparent_1px)]
         bg-[size:48px_48px]"></div>

    {{-- lớp 2: quầng sáng đỏ son --}}
    <div data-parallax="0.22" class="absolute inset-x-0 -bottom-24 h-3/4 bg-[radial-gradient(50%_60%_at_50%_70%,rgba(224,35,28,.28),transparent_70%)]"></div>

    {{-- lớp 3: ảnh xe, trôi ngược nhẹ --}}
    <div data-parallax="-0.05" class="absolute inset-0 scale-110">
        @if ($vehicle->imageUrl())
            <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full items-center justify-center text-6xl font-light uppercase tracking-[.2em] text-fg/10">
                {{ $vehicle->type }}
            </div>
        @endif
    </div>

    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-card/80 via-transparent to-transparent"></div>
</div>