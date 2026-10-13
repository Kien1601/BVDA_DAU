@extends('layouts.customer')

@section('title', 'Đơn thuê #' . $order->id)

@section('content')
    @php
        $status = $order->status;
        $showHold = $status === \App\Enums\OrderStatus::Pending && ! $holdExpired;
    @endphp

    <section class="mx-auto max-w-4xl px-5 pb-24 pt-28 lg:px-8">
        @if (session('success'))
            <div class="mb-6 flex items-start gap-3 rounded-md border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-fg" role="status">
                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>{{ session('success') }}
            </div>
        @endif
        @error('order')
            <div class="mb-6 rounded-md border border-vermilion/40 bg-vermilion/10 px-4 py-3 text-sm text-fg">{{ $message }}</div>
        @enderror

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Đơn thuê</div>
                <h1 class="mt-2 text-4xl font-normal tabular-nums tracking-tight">#{{ $order->id }}</h1>
            </div>
            @if ($holdExpired)
                <x-status-badge tone="danger">Hết hạn giữ chỗ</x-status-badge>
            @else
                <x-status-badge :tone="$status->tone()">{{ $status->label() }}</x-status-badge>
            @endif
        </div>

        @if ($showHold && $order->expires_at)
            <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-ember/40 bg-ember/10 p-5">
                <div>
                    <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Xe đang được giữ cho bạn</div>
                    <p class="mt-1 text-sm text-fg-soft">Thanh toán trước khi hết giờ để giữ đơn. Chức năng thanh toán có ở bước tiếp theo.</p>
                </div>
                <div class="text-4xl font-light tabular-nums text-ember" data-countdown="{{ $order->expires_at->toIso8601String() }}">--:--</div>
            </div>
        @elseif ($holdExpired)
            <p class="mt-8 rounded-xl border border-edge bg-card-2 p-5 text-sm text-fg-soft">
                Đơn đã quá thời gian giữ chỗ nên xe không còn được giữ. Bạn có thể đặt lại.
            </p>
        @endif

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-edge bg-card p-6">
                <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Xe</div>
                <div class="mt-3 text-lg text-fg">{{ $order->vehicle->name }}</div>
                <div class="mt-1 text-sm tabular-nums text-fg-soft">{{ $order->vehicle->license_plate }}</div>
                <div class="mt-4 text-sm text-fg-soft">Nhận và trả tại <span class="text-fg">{{ $order->vehicle->store->name }}</span></div>
                <div class="mt-1 text-sm text-fg-muted">{{ $order->vehicle->store->address }}</div>
            </div>

            <div class="rounded-xl border border-edge bg-card p-6">
                <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Thời gian</div>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-fg-muted">Nhận xe</dt><dd class="tabular-nums text-fg">{{ $order->start_time->format('H:i · d/m/Y') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-muted">Trả xe</dt><dd class="tabular-nums text-fg">{{ $order->end_time->format('H:i · d/m/Y') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-fg-muted">Thời lượng</dt><dd class="text-fg">{{ $duration }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-edge bg-card p-6">
            <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Thanh toán</div>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-fg-muted">Tiền thuê</dt><dd class="tabular-nums text-fg">{{ \App\Support\Money::vnd($order->subtotal_amount) }}</dd></div>
                @if ($order->discount_amount)
                    <div class="flex justify-between gap-4">
                        <dt class="text-fg-muted">Giảm giá{{ $order->promotion ? ' (' . $order->promotion->code . ')' : '' }}</dt>
                        <dd class="tabular-nums text-ember">− {{ \App\Support\Money::vnd($order->discount_amount) }}</dd>
                    </div>
                @endif
                <div class="flex items-baseline justify-between gap-4 border-t border-edge-soft pt-3">
                    <dt class="text-[10px] font-medium uppercase tracking-label text-fg-muted">Tổng tiền</dt>
                    <dd class="text-2xl font-light tabular-nums text-fg">{{ \App\Support\Money::vnd($order->total_amount) }}</dd>
                </div>
                <div class="flex justify-between gap-4"><dt class="text-fg-muted">Tiền cọc</dt><dd class="tabular-nums text-fg">{{ \App\Support\Money::vnd($order->deposit_amount) }}</dd></div>
            </dl>
            @if ($order->note)
                <p class="mt-4 border-t border-edge-soft pt-3 text-sm text-fg-soft">Ghi chú: {{ $order->note }}</p>
            @endif
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-4">
            <x-button variant="outline-light" :href="route('vehicles.index')">Xem xe khác</x-button>

            @can('cancel', $order)
                <form method="POST" action="{{ route('booking.cancel', $order) }}"
                      onsubmit="return confirm('Hủy đơn thuê #{{ $order->id }}?');">
                    @csrf
                    <x-button variant="link-danger">Hủy đơn</x-button>
                </form>
            @endcan
        </div>
    </section>
@endsection

@push('scripts')
    @vite('resources/views/customer/booking/booking.page.js')
@endpush