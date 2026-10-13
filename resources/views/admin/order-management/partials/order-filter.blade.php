{{-- Tab lọc theo trạng thái --}}
<nav class="mb-4 flex flex-wrap gap-x-6 gap-y-2 border-b border-edge text-sm">
    <a href="{{ route('admin.orders.index', array_filter(['q' => $q])) }}"
       class="-mb-px border-b-2 pb-2 {{ $status === null ? 'border-vermilion text-fg' : 'border-transparent text-fg-soft hover:text-fg' }}">
        Tất cả <span class="tabular-nums text-fg-muted">{{ array_sum($counts) }}</span>
    </a>
    @foreach (\App\Enums\OrderStatus::cases() as $case)
        <a href="{{ route('admin.orders.index', array_filter(['status' => $case->value, 'q' => $q])) }}"
           class="-mb-px border-b-2 pb-2 {{ $status === $case->value ? 'border-vermilion text-fg' : 'border-transparent text-fg-soft hover:text-fg' }}">
            {{ $case->label() }} <span class="tabular-nums text-fg-muted">{{ $counts[$case->value] ?? 0 }}</span>
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
