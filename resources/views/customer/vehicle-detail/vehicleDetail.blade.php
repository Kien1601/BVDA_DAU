@extends('layouts.customer')

@section('title', $vehicle->name)
@section('description', $vehicle->name . ' cho thuê tại ' . $vehicle->store->name . ', giá từ ' . \App\Support\Money::vnd($vehicle->price_per_day) . ' mỗi ngày.')

@section('content')
    @php $rented = $vehicle->status === \App\Enums\VehicleStatus::Rented; @endphp

    <section class="mx-auto max-w-7xl px-5 pb-20 pt-28 lg:px-8">
        <nav class="text-[10px] font-medium uppercase tracking-label text-fg-muted" aria-label="Đường dẫn">
            <a href="{{ route('vehicles.index') }}" class="transition-colors hover:text-fg">Xe cho thuê</a>
            <span class="mx-2">/</span>
            <a href="{{ route('vehicles.index', ['type' => $vehicle->type]) }}" class="transition-colors hover:text-fg">{{ $vehicle->type }}</a>
            <span class="mx-2">/</span>
            <span class="text-fg-soft">{{ $vehicle->name }}</span>
        </nav>

        <div class="mt-8 grid gap-10 lg:grid-cols-[1.25fr_1fr] lg:gap-14">
            @include('customer.vehicle-detail.partials.vehicle-gallery')

            <div>
                <div data-reveal class="text-[10px] font-medium uppercase tracking-label text-fg-soft">
                    {{ $vehicle->type }}{{ $vehicle->brand ? ' · ' . $vehicle->brand : '' }}
                </div>
                <h1 data-reveal style="--reveal-delay: 80ms" class="mt-3 text-4xl font-normal uppercase leading-tight tracking-tight sm:text-5xl">
                    {{ $vehicle->name }}
                </h1>
                <div data-reveal style="--reveal-delay: 140ms" class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                    <span class="tabular-nums text-fg-soft">{{ $vehicle->license_plate }}</span>
                    @if ($rented)
                        <span class="inline-flex items-center gap-1.5 text-ember">
                            <span class="h-1.5 w-1.5 rounded-full bg-ember"></span>Đang có khách thuê
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-emerald-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Sẵn sàng
                        </span>
                    @endif
                </div>

                @include('customer.vehicle-detail.partials.price-table')

                <dl data-reveal style="--reveal-delay: 260ms" class="mt-8 divide-y divide-edge-soft border-y border-edge-soft text-sm">
                    @foreach ([
                        'Loại xe' => $vehicle->type,
                        'Hãng' => $vehicle->brand ?: '—',
                        'Nhận xe tại' => $vehicle->store->name,
                    ] as $term => $detail)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-fg-muted">{{ $term }}</dt>
                            <dd class="text-right text-fg">{{ $detail }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div data-reveal style="--reveal-delay: 320ms" class="mt-8 flex flex-wrap items-center gap-4">
                    @guest
                        <x-button variant="light" :href="route('login')">Đăng nhập để đặt thuê</x-button>
                    @else
                        {{-- B2: đổi thành nút đặt thuê khi có chức năng đặt thuê --}}
                        <x-button variant="light" type="button" disabled>Đặt thuê (sắp mở)</x-button>
                    @endguest
                    <x-button variant="outline-light" :href="route('vehicles.index', ['type' => $vehicle->type])">Xe cùng loại</x-button>
                </div>

                <p class="mt-4 text-xs leading-relaxed text-fg-muted">
                    @if ($rented)
                        Xe đang có khách thuê nhưng vẫn có thể đặt cho các ngày khác.
                    @endif
                    Giá có thể thay đổi theo thời điểm; đơn đã đặt giữ nguyên giá.
                </p>
            </div>
        </div>
    </section>

    @include('customer.vehicle-detail.partials.store-map')

    @if ($related->isNotEmpty())
        <section class="mx-auto max-w-7xl px-5 py-24 lg:px-8">
            <div data-reveal class="mb-12 flex items-baseline gap-4">
                <span class="text-[10px] font-medium uppercase tracking-label text-fg-muted">
                    <b class="font-medium text-vermilion">02</b> — Xe cùng loại
                </span>
                <span class="h-px flex-1 bg-edge-soft"></span>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $i => $item)
                    <div data-reveal style="--reveal-delay: {{ $i * 90 }}ms">
                        <x-vehicle-card :vehicle="$item" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    @vite('resources/views/customer/vehicle-detail/vehicleDetail.page.js')
@endpush