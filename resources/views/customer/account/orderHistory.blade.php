@extends('layouts.customer')

@section('title', 'Đơn của tôi')

@php
    use App\Enums\OrderStatus;
    $money = fn ($v) => number_format((int) $v, 0, ',', '.').' đ';
    $tab = 'inline-flex items-center gap-2 rounded-full border px-4 py-2 transition-colors';
    $tabOn = 'border-vermilion bg-card text-fg';
    $tabOff = 'border-edge-strong text-fg-soft hover:border-fg hover:text-fg';
    $badge = 'rounded-full bg-card-2 px-2 py-0.5 text-xs tabular-nums text-fg-soft';
@endphp

@section('content')
    <section class="mx-auto max-w-4xl px-5 pb-24 pt-28 lg:px-8">
        <p class="text-[11px] font-medium uppercase tracking-label text-fg-muted">Tài khoản</p>
        <h1 class="mt-2 text-4xl uppercase tracking-[-.01em] text-fg">Đơn của tôi</h1>

        {{-- Lọc theo trạng thái --}}
        <nav class="mt-8 flex flex-wrap gap-2 text-sm">
            <a href="{{ route('account.orders') }}" class="{{ $tab }} {{ $status === null ? $tabOn : $tabOff }}">
                Tất cả <span class="{{ $badge }}">{{ array_sum($counts) }}</span>
            </a>
            @foreach (OrderStatus::cases() as $case)
                <a href="{{ route('account.orders', ['status' => $case->value]) }}"
                   class="{{ $tab }} {{ $status === $case->value ? $tabOn : $tabOff }}">
                    {{ $case->label() }} <span class="{{ $badge }}">{{ $counts[$case->value] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Danh sách đơn --}}
        <div class="mt-8 space-y-3">
            @forelse ($orders as $order)
                @php
                    $holdExpired = $order->status === OrderStatus::Pending
                        && $order->expires_at && $order->expires_at->isPast();
                @endphp
                <a href="{{ route('booking.show', $order) }}"
                   class="block border border-edge bg-card p-5 transition-colors hover:border-vermilion">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-[11px] font-medium uppercase tracking-label text-fg-muted">Đơn #{{ $order->id }}</div>
                            <div class="mt-1 text-lg text-fg">{{ $order->vehicle->name }}</div>
                            <div class="text-sm text-fg-soft">{{ $order->vehicle->license_plate }} · {{ $order->vehicle->type }}</div>
                        </div>
                        <div class="text-right">
                            @if ($holdExpired)
                                <x-status-badge tone="danger">Hết hạn giữ chỗ</x-status-badge>
                            @else
                                <x-status-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-status-badge>
                            @endif
                            <div class="mt-2 text-lg font-light tabular-nums text-fg">{{ $money($order->total_amount + $order->surcharge_amount) }}</div>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-edge-soft pt-3 text-sm tabular-nums text-fg-soft">
                        {{ $order->start_time->format('H:i d/m/Y') }} → {{ $order->end_time->format('H:i d/m/Y') }}
                    </div>
                </a>
            @empty
                <div class="border border-edge bg-card p-8 text-center">
                    <p class="text-sm text-fg-soft">
                        {{ $status ? 'Không có đơn nào ở trạng thái này.' : 'Bạn chưa có đơn thuê nào.' }}
                    </p>
                    <a href="{{ route('vehicles.index') }}" class="mt-4 inline-block text-sm text-fg hover:text-vermilion">Xem xe cho thuê →</a>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    </section>
@endsection