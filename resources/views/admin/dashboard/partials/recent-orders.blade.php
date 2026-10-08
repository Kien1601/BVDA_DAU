<div class="mt-8">
    <div class="mb-3 text-[10px] font-medium uppercase tracking-label text-fg-muted">Đơn thuê mới</div>

    <x-table>
        <x-slot:head>
            <th>Mã đơn</th>
            <th>Khách hàng</th>
            <th>Xe</th>
            <th>Bắt đầu</th>
            <th>Trạng thái</th>
        </x-slot:head>

        @forelse ($recentOrders as $order)
            <tr>
                <td class="tabular-nums">#{{ $order->id }}</td>
                <td>{{ $order->user->name }}</td>
                <td>{{ $order->vehicle->license_plate }}</td>
                <td class="tabular-nums">{{ $order->start_time->format('d/m/Y H:i') }}</td>
                <td><x-status-badge>{{ $order->status->value }}</x-status-badge></td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="py-8 text-center text-fg-muted">Chưa có đơn thuê nào.</td>
            </tr>
        @endforelse
    </x-table>
</div>