@php
    $tab = 'inline-flex items-center gap-2 rounded-full border px-4 py-2 transition-colors';
    $tabOn = 'border-vermilion bg-card text-fg';
    $tabOff = 'border-edge-strong bg-card text-fg-soft hover:border-vermilion hover:text-fg';
    $badge = 'rounded-full bg-card-2 px-2 py-0.5 text-xs tabular-nums text-fg-soft';
@endphp

<nav class="mb-4 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('admin.orders.index', array_filter(['q' => $q])) }}"
       class="{{ $tab }} {{ $status === null ? $tabOn : $tabOff }}">
        Tất cả <span class="{{ $badge }}">{{ array_sum($counts) }}</span>
    </a>
    @foreach (\App\Enums\OrderStatus::cases() as $case)
        <a href="{{ route('admin.orders.index', array_filter(['status' => $case->value, 'q' => $q])) }}"
           class="{{ $tab }} {{ $status === $case->value ? $tabOn : $tabOff }}">
            {{ $case->label() }} <span class="{{ $badge }}">{{ $counts[$case->value] ?? 0 }}</span>
        </a>
    @endforeach
</nav>

{{-- Tìm kiếm --}}
<form method="GET" action="{{ route('admin.orders.index') }}" class="mb-6 flex gap-3">
    @if ($status)
        <input type="hidden" name="status" value="{{ $status }}">
    @endif
    <input type="text" name="q" value="{{ $q }}" placeholder="Mã đơn, tên / SĐT khách, biển số"
           class="w-full max-w-md border border-edge-strong bg-card px-3 py-2 text-sm text-fg placeholder:text-fg-muted focus:border-fg focus:outline-none">
    <button type="submit" class="rounded-full bg-solid px-5 py-2 text-sm text-on-solid transition-colors hover:bg-vermilion hover:text-white">
        Tìm
    </button>
</form>
