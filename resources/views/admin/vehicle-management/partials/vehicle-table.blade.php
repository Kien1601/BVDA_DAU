<x-table>
    <x-slot:head>
        <th class="w-20">Ảnh</th>
        <th>Xe</th>
        <th>Cửa hàng</th>
        <th class="text-right">Giá / ngày</th>
        <th>Thiết bị GPS</th>
        <th>Trạng thái</th>
        <th class="text-right">Thao tác</th>
    </x-slot:head>

    @forelse ($vehicles as $vehicle)
        <tr>
            <td>
                <div class="h-12 w-16 overflow-hidden rounded border border-ink/10 bg-paper">
                    @if ($vehicle->imageUrl())
                        <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" class="h-full w-full object-cover" loading="lazy">
                    @endif
                </div>
            </td>
            <td>
                <div class="font-normal tabular-nums">{{ $vehicle->license_plate }}</div>
                <div class="text-xs text-muted">{{ $vehicle->name }} · {{ $vehicle->type }}</div>
            </td>
            <td class="text-ink/80">{{ $vehicle->store->name }}</td>
            <td class="text-right tabular-nums">{{ number_format($vehicle->price_per_day) }} đ</td>
            <td class="tabular-nums">
                @if ($vehicle->gps_device_id)
                    <span class="text-ink/80">{{ $vehicle->gps_device_id }}</span>
                @else
                    <span class="text-muted">Chưa gắn</span>
                @endif
            </td>
            <td>
                <x-status-badge :tone="$vehicle->status->tone()">{{ $vehicle->status->label() }}</x-status-badge>
            </td>
            <td class="whitespace-nowrap text-right">
                <x-button variant="link" :href="route('admin.vehicles.edit', $vehicle)">Sửa</x-button>

                @can('vehicle.delete')
                    <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}" class="ms-4 inline"
                          onsubmit="return confirm('Xóa xe {{ $vehicle->license_plate }}?');">
                        @csrf
                        @method('DELETE')
                        <x-button variant="link-danger">Xóa</x-button>
                    </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="py-8 text-center text-muted">
                {{ array_filter($filters) ? 'Không có xe nào phù hợp bộ lọc.' : 'Chưa có xe nào.' }}
            </td>
        </tr>
    @endforelse
</x-table>