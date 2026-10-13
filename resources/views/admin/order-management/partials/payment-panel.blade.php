@php
    $depositPaid = $order->depositPaid();
    $cashChosen = $order->payments->contains(fn ($p) =>
        $p->type === \App\Enums\PaymentType::Deposit
        && $p->method === \App\Enums\PaymentMethod::Cash
        && $p->status === \App\Enums\PaymentStatus::Pending);
    $canCollectDeposit = ! $depositPaid && in_array($order->status, [
        \App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Approved, \App\Enums\OrderStatus::Active,
    ], true);
    $canCollectBalance = $amountDue > 0 && in_array($order->status, [
        \App\Enums\OrderStatus::Active, \App\Enums\OrderStatus::Completed,
    ], true);
@endphp

<section class="border border-edge bg-card p-5">
    <h2 class="mb-4 text-base text-fg">Thanh toán</h2>

    <dl class="mb-4 space-y-2 text-sm tabular-nums">
        <div class="flex justify-between"><dt class="text-fg-soft">Cần thu (tổng + phụ thu)</dt><dd class="text-fg">{{ $money($order->total_amount + $order->surcharge_amount) }}</dd></div>
        <div class="flex justify-between"><dt class="text-fg-soft">Đã thu</dt><dd class="text-fg">{{ $money($order->paidAmount()) }}</dd></div>
        <div class="flex justify-between border-t border-edge pt-2"><dt class="text-fg">Còn lại</dt><dd class="text-fg">{{ $money($amountDue) }}</dd></div>
    </dl>

    @if ($cashChosen)
        <p class="mb-4 text-sm text-fg-soft">Khách chọn trả cọc tiền mặt khi nhận xe.</p>
    @endif

    @if ($order->payments->isNotEmpty())
        <div class="mb-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-edge text-left {{ $label }}">
                        <th class="py-2 pr-3">Khoản</th>
                        <th class="py-2 pr-3">Cách trả</th>
                        <th class="py-2 pr-3 text-right">Số tiền</th>
                        <th class="py-2 pr-3">Trạng thái</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->payments->sortBy('id') as $payment)
                        <tr class="border-b border-edge-soft text-fg">
                            <td class="py-2 pr-3">{{ $payment->type->label() }}</td>
                            <td class="py-2 pr-3 text-fg-soft">{{ $payment->method->label() }}</td>
                            <td class="py-2 pr-3 text-right tabular-nums">{{ $money($payment->amount) }}</td>
                            <td class="py-2 pr-3">
                                {{ $payment->status->label() }}
                                @if ($payment->paid_at)
                                    <div class="text-xs text-fg-soft">{{ $payment->paid_at->format('H:i d/m') }}{{ $payment->createdBy ? ' · '.$payment->createdBy->name : '' }}</div>
                                @endif
                            </td>
                            <td class="py-2 text-right">
                                @if ($payment->type === \App\Enums\PaymentType::Refund && $payment->status === \App\Enums\PaymentStatus::Pending)
                                    <form method="POST" action="{{ route('admin.orders.confirm-refund', [$order, $payment]) }}"
                                          onsubmit="return confirm('Xác nhận đã hoàn {{ $money($payment->amount) }} cho khách?')">
                                        @csrf
                                        <button type="submit" class="text-fg-soft hover:text-vermilion">Đã hoàn tiền</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        @if ($canCollectDeposit)
            <form method="POST" action="{{ route('admin.orders.collect-cash', $order) }}"
                  onsubmit="return confirm('Thu cọc {{ $money($order->deposit_amount) }} tiền mặt?')">
                @csrf
                <button type="submit" name="type" value="deposit" class="{{ $btn }}">Thu cọc {{ $money($order->deposit_amount) }}</button>
            </form>
        @endif
        @if ($canCollectBalance)
            <form method="POST" action="{{ route('admin.orders.collect-cash', $order) }}"
                  onsubmit="return confirm('Thu {{ $money($amountDue) }} tiền mặt?')">
                @csrf
                <button type="submit" name="type" value="balance" class="{{ $btn }}">Thu phần còn lại {{ $money($amountDue) }}</button>
            </form>
        @endif
    </div>
</section>