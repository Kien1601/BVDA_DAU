@if ($order->status === \App\Enums\OrderStatus::Active)
    <div class="rounded-lg border border-edge-soft bg-card p-5">
        <h3 class="mb-1 font-semibold text-fg">Trả xe sớm</h3>
        <p class="mb-4 text-sm text-fg-soft">
            Báo trước để cửa hàng chuẩn bị nhận xe. Tiền thuê sẽ được tính lại theo giờ trả thực tế, phần dư được hoàn lại.
        </p>

        @if ($order->early_return_at)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded border border-edge-soft bg-card-2 px-4 py-3 text-sm">
                <span class="text-fg">Bạn đã báo trả lúc <strong>{{ $order->early_return_at->format('H:i d/m/Y') }}</strong></span>
                <form method="POST" action="{{ route('booking.early-return.cancel', $order) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-fg-soft hover:text-vermilion">Hủy báo trả sớm</button>
                </form>
            </div>
        @endif

        <form method="POST" action="{{ route('booking.early-return', $order) }}" class="space-y-3">
            @csrf
            <div>
                <x-input-label for="early_return_at" value="Giờ trả xe" />
                <x-text-input id="early_return_at" name="early_return_at" type="datetime-local" class="mt-1 block w-full"
                    :value="old('early_return_at', $order->early_return_at?->format('Y-m-d\TH:i'))"
                    :min="now()->addMinutes(config('rental.early_return_min_minutes'))->format('Y-m-d\TH:i')"
                    :max="$order->end_time->format('Y-m-d\TH:i')" required />
                <x-input-error :messages="$errors->get('early_return_at')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="early_return_note" value="Ghi chú (không bắt buộc)" />
                <textarea id="early_return_note" name="early_return_note" rows="2" maxlength="500"
                    class="mt-1 block w-full rounded border-edge-soft bg-card text-fg">{{ old('early_return_note', $order->early_return_note) }}</textarea>
                <x-input-error :messages="$errors->get('early_return_note')" class="mt-1" />
            </div>
            <x-button type="submit">{{ $order->early_return_at ? 'Đổi giờ trả' : 'Báo cửa hàng' }}</x-button>
        </form>
    </div>
@endif