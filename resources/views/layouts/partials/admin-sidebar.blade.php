@php
    $menu = [
        ['route' => 'admin.dashboard',        'label' => 'Dashboard',    'can' => 'dashboard.view'],
        ['route' => 'admin.gps-monitor',      'label' => 'Giám sát GPS', 'can' => 'gps.monitor'],
        ['route' => 'admin.orders.index',     'label' => 'Đơn thuê',     'can' => 'order.manage'],
        ['route' => 'admin.customers.index',  'label' => 'Khách hàng',   'can' => 'customer.view'],
        ['route' => 'admin.vehicles.index',   'label' => 'Xe',           'can' => 'vehicle.manage'],
        ['route' => 'admin.stores.index',     'label' => 'Cửa hàng',     'can' => 'store.manage'],
        ['route' => 'admin.pricing',          'label' => 'Giá thuê',     'can' => 'pricing.manage'],
        ['route' => 'admin.promotions.index', 'label' => 'Khuyến mãi',   'can' => 'pricing.manage'],
        ['route' => 'admin.staff.index',      'label' => 'Nhân viên',    'can' => 'user.manage'],
        ['route' => 'admin.reports',          'label' => 'Báo cáo',      'can' => 'report.view'],
    ];
@endphp

<aside class="w-60 bg-white border-r">
    <div class="h-14 flex items-center px-4 font-semibold border-b">Rental GPS</div>
    <nav class="p-2 space-y-1">
        @foreach ($menu as $item)
            @if (Route::has($item['route']))
                @can($item['can'])
                    <a href="{{ route($item['route']) }}"
                       class="block px-3 py-2 rounded {{ request()->routeIs($item['route']) ? 'bg-indigo-50 text-indigo-700 font-medium' : 'hover:bg-gray-100' }}">
                        {{ $item['label'] }}
                    </a>
                @endcan
            @endif
        @endforeach
    </nav>
</aside>