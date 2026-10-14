@php
    use App\Enums\PaymentType;
    $money = fn ($v) => number_format((int) $v, 0, ',', '.').' đ';
    $fmt = fn ($t) => $t?->format('H:i d/m/Y');
    $label = 'text-[10px] font-medium uppercase tracking-label text-fg-muted';
@endphp
<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hóa đơn {{ $number }} · Rental GPS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@300;400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-card-2 font-light text-fg antialiased print:bg-card">
    {{-- Nút thao tác: ẩn khi in --}}
    <div class="mx-auto flex max-w-3xl items-center justify-between px-5 pt-8 print:hidden">
        <a href="{{ route('booking.show', $order) }}" class="text-sm text-fg-soft hover:text-fg">← Về trang đơn</a>
        <button type="button" onclick="window.print()"
                class="rounded-full bg-solid px-5 py-2 text-sm text-on-solid transition-colors hover:bg-vermilion hover:text-white">
            In hóa đơn
        </button>
    </div>

    <article class="mx-auto my-6 max-w-3xl border border-edge bg-card p-8 sm:p-12 print:my-0 print:max-w-none print:border-0 print:p-0">
        {{-- Đầu hóa đơn --}}
        <header class="flex flex-wrap items-start justify-between gap-6 border-b border-edge pb-6">
            <div>
                <div class="flex items-center gap-3">
                    <span class="h-2 w-2 rounded-full bg-vermilion"></span>
                    <span class="text-xs font-medium tracking-[.26em]">RENTAL GPS</span>
                </div>
                <p class="mt-2 text-xs text-fg-soft">Thuê xe máy · Định vị GPS · Đà Nẵng</p>
            </div>
            <div class="text-right">
                <h1 class="text-2xl uppercase tracking-[.1em]">Hóa đơn</h1>
                <p class="mt-1 text-sm tabular-nums text-fg-soft">Số {{ $number }}</p>
                <p class="text-sm tabular-nums text-fg-soft">Ngày {{ $issuedAt->format('d/m/Y') }}</p>
            </div>
        </header>

        {{-- Khách, xe, thời gian --}}
        <section class="grid gap-6 border-b border-edge py-6 text-sm sm:grid-cols-3">
            <div>
                <div class="{{ $label }}">Khách hàng</div>
                <div class="mt-1">{{ $order->user->name }}</div>
                <div class="text-fg-soft">{{ $order->user->phone }}</div>
                <div class="text-fg-soft">{{ $order->user->email }}</div>
            </div>
            <div>
                <div class="{{ $label }}">Xe</div>
                <div class="mt-1">{{ $order->vehicle->name }}</div>
                <div class="text-fg-soft">{{ $order->vehicle->license_plate }} · {{ $order->vehicle->type }}</div>
                <div class="text-fg-soft">Đơn #{{ $order->id }}</div>
            </div>
            <div class="tabular-nums">
                <div class="{{ $label }}">Thời gian thuê</div>
                <div class="mt-1">Nhận: {{ $fmt($order->picked_up_at ?? $order->start_time) }}</div>
                <div>Hẹn trả: {{ $fmt($order->end_time) }}</div>
                <div>Trả thực tế: {{ $fmt($order->actual_return_time) }}</div>
            </div>
        </section>

        {{-- Các khoản tiền --}}
        <table class="mt-6 w-full text-sm tabular-nums">
            <thead>
                <tr class="border-b border-edge text-left">
                    <th class="py-2 {{ $label }}">Nội dung</th>
                    <th class="py-2 text-right {{ $label }}">Số tiền</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-edge-soft">
                    <td class="py-3">
                        Tiền thuê xe
                        <div class="text-xs text-fg-soft">{{ $fmt($order->start_time) }} → {{ $fmt($order->end_time) }}</div>
                    </td>
                    <td class="py-3 text-right">{{ $money($order->subtotal_amount) }}</td>
                </tr>
                @if ($order->discount_amount > 0)
                    <tr class="border-b border-edge-soft">
                        <td class="py-3">Giảm giá{{ $order->promotion ? ' (mã '.$order->promotion->code.')' : '' }}</td>
                        <td class="py-3 text-right">−{{ $money($order->discount_amount) }}</td>
                    </tr>
                @endif
                @if ($order->surcharge_amount > 0)
                    <tr class="border-b border-edge-soft">
                        <td class="py-3">
                            Phụ thu
                            @if ($order->surcharge_note)
                                <div class="text-xs text-fg-soft">{{ $order->surcharge_note }}</div>
                            @endif
                        </td>
                        <td class="py-3 text-right">{{ $money($order->surcharge_amount) }}</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td class="pt-4 text-base">Tổng cộng</td>
                    <td class="pt-4 text-right text-xl">{{ $money($grandTotal) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Đã thanh toán --}}
        <section class="mt-8 border-t border-edge pt-6">
            <div class="{{ $label }}">Các lần thanh toán</div>
            <ul class="mt-3 space-y-2 text-sm tabular-nums">
                @forelse ($paidPayments as $payment)
                    <li class="flex justify-between gap-4">
                        <span>
                            {{ $payment->type->label() }} · {{ $payment->method->label() }}
                            <span class="text-fg-soft">· {{ $fmt($payment->paid_at) }}</span>
                        </span>
                        <span>{{ $payment->type === PaymentType::Refund ? '−' : '' }}{{ $money($payment->amount) }}</span>
                    </li>
                @empty
                    <li class="text-fg-soft">Chưa có khoản nào.</li>
                @endforelse
            </ul>

            <dl class="mt-6 space-y-2 text-sm tabular-nums">
                <div class="flex justify-between"><dt class="text-fg-soft">Đã thanh toán</dt><dd>{{ $money($paidTotal) }}</dd></div>
                <div class="flex justify-between text-base">
                    <dt>Còn phải trả</dt>
                    <dd class="{{ $amountDue > 0 ? 'text-vermilion' : '' }}">{{ $money($amountDue) }}</dd>
                </div>
            </dl>

            <p class="mt-6 inline-block border px-3 py-1 text-xs font-medium uppercase tracking-label
                      {{ $amountDue > 0 ? 'border-vermilion text-vermilion' : 'border-edge-strong text-fg' }}">
                {{ $amountDue > 0 ? 'Chưa thanh toán đủ' : 'Đã thanh toán đủ' }}
            </p>
        </section>

        <footer class="mt-10 border-t border-edge-soft pt-4 text-center text-xs text-fg-soft">
            Cảm ơn quý khách đã sử dụng dịch vụ Rental GPS.
        </footer>
    </article>
</body>
</html>