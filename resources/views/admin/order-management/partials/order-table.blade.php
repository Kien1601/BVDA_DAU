<div class="overflow-x-auto">
    @if ($errors->any())
    <div class="mb-4 border border-edge bg-card p-4 text-sm text-vermilion">
        @foreach ($errors->all() as $message)
            <p>{{ $message }}</p>
        @endforeach
    </div>
@endif
    <table class="w-full text-sm"
           x-data="{ open: Number(sessionStorage.getItem('openOrder')) || null }"
           x-init="$watch('open', v => sessionStorage.setItem('openOrder', v ?? ''))">
        <thead>
            <tr class="border-b border-edge text-left text-[11px] font-medium uppercase tracking-[.2em] text-fg-muted">
                <th class="py-3 pr-4">Mã</th>
                <th class="py-3 pr-4">Khách</th>
                <th class="py-3 pr-4">Xe</th>
                <th class="py-3 pr-4">Nhận – Trả</th>
                <th class="py-3 pr-4 text-right">Tổng</th>
                <th class="py-3 pr-4">Trạng thái</th>
                <th class="py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="cursor-pointer border-b border-edge-soft text-fg hover:bg-card-2"
                    :class="open === {{ $order->id }} && 'bg-card-2'"
                    @click="open = open === {{ $order->id }} ? null : {{ $order->id }}">
                    <td class="py-3 pr-4 tabular-nums">#{{ $order->id }}</td>
                    <td class="py-3 pr-4">
                        {{ $order->user->name }}
                        <div class="text-xs text-fg-soft">{{ $order->user->phone }}</div>
                    </td>
                    <td class="py-3 pr-4">
                        {{ $order->vehicle->name }}
                        <div class="text-xs text-fg-soft">{{ $order->vehicle->license_plate }}</div>
                    </td>
                    <td class="py-3 pr-4 tabular-nums text-fg-soft">
                        {{ $order->start_time->format('H:i d/m') }} – {{ $order->end_time->format('H:i d/m') }}
                    </td>
                    <td class="py-3 pr-4 text-right tabular-nums">
                        {{ number_format($order->total_amount, 0, ',', '.') }} đ
                    </td>
                    <td class="py-3 pr-4">
                        {{-- BADGE: thay bằng x-status-badge chép từ bookingConfirm --}}
                        {{ $order->status->label() }}
                    </td>
                    <td class="py-3 text-right">
                        <a href="{{ route('admin.orders.show', $order) }}" @click.stop
                           x-show="open !== {{ $order->id }}"
                           class="text-fg-soft hover:text-vermilion">Xem</a>
                    </td>
                </tr>
                                <tr x-show="open === {{ $order->id }}" style="display: none" class="border-b border-edge-soft bg-card-2">
                    <td colspan="7" class="px-4 py-5">
                        @include('admin.order-management.partials.order-quick', ['order' => $order])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-fg-soft">Không có đơn nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>