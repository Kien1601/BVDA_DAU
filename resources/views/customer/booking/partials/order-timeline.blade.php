@php
    use App\Enums\OrderStatus;
    $fmt = fn ($t) => $t?->format('H:i d/m/Y');

    if ($order->status === OrderStatus::Cancelled) {
        $steps = array_filter([
            ['label' => 'Đặt đơn', 'at' => $order->created_at, 'note' => null],
            $order->approved_at ? ['label' => 'Cửa hàng duyệt', 'at' => $order->approved_at, 'note' => null] : null,
            ['label' => 'Đã hủy', 'at' => $order->cancelled_at, 'note' => $order->cancel_reason],
        ]);
    } else {
        $steps = [
            ['label' => 'Đặt đơn', 'at' => $order->created_at, 'note' => null],
            ['label' => 'Cửa hàng duyệt', 'at' => $order->approved_at, 'note' => null],
            ['label' => 'Nhận xe', 'at' => $order->picked_up_at, 'note' => null],
            ['label' => 'Trả xe', 'at' => $order->actual_return_time, 'note' => null],
        ];
    }
@endphp

<section class="border border-edge bg-card p-6">
    <p class="text-[11px] font-medium uppercase tracking-label text-fg-muted">Hành trình đơn</p>

    <ol class="mt-5 space-y-5">
        @foreach ($steps as $step)
            <li class="flex gap-4">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $step['at'] ? 'bg-fg' : 'border border-edge-strong' }}"></span>
                <div>
                    <div class="text-sm {{ $step['at'] ? 'text-fg' : 'text-fg-muted' }}">{{ $step['label'] }}</div>
                    <div class="text-xs tabular-nums text-fg-soft">{{ $fmt($step['at']) ?? 'Chưa đến bước này' }}</div>
                    @if ($step['note'])
                        <div class="mt-1 text-xs text-fg-soft">Lý do: {{ $step['note'] }}</div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @if ($order->original_end_time && $order->status !== OrderStatus::Cancelled)
        <p class="mt-5 border-t border-edge-soft pt-4 text-xs text-fg-soft">
            Đã gia hạn: trả xe lúc {{ $fmt($order->end_time) }} (ban đầu {{ $fmt($order->original_end_time) }}).
        </p>
    @endif
</section>