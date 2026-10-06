<div class="bg-white rounded border mt-6">
    <div class="px-4 py-3 border-b font-medium">Đơn thuê mới</div>

    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500">
            <tr>
                <th class="px-4 py-2">Mã đơn</th>
                <th class="px-4 py-2">Khách hàng</th>
                <th class="px-4 py-2">Xe</th>
                <th class="px-4 py-2">Bắt đầu</th>
                <th class="px-4 py-2">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recentOrders as $order)
                <tr class="border-t">
                    <td class="px-4 py-2">#{{ $order->id }}</td>
                    <td class="px-4 py-2">{{ $order->user->name }}</td>
                    <td class="px-4 py-2">{{ $order->vehicle->license_plate }}</td>
                    <td class="px-4 py-2">{{ $order->start_time->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">{{ $order->status->value }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Chưa có đơn thuê nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>