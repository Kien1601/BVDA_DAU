<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded border p-4">
        <div class="text-sm text-gray-500">Đơn chờ duyệt</div>
        <div class="text-2xl font-semibold">{{ $stats['pending_orders'] }}</div>
    </div>
    <div class="bg-white rounded border p-4">
        <div class="text-sm text-gray-500">Xe đang cho thuê</div>
        <div class="text-2xl font-semibold">{{ $stats['active_rentals'] }}</div>
    </div>
    <div class="bg-white rounded border p-4">
        <div class="text-sm text-gray-500">Xe sẵn sàng</div>
        <div class="text-2xl font-semibold">{{ $stats['available_vehicles'] }}</div>
    </div>
</div>

@can('report.view')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
        <div class="bg-white rounded border p-4">
            <div class="text-sm text-gray-500">Doanh thu hôm nay</div>
            <div class="text-2xl font-semibold">{{ number_format($revenue['today']) }} đ</div>
        </div>
        <div class="bg-white rounded border p-4">
            <div class="text-sm text-gray-500">Doanh thu tháng này</div>
            <div class="text-2xl font-semibold">{{ number_format($revenue['month']) }} đ</div>
        </div>
    </div>
@endcan