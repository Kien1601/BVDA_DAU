<aside class="h-fit rounded-xl border border-edge bg-card p-6 lg:sticky lg:top-28">
    <div class="flex items-center gap-4 border-b border-edge-soft pb-5">
        <div data-theme="dark" class="h-16 w-20 shrink-0 overflow-hidden rounded-md bg-card-2">
            @if ($vehicle->imageUrl())
                <img src="{{ $vehicle->imageUrl() }}" alt="{{ $vehicle->name }}" class="h-full w-full object-cover">
            @endif
        </div>
        <div class="min-w-0">
            <div class="truncate text-base text-fg">{{ $vehicle->name }}</div>
            <div class="mt-0.5 text-xs tabular-nums text-fg-muted">{{ $vehicle->license_plate }} · {{ $vehicle->store->name }}</div>
        </div>
    </div>

    <dl class="mt-5 space-y-3 text-sm transition-opacity group-data-[state=loading]:opacity-50">
        <div class="flex justify-between gap-4">
            <dt class="text-fg-muted">Thời gian thuê</dt>
            <dd data-q="breakdown" class="text-right text-fg">—</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-fg-muted">Tiền thuê</dt>
            <dd data-q="subtotal" class="tabular-nums text-fg">—</dd>
        </div>
        <div data-q-row="discount" class="hidden justify-between gap-4 [&:not(.hidden)]:flex">
            <dt class="text-fg-muted">Giảm giá</dt>
            <dd data-q="discount" class="tabular-nums text-ember"></dd>
        </div>
        <div class="flex items-baseline justify-between gap-4 border-t border-edge-soft pt-4">
            <dt class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Tổng tiền</dt>
            <dd data-q="total" class="text-2xl font-light tabular-nums text-fg">—</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-fg-muted">Tiền cọc</dt>
            <dd data-q="deposit" class="tabular-nums text-fg">—</dd>
        </div>
    </dl>

    <p data-q-error class="mt-4 hidden rounded-md border border-vermilion/40 bg-vermilion/10 px-3 py-2 text-sm text-fg"></p>

    <x-button variant="light" class="mt-6 w-full" data-submit>Xác nhận đặt thuê</x-button>

    <p class="mt-4 text-xs leading-relaxed text-fg-muted">
        Sau khi xác nhận, xe được giữ cho bạn {{ $holdMinutes }} phút để thanh toán.
        Quá thời gian này, đơn tự hủy.
    </p>
</aside>