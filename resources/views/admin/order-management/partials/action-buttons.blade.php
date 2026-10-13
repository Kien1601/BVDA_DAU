@php use App\Enums\OrderStatus; @endphp

@if ($order->status === OrderStatus::Pending)
                <section class="border border-edge bg-card p-5">
                    <h2 class="mb-2 text-base text-fg">Duyệt đơn</h2>
                    @if ($order->expires_at)
                        <p class="mb-4 text-sm text-fg-soft">Giữ chỗ đến {{ $order->expires_at->format('H:i d/m/Y') }}</p>
                    @endif
                    <form method="POST" action="{{ route('admin.orders.approve', $order) }}">
                        @csrf
                        <button type="submit" class="{{ $btn }}">Duyệt đơn</button>
                    </form>
                </section>
            @endif

            @if ($order->status === OrderStatus::Approved)
                <section class="border border-edge bg-card p-5">
                    <h2 class="mb-2 text-base text-fg">Giao xe</h2>
                    <p class="mb-4 text-sm text-fg-soft">Kiểm tra CCCD của khách và nhận cọc trước khi giao.</p>
                    <form method="POST" action="{{ route('admin.orders.pick-up', $order) }}"
                          onsubmit="return confirm('Xác nhận đã giao xe cho khách?')">
                        @csrf
                        <button type="submit" class="{{ $btn }}">Giao xe</button>
                    </form>
                </section>
            @endif

            @if (in_array($order->status, [OrderStatus::Approved, OrderStatus::Active], true))
                <section class="border border-edge bg-card p-5">
                    <h2 class="mb-4 text-base text-fg">Gia hạn</h2>
                    <form method="POST" action="{{ route('admin.orders.extend', $order) }}" class="space-y-3">
                        @csrf
                        <label class="block">
                            <span class="{{ $label }}">Giờ trả mới</span>
                            <input type="datetime-local" name="end_time" class="{{ $input }} mt-1"
                                   value="{{ old('end_time', $order->end_time->format('Y-m-d\TH:i')) }}">
                        </label>
                        <p class="text-xs text-fg-soft">Tiền thuê tính lại cả kỳ, giữ nguyên số tiền giảm cũ.</p>
                        <button type="submit" class="{{ $btn }}">Gia hạn</button>
                    </form>
                </section>
            @endif

            @if ($order->status === OrderStatus::Active)
                <section class="border border-edge bg-card p-5">
                    <h2 class="mb-2 text-base text-fg">Nhận lại xe</h2>
                    <p class="mb-4 text-sm text-fg-soft">
                        Phụ thu trả trễ nếu nhận ngay bây giờ:
                        <span class="tabular-nums text-fg">{{ $money($lateFeeNow) }}</span>
                    </p>
                    <form method="POST" action="{{ route('admin.orders.complete', $order) }}" class="space-y-3"
                          onsubmit="return confirm('Xác nhận đã nhận lại xe?')">
                        @csrf
                        <label class="block">
                            <span class="{{ $label }}">Phụ thu khác (đ)</span>
                            <input type="number" name="extra_fee" min="0" step="1000" class="{{ $input }} mt-1"
                                   value="{{ old('extra_fee', 0) }}">
                        </label>
                        <label class="block">
                            <span class="{{ $label }}">Lý do phụ thu</span>
                            <textarea name="surcharge_note" rows="2" class="{{ $input }} mt-1"
                                      placeholder="Hư hỏng, thiếu xăng...">{{ old('surcharge_note') }}</textarea>
                        </label>
                        <button type="submit" class="{{ $btn }}">Hoàn tất trả xe</button>
                    </form>
                </section>
            @endif

            @if (in_array($order->status, [OrderStatus::Pending, OrderStatus::Approved], true))
                <section class="border border-edge bg-card p-5">
                    <h2 class="mb-4 text-base text-fg">Hủy đơn</h2>
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="space-y-3"
                          onsubmit="return confirm('Hủy đơn này?')">
                        @csrf
                        <label class="block">
                            <span class="{{ $label }}">Lý do</span>
                            <input type="text" name="cancel_reason" maxlength="255" class="{{ $input }} mt-1"
                                   value="{{ old('cancel_reason') }}" placeholder="Khách không đến nhận xe...">
                        </label>
                        <button type="submit" class="{{ $btnDanger }}">Hủy đơn</button>
                    </form>
                </section>
            @endif