<section class="border border-edge bg-card p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base text-fg">Thông tin đơn</h2>
                    {{-- BADGE --}}
                    <span class="text-sm text-fg">{{ $order->status->label() }}</span>
                </div>
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="{{ $label }}">Khách</dt><dd class="text-fg"><a href="{{ route('admin.customers.show', $order->user) }}" class="underline-offset-4 hover:text-vermilion hover:underline">{{ $order->user->name }}</a></dd></div>
                    <div><dt class="{{ $label }}">Điện thoại</dt><dd class="text-fg">{{ $order->user->phone ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">CCCD</dt><dd class="text-fg">{{ $order->user->id_card_number ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Số lần vi phạm</dt><dd class="text-fg tabular-nums">{{ $order->user->violation_count ?? 0 }}</dd></div>
                    <div><dt class="{{ $label }}">Xe</dt><dd class="text-fg">{{ $order->vehicle->name }} · {{ $order->vehicle->license_plate }}</dd></div>
                    <div><dt class="{{ $label }}">Khuyến mãi</dt><dd class="text-fg">{{ $order->promotion?->code ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Nhận xe</dt><dd class="text-fg tabular-nums">{{ $order->start_time->format('H:i d/m/Y') }}</dd></div>
                    <div>
                        <dt class="{{ $label }}">Trả xe</dt>
                        <dd class="text-fg tabular-nums">
                            {{ $order->end_time->format('H:i d/m/Y') }}
                            @if ($order->original_end_time)
                                <span class="text-fg-soft">(gia hạn từ {{ $order->original_end_time->format('H:i d/m') }})</span>
                            @endif
                        </dd>
                    </div>
                    @if ($order->actual_return_time)
                        <div><dt class="{{ $label }}">Trả thực tế</dt><dd class="text-fg tabular-nums">{{ $order->actual_return_time->format('H:i d/m/Y') }}</dd></div>
                    @endif
                    @if ($order->note)
                        <div class="sm:col-span-2"><dt class="{{ $label }}">Ghi chú của khách</dt><dd class="text-fg">{{ $order->note }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="border border-edge bg-card p-5">
                <h2 class="mb-4 text-base text-fg">Tiền</h2>
                <dl class="space-y-2 text-sm tabular-nums">
                    <div class="flex justify-between"><dt class="text-fg-soft">Tiền thuê</dt><dd class="text-fg">{{ $money($order->subtotal_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-soft">Giảm giá</dt><dd class="text-fg">− {{ $money($order->discount_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-soft">Tổng</dt><dd class="text-fg">{{ $money($order->total_amount) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-fg-soft">Đặt cọc</dt><dd class="text-fg">{{ $money($order->deposit_amount) }}</dd></div>
                    @if ($order->surcharge_amount > 0)
                        <div class="flex justify-between"><dt class="text-fg-soft">Phụ thu</dt><dd class="text-fg">{{ $money($order->surcharge_amount) }}</dd></div>
                        @if ($order->surcharge_note)
                            <div class="text-fg-soft">{{ $order->surcharge_note }}</div>
                        @endif
                        <div class="flex justify-between border-t border-edge pt-2"><dt class="text-fg">Tổng cần thu</dt><dd class="text-fg">{{ $money($order->total_amount + $order->surcharge_amount) }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="border border-edge bg-card p-5">
                <h2 class="mb-4 text-base text-fg">Lịch sử xử lý</h2>
                <ul class="space-y-1 text-sm text-fg-soft">
                    <li>Tạo đơn: {{ $order->created_at->format('H:i d/m/Y') }}</li>
                    @if ($order->approved_at)
                        <li>Duyệt: {{ $order->approved_at->format('H:i d/m/Y') }} · {{ $order->approvedBy?->name }}</li>
                    @endif
                    @if ($order->picked_up_at)
                        <li>Giao xe: {{ $order->picked_up_at->format('H:i d/m/Y') }} · {{ $order->pickedUpBy?->name }}</li>
                    @endif
                    @if ($order->actual_return_time)
                        <li>Nhận lại xe: {{ $order->actual_return_time->format('H:i d/m/Y') }} · {{ $order->returnedBy?->name }}</li>
                    @endif
                    @if ($order->cancelled_at)
                        <li>Hủy: {{ $order->cancelled_at->format('H:i d/m/Y') }} · {{ $order->cancelledBy?->name ?? 'Khách / hệ thống' }} · {{ $order->cancel_reason }}</li>
                    @endif
                </ul>
            </section>