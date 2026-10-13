<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="block">
        <x-stat-card index="01" label="Đơn chờ duyệt" :value="$stats['pending_orders']" accent />
    </a>
    <a href="{{ route('admin.orders.index', ['status' => 'active']) }}" class="block">
        <x-stat-card index="02" label="Đang thuê" :value="$stats['active_rentals']" />
    </a>
    <x-stat-card index="03" label="Xe sẵn sàng" :value="$stats['available_vehicles']" />
</div>

@can('report.view')
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card label="Doanh thu hôm nay" :value="number_format($revenue['today']) . ' đ'" />
        <x-stat-card label="Doanh thu tháng này" :value="number_format($revenue['month']) . ' đ'" />
    </div>
@endcan