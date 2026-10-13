@php
    $depositPaid = $order->depositPaid();
    $cashChosen = $order->payments->contains(fn ($p) =>
        $p->type === \App\Enums\PaymentType::Deposit
        && $p->method === \App\Enums\PaymentMethod::Cash
        && $p->status === \App\Enums\PaymentStatus::Pending);
    $holdExpired = $order->status === \App\Enums\OrderStatus::Pending
        && $order->expires_at && $order->expires_at->isPast();
    $canPay = in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Approved], true)
        && ! $depositPaid && ! $holdExpired;
    $refund = $order->payments->first(fn ($p) => $p->type === \App\Enums\PaymentType::Refund);
    $money = fn ($v) => number_format((int) $v, 0, ',', '.').' đ';
    $btn = 'rounded-full bg-solid px-6 py-3 text-sm text-on-solid transition-colors hover:bg-vermilion hover:text-white';
    $btn2 = 'rounded-full border border-edge-strong px-6 py-3 text-sm text-fg transition-colors hover:border-fg';
@endphp

<section class="border border-edge bg-card p-6">
    <p class="text-[11px] font-medium uppercase tracking-[.2em] text-fg-muted">Thanh toán tiền cọc</p>
    <p class="mt-2 text-2xl font-light tabular-nums text-fg">{{ $money($order->deposit_amount) }}</p>

    @if (session('success'))
        <p class="mt-4 text-sm text-fg">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p class="mt-4 text-sm text-vermilion">{{ session('error') }}</p>
    @endif
    @error('payment')
        <p class="mt-4 text-sm text-vermilion">{{ $message }}</p>
    @enderror

    @if ($depositPaid)
        <p class="mt-4 text-sm text-fg">Đã trả cọc. Phần còn lại thanh toán tại cửa hàng khi trả xe.</p>
    @elseif ($canPay)
        @if ($cashChosen)
            <p class="mt-4 text-sm text-fg-soft">Bạn đã chọn trả cọc tại cửa hàng khi nhận xe. Vẫn có thể trả online ngay bây giờ.</p>
        @else
            <p class="mt-4 text-sm text-fg-soft">Trả cọc để giữ xe. Chưa trả thì đơn tự hủy khi hết thời gian giữ chỗ.</p>
        @endif

        <div class="mt-5 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('payment.vnpay.create', $order) }}">
                @csrf
                <button type="submit" class="{{ $btn }}">Trả qua VNPay</button>
            </form>
            <form method="POST" action="{{ route('payment.momo.create', $order) }}">
                @csrf
                <button type="submit" class="{{ $btn }}">Trả qua MoMo</button>
            </form>
            @unless ($cashChosen)
                <form method="POST" action="{{ route('payment.cash', $order) }}">
                    @csrf
                    <button type="submit" class="{{ $btn2 }}">Trả tại cửa hàng</button>
                </form>
            @endunless
        </div>
    @endif

    @if ($refund)
        <p class="mt-4 text-sm text-fg">
            Hoàn tiền {{ $money($refund->amount) }}:
            {{ $refund->status === \App\Enums\PaymentStatus::Paid ? 'cửa hàng đã hoàn.' : 'đang chờ cửa hàng xử lý.' }}
        </p>
    @endif
</section>