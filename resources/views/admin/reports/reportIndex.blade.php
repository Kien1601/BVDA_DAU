@extends('layouts.admin')

@php
    use App\Enums\OrderStatus;
    use Illuminate\Support\Carbon;
    $money = fn ($v) => number_format((int) $v, 0, ',', '.').' đ';
    $label = 'text-[11px] font-medium uppercase tracking-[.2em] text-fg-muted';
    $field = 'border border-edge-strong bg-card px-3 py-2 text-sm text-fg focus:border-fg focus:outline-none';
    $btn = 'rounded-full bg-solid px-5 py-2 text-sm text-on-solid transition-colors hover:bg-vermilion hover:text-white';
    $chip = 'rounded-full border border-edge-strong px-3 py-1 text-xs text-fg-soft transition-colors hover:border-fg hover:text-fg';

    $today = today();
    $quick = [
        'Hôm nay' => [$today, $today],
        '7 ngày' => [$today->copy()->subDays(6), $today],
        '30 ngày' => [$today->copy()->subDays(29), $today],
        'Tháng này' => [$today->copy()->startOfMonth(), $today],
        'Tháng trước' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
    ];

    // Chiều cao cột theo bậc 5%. Viết sẵn đủ tên lớp để Tailwind quét được khi build
    $heights = ['h-0', 'h-[5%]', 'h-[10%]', 'h-[15%]', 'h-[20%]', 'h-[25%]', 'h-[30%]', 'h-[35%]', 'h-[40%]', 'h-[45%]', 'h-[50%]',
                'h-[55%]', 'h-[60%]', 'h-[65%]', 'h-[70%]', 'h-[75%]', 'h-[80%]', 'h-[85%]', 'h-[90%]', 'h-[95%]', 'h-full'];
    $maxDay = max(1, max($byDay));
    $barClass = fn ($amount) => $amount > 0 ? $heights[max(1, (int) round($amount / $maxDay * 20))] : $heights[0];

    $dayCount = count($byDay);
    $labelEvery = $dayCount <= 31 ? 1 : 7;
    $cancelled = $orders['byStatus'][OrderStatus::Cancelled->value] ?? 0;
@endphp

@section('content')
    <x-page-header title="Báo cáo" />

    {{-- Chọn kỳ --}}
    <section class="mb-6 border border-edge bg-card p-5">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <div class="mb-1 {{ $label }}">Từ ngày</div>
                <x-text-input type="date" name="from" :value="$from->toDateString()" class="{{ $field }}" />
            </div>
            <div>
                <div class="mb-1 {{ $label }}">Đến ngày</div>
                <x-text-input type="date" name="to" :value="$to->toDateString()" class="{{ $field }}" />
            </div>
            <button type="submit" class="{{ $btn }}">Xem</button>
        </form>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($quick as $name => [$qFrom, $qTo])
                <a href="{{ route('admin.reports.index', ['from' => $qFrom->toDateString(), 'to' => $qTo->toDateString()]) }}"
                   class="{{ $chip }}">{{ $name }}</a>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-fg-soft">
            Kỳ {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }} ({{ $dayCount }} ngày, tối đa 92 ngày).
        </p>
    </section>

    {{-- Thẻ số --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Doanh thu thực thu" :value="$money($revenue)" index="01" accent />
        <x-stat-card label="Đơn mới" :value="$orders['newOrders']" index="02" />
        <x-stat-card label="Lượt thuê (giao xe)" :value="$orders['rentals']" index="03" />
        <x-stat-card label="Đơn hủy" :value="$cancelled" index="04" />
    </div>

    {{-- Biểu đồ doanh thu theo ngày --}}
    <section class="mb-6 border border-edge bg-card p-5">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 class="text-base text-fg">Doanh thu theo ngày</h2>
            <span class="text-xs text-fg-soft">Cao nhất: {{ $money(max($byDay)) }}</span>
        </div>

        @if (max($byDay) <= 0)
            <p class="py-10 text-center text-sm text-fg-soft">Không có khoản thu nào trong kỳ này.</p>
        @else
            <div class="flex h-48 items-end gap-px border-b border-edge">
                @foreach ($byDay as $day => $amount)
                    <div class="flex h-full flex-1 items-end" title="{{ Carbon::parse($day)->format('d/m') }}: {{ $money($amount) }}">
                        <div class="w-full bg-vermilion/80 transition-colors hover:bg-vermilion {{ $barClass($amount) }}"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-1 flex gap-px">
                @foreach (array_keys($byDay) as $i => $day)
                    <div class="flex-1 text-center text-[10px] tabular-nums text-fg-muted">
                        {{ $i % $labelEvery === 0 ? Carbon::parse($day)->format('d') : '' }}
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-fg-soft">Rê chuột lên cột để xem số tiền từng ngày.</p>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Đơn theo trạng thái --}}
        <section class="border border-edge bg-card p-5">
            <h2 class="mb-4 text-base text-fg">Đơn tạo trong kỳ</h2>
            <div class="space-y-2 text-sm tabular-nums">
                @foreach (OrderStatus::cases() as $case)
                    <div class="flex justify-between">
                        <x-status-badge :tone="$case->tone()">{{ $case->label() }}</x-status-badge>
                        <span class="text-fg">{{ $orders['byStatus'][$case->value] ?? 0 }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between border-t border-edge pt-2 text-fg">
                    <span>Tổng</span>
                    <span>{{ $orders['newOrders'] }}</span>
                </div>
            </div>
        </section>

        {{-- Xe thuê nhiều --}}
        <section class="border border-edge bg-card p-5 lg:col-span-2">
            <h2 class="mb-4 text-base text-fg">Xe thuê nhiều nhất</h2>
            @if ($topVehicles->isEmpty())
                <p class="py-6 text-center text-sm text-fg-soft">Chưa có lượt giao xe nào trong kỳ này.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-edge text-left {{ $label }}">
                                <th class="py-2 pr-3">#</th>
                                <th class="py-2 pr-3">Xe</th>
                                <th class="py-2 pr-3">Loại</th>
                                <th class="py-2 pr-3 text-right">Lượt thuê</th>
                                <th class="py-2 text-right">Tổng tiền đơn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topVehicles as $i => $vehicle)
                                <tr class="border-b border-edge-soft text-fg">
                                    <td class="py-2 pr-3 tabular-nums text-fg-muted">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="py-2 pr-3">
                                        {{ $vehicle->name }}
                                        <div class="text-xs text-fg-soft">{{ $vehicle->license_plate }}</div>
                                    </td>
                                    <td class="py-2 pr-3 text-fg-soft">{{ $vehicle->type }}</td>
                                    <td class="py-2 pr-3 text-right tabular-nums">{{ $vehicle->rentals }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ $money($vehicle->amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection