@php
    use App\Enums\OrderStatus;
    $money = fn ($v) => number_format((int) $v, 0, ',', '.') . ' đ';
    $paid = $order->depositPaid();
    $due = app(\App\Services\PaymentService::class)->amountDue($order);
    $lateFee = $order->status === OrderStatus::Active
        ? (int) app(\App\Services\OrderManagementService::class)->lateFee($order, now())
        : 0;
    $lateTake = $order->status === OrderStatus::Approved && $order->start_time->isPast();
    $overdue  = $order->status === OrderStatus::Active && $order->end_time->isPast();
    $expired  = in_array($order->status, [OrderStatus::Pending, OrderStatus::Approved], true) && $order->end_time->isPast();
    $expired  = $order->status === OrderStatus::Pending && $order->end_time->isPast();
    $label = 'block text-[10px] uppercase tracking-label text-fg-muted';
@endphp

<div class="grid gap-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
    <div>
        <span class="{{ $label }}">Khách</span>
        <p class="mt-1 text-fg">{{ $order->user->name }}</p>
        <a href="tel:{{ $order->user->phone }}" class="text-fg-soft hover:text-vermilion">{{ $order->user->phone }}</a>
    </div>
    <div>
        <span class="{{ $label }}">Xe</span>
        <p class="mt-1 text-fg">{{ $order->vehicle->license_plate }} · {{ $order->vehicle->name }}</p>
        <p class="text-fg-soft">{{ $order->vehicle->store?->name }}</p>
    </div>
    <div>
        <span class="{{ $label }}">Thời gian</span>
        <p class="mt-1 tabular-nums text-fg">Nhận {{ $order->start_time->format('H:i d/m') }}</p>
        <p class="tabular-nums text-fg">Trả {{ $order->end_time->format('H:i d/m') }}</p>
        @if ($lateTake && ! $expired) <p class="font-medium text-vermilion">Trễ nhận xe</p> @endif
        @if ($expired) <p class="font-medium text-vermilion">Đơn đã quá hạn, chỉ có thể hủy</p> @endif
        @if ($overdue)  <p class="font-medium text-vermilion">Quá hạn trả</p> @endif
    </div>
    <div>
        <span class="{{ $label }}">Tiền</span>
        <p class="mt-1 tabular-nums text-fg">Tổng {{ $money($order->total_amount) }}</p>
        <p class="tabular-nums {{ $paid ? 'text-fg-soft' : 'text-vermilion' }}">
            Cọc {{ $money($order->deposit_amount) }} · {{ $paid ? 'đã thu' : 'chưa thu' }}
        </p>
        <p class="tabular-nums text-fg-soft">Còn thu {{ $money($due) }}</p>
    </div>
</div>

<div class="mt-5 flex flex-wrap items-center gap-3">
    {{-- Thu cọc: hiện ở chờ duyệt và đã duyệt khi chưa thu --}}
    @if (in_array($order->status, [OrderStatus::Pending, OrderStatus::Approved], true) && ! $paid && ! $expired)
        <form method="POST" action="{{ route('admin.orders.collect-cash', [$order, 'type' => 'deposit']) }}"
              onsubmit="return confirm('Xác nhận đã thu {{ $money($order->deposit_amount) }} tiền cọc?')">
            @csrf
            <x-button variant="secondary">Thu cọc {{ $money($order->deposit_amount) }}</x-button>
        </form>
    @endif

    @if ($order->status === OrderStatus::Pending && ! $expired)
        <form method="POST" action="{{ route('admin.orders.approve', $order) }}">
            @csrf
            <x-button>Duyệt đơn</x-button>
        </form>
    @endif

    @if ($order->status === OrderStatus::Approved && $paid && ! $expired)
        <form method="POST" action="{{ route('admin.orders.pick-up', $order) }}"
              onsubmit="return confirm('Xác nhận đã giao xe cho khách?')">
            @csrf
            <x-button>Giao xe</x-button>
        </form>
    @endif

    @if ($order->status === OrderStatus::Active)
        @if ($lateFee === 0)
            <form method="POST" action="{{ route('admin.orders.complete', $order) }}"
                  onsubmit="return confirm('Xác nhận đã nhận lại xe, không có phụ thu?')">
                @csrf
                <x-button>Nhận lại xe</x-button>
            </form>
        @else
            <span class="text-xs text-vermilion">Trễ hạn, phụ thu {{ $money($lateFee) }}. Xử lý ở trang chi tiết.</span>
        @endif
    @endif

    <x-button variant="link" :href="route('admin.orders.show', $order)">Xem chi tiết</x-button>
</div>

{{-- Hủy đơn chờ duyệt: cần lý do (bắt buộc ở controller) --}}
@if ($order->status === OrderStatus::Pending || $expired)
    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}"
          onsubmit="return confirm('Hủy đơn #{{ $order->id }}?')"
          class="mt-4 flex flex-wrap items-end gap-3 border-t border-edge-soft pt-4">
        @csrf
        <div class="w-full sm:w-80">
            <x-form.input name="cancel_reason" label="Lý do hủy" placeholder="Khách không đến nhận xe..." />
        </div>
        <x-button variant="secondary">Hủy đơn</x-button>
    </form>
@endif