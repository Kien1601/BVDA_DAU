@extends('layouts.admin')

@section('title', 'Giá thuê')

@section('content')
    <x-page-header eyebrow="Chính sách giá" title="Giá thuê">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('admin.promotions.index')">Mã khuyến mãi</x-button>
        </x-slot:actions>
    </x-page-header>

    <p class="-mt-3 mb-6 text-sm text-fg-muted">
        Giá mới chỉ áp dụng cho các đơn tạo sau khi lưu. Đơn đã tạo giữ nguyên số tiền.
    </p>

    @if ($summary->isNotEmpty())
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($summary as $i => $row)
                <x-stat-card :index="sprintf('%02d', $i + 1)" :label="$row->type" :value="$row->total . ' xe'">
                    <x-slot:foot>
                        Giá ngày {{ number_format($row->min_day) }}{{ $row->min_day != $row->max_day ? ' – ' . number_format($row->max_day) : '' }} đ
                    </x-slot:foot>
                </x-stat-card>
            @endforeach
        </div>
    @endif

    @include('admin.pricing.partials.price-form')

    {{-- 02 · Giá từng xe --}}
    <form method="POST" action="{{ route('admin.pricing.vehicles') }}">
        @csrf
        @method('PUT')

        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">02 · Giá từng xe</div>

            @php $chips = ['' => 'Tất cả'] + array_combine(\App\Models\Vehicle::TYPES, \App\Models\Vehicle::TYPES); @endphp
            <nav class="flex flex-wrap gap-1">
                @foreach ($chips as $value => $text)
                    <a href="{{ route('admin.pricing', array_filter(['type' => $value])) }}"
                       class="rounded-full px-3 py-1 text-[10px] font-medium uppercase tracking-label transition-colors
                              {{ (string) $type === (string) $value ? 'bg-solid text-on-solid' : 'text-fg-muted hover:text-fg' }}">
                        {{ $text }}
                    </a>
                @endforeach
            </nav>
        </div>

        <x-table>
            <x-slot:head>
                <th>Xe</th>
                <th>Loại</th>
                <th class="text-right">Giá giờ</th>
                <th class="text-right">Giá ngày</th>
                <th class="text-right">Giá tuần</th>
            </x-slot:head>

            @forelse ($vehicles as $vehicle)
                <tr>
                    <td>
                        <div class="font-normal tabular-nums">{{ $vehicle->license_plate }}</div>
                        <div class="text-xs text-fg-muted">{{ $vehicle->name }} · {{ $vehicle->store->name }}</div>
                    </td>
                    <td class="text-fg-soft">{{ $vehicle->type }}</td>
                    @foreach (['price_per_hour' => 'hour', 'price_per_day' => 'day', 'price_per_week' => 'week'] as $field => $short)
                        <td class="text-right">
                            <x-form.input type="number" min="1000" step="1000"
                                name="prices[{{ $vehicle->id }}][{{ $field }}]"
                                id="price-{{ $vehicle->id }}-{{ $short }}"
                                :value="$vehicle->{$field}"
                                class="!mt-0 ms-auto w-32 text-right tabular-nums" />
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-8 text-center text-fg-muted">Chưa có xe nào{{ $type ? ' thuộc loại ' . $type : '' }}.</td>
                </tr>
            @endforelse
        </x-table>

        @if ($vehicles->isNotEmpty())
            <div class="mt-4 flex justify-end">
                <x-button>Lưu thay đổi</x-button>
            </div>
        @endif
    </form>
@endsection