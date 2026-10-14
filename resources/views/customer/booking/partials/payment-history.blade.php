@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
    use App\Enums\PaymentType;
    $money = fn ($v) => number_format((int) $v, 0, ',', '.').' đ';
    // Bỏ các khoản đã hủy, thất bại (link cũ, khách bấm rồi thôi) cho đỡ rối
    $rows = $order->payments
        ->filter(fn ($p) => in_array($p->status, [PaymentStatus::Paid, PaymentStatus::Pending], true))
        ->sortBy('id');
    $grandTotal = $order->total_amount + $order->surcharge_amount;
    $amountDue = max(0, $grandTotal - $order->paidAmount());
@endphp

<section class="border border-edge bg-card p-6">
    <p class="text-[11px] font-medium uppercase tracking-label text-fg-muted">Thanh toán</p>

    <dl class="mt-5 space-y-2 text-sm tabular-nums">
        <div class="flex justify-between"><dt class="text-fg-soft">Tiền thuê</dt><dd class="text-fg">{{ $money($order->subtotal_amount) }}</dd></div>
        @if ($order->discount_amount > 0)
            <div class="flex justify-between"><dt class="text-fg-soft">Giảm giá</dt><dd class="text-fg">−{{ $money($order->discount_amount) }}</dd></div>
        @endif
        @if ($order->surcharge_amount > 0)
            <div class="flex justify-between"><dt class="text-fg-soft">Phụ thu</dt><dd class="text-fg">{{ $money($order->surcharge_amount) }}</dd></div>
            @if ($order->surcharge_note)
                <div class="text-xs text-fg-soft">{{ $order->surcharge_note }}</div>
            @endif
        @endif
        <div class="flex justify-between border-t border-edge pt-2"><dt class="text-fg">Tổng cộng</dt><dd class="text-fg">{{ $money($grandTotal) }}</dd></div>
        @if ($order->status !== OrderStatus::Cancelled)
            <div class="flex justify-between"><dt class="text-fg-soft">Đã trả</dt><dd class="text-fg">{{ $money($order->paidAmount()) }}</dd></div>
            <div class="flex justify-between"><dt class="text-fg-soft">Còn lại</dt><dd class="text-fg">{{ $money($amountDue) }}</dd></div>
        @endif
    </dl>

    @if ($rows->isNotEmpty())
        <ul class="mt-5 space-y-3 border-t border-edge-soft pt-4 text-sm">
            @foreach ($rows as $payment)
                <li class="flex justify-between gap-4">
                    <div>
                        <div class="text-fg">{{ $payment->type->label() }} · {{ $payment->method->label() }}</div>
                        <div class="text-xs tabular-nums text-fg-soft">
                            {{ $payment->status->label() }}{{ $payment->paid_at ? ' · '.$payment->paid_at->format('H:i d/m/Y') : '' }}
                        </div>
                    </div>
                    <div class="tabular-nums text-fg">
                        {{ $payment->type === PaymentType::Refund ? '−' : '' }}{{ $money($payment->amount) }}
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($order->status === OrderStatus::Completed)
        <a href="{{ route('account.invoice', $order) }}"
           class="mt-6 inline-block rounded-full border border-edge-strong px-5 py-2 text-sm text-fg transition-colors hover:border-vermilion hover:text-vermilion">
            Xem hóa đơn
        </a>
    @endif
</section>