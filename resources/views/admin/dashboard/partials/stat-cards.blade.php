<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-stat-card index="01" label="Đơn chờ duyệt" :value="$stats['pending_orders']" accent />
    <x-stat-card index="02" label="Xe đang cho thuê" :value="$stats['active_rentals']" />
    <x-stat-card index="03" label="Xe sẵn sàng" :value="$stats['available_vehicles']" />
</div>

@can('report.view')
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card label="Doanh thu hôm nay" :value="number_format($revenue['today']) . ' đ'" />
        <x-stat-card label="Doanh thu tháng này" :value="number_format($revenue['month']) . ' đ'" />
    </div>
@endcan