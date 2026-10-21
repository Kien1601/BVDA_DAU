@extends('layouts.admin')

@section('title', 'Đội xe')

@section('content')
    <x-page-header eyebrow="Danh mục" title="Tình trạng đội xe" />

    <form method="GET" action="{{ route('admin.fleet.index') }}"
          class="mb-5 grid grid-cols-1 items-end gap-3 sm:grid-cols-[16rem_auto]">
        <x-form.select name="store_id" label="Cửa hàng" :options="$stores" :value="$storeId" placeholder="Tất cả cửa hàng" />
        <div class="flex items-center gap-4">
            <x-button variant="secondary">Lọc</x-button>
            @if ($storeId)
                <x-button variant="link" :href="route('admin.fleet.index')">Xóa lọc</x-button>
            @endif
        </div>
    </form>

    @php
        // Thẻ nào có "status" thì bấm vào sẽ mở danh sách xe đã lọc theo trạng thái đó.
        $cards = [
            ['label' => 'Tổng xe',   'value' => $summary['total'],       'status' => null],
            ['label' => 'Sẵn sàng',  'value' => $summary['available'],   'status' => 'available'],
            ['label' => 'Đã đặt',    'value' => $summary['booked'],      'status' => null],
            ['label' => 'Đang thuê', 'value' => $summary['rented'],      'status' => 'rented'],
            ['label' => 'Bảo trì',   'value' => $summary['maintenance'], 'status' => 'maintenance'],
        ];
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($cards as $card)
            <a href="{{ route('admin.vehicles.index', array_filter(['status' => $card['status'], 'store' => $storeId])) }}"
               class="rounded-md border border-edge-soft bg-card p-4 transition-colors hover:border-ember">
                <span class="block text-[10px] uppercase tracking-label text-fg-muted">{{ $card['label'] }}</span>
                <span class="mt-2 block text-2xl font-medium tabular-nums text-fg">{{ $card['value'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-md border border-edge-soft bg-card">
        <table class="w-full text-sm">
            <thead class="text-[10px] uppercase tracking-label text-fg-muted">
                <tr class="border-b border-edge-soft">
                    <th class="px-4 py-3 text-left">Loại xe</th>
                    <th class="px-4 py-3 text-right">Tổng</th>
                    <th class="px-4 py-3 text-right">Sẵn sàng</th>
                    <th class="px-4 py-3 text-right">Đã đặt</th>
                    <th class="px-4 py-3 text-right">Đang thuê</th>
                    <th class="px-4 py-3 text-right">Bảo trì</th>
                    <th class="px-4 py-3 text-right">Tỷ lệ dùng</th>
                    <th class="px-4 py-3 text-left">Tình trạng</th>
                </tr>
            </thead>
            <tbody class="text-fg">
                @forelse ($types as $row)
                    <tr class="border-b border-edge-soft last:border-0">
                        <td class="px-4 py-3 font-medium">{{ $row['type'] ?: 'Chưa phân loại' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $row['total'] }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $row['available'] }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $row['booked'] }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $row['rented'] }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $row['maintenance'] }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $row['usage'] }}%</td>
                        <td class="px-4 py-3">
                            @switch($row['stock'])
                                @case('out')
                                    <span class="font-medium text-vermilion">Hết xe</span>
                                    @break
                                @case('low')
                                    <span class="font-medium text-ember">Sắp hết</span>
                                    @break
                                @default
                                    <span class="text-fg-soft">Còn xe</span>
                            @endswitch
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-fg-muted">Chưa có xe nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection