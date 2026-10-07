@php
    // Menu khai báo một lần. Mục chỉ hiện khi route đã tồn tại VÀ người dùng có quyền (lớp 3).
    // "match": các route được coi là thuộc mục này (để sáng mục khi đang ở trang thêm/sửa).
    $menu = [
        ['route' => 'admin.dashboard',        'match' => 'admin.dashboard',     'label' => 'Dashboard',    'can' => 'dashboard.view'],
        ['route' => 'admin.gps-monitor',      'match' => 'admin.gps-monitor*',  'label' => 'Giám sát GPS', 'can' => 'gps.monitor'],
        ['route' => 'admin.orders.index',     'match' => 'admin.orders.*',      'label' => 'Đơn thuê',     'can' => 'order.manage'],
        ['route' => 'admin.customers.index',  'match' => 'admin.customers.*',   'label' => 'Khách hàng',   'can' => 'customer.view'],
        ['route' => 'admin.vehicles.index',   'match' => 'admin.vehicles.*',    'label' => 'Xe',           'can' => 'vehicle.manage'],
        ['route' => 'admin.stores.index',     'match' => 'admin.stores.*',      'label' => 'Cửa hàng',     'can' => 'store.manage'],
        ['route' => 'admin.pricing',          'match' => 'admin.pricing*',      'label' => 'Giá thuê',     'can' => 'pricing.manage'],
        ['route' => 'admin.promotions.index', 'match' => 'admin.promotions.*',  'label' => 'Khuyến mãi',   'can' => 'pricing.manage'],
        ['route' => 'admin.staff.index',      'match' => 'admin.staff.*',       'label' => 'Nhân viên',    'can' => 'user.manage'],
        ['route' => 'admin.reports',          'match' => 'admin.reports*',      'label' => 'Báo cáo',      'can' => 'report.view'],
    ];
    $n = 0;
@endphp

<aside class="sticky top-0 flex h-screen w-60 shrink-0 flex-col bg-ink text-bone">
    <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center gap-3 border-b border-line-soft px-5">
        <span class="h-2 w-2 rounded-full bg-vermilion shadow-[0_0_10px_#e0231c]"></span>
        <span class="leading-none">
            <span class="block text-xs font-medium tracking-[.26em]">RENTAL GPS</span>
            <span class="mt-1 block text-[8px] tracking-[.34em] text-muted">KHU QUẢN LÝ</span>
        </span>
    </a>

    <nav class="flex-1 space-y-0.5 overflow-y-auto p-3">
        @foreach ($menu as $item)
            @if (Route::has($item['route']))
                @can($item['can'])
                    @php
                        $n++;
                        $active = request()->routeIs($item['match']);
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       @if ($active) aria-current="page" @endif
                       class="group relative flex items-center gap-3 rounded-md px-3 py-2.5 text-[11px] font-medium uppercase tracking-label transition-colors duration-300
                              {{ $active ? 'bg-ink-2 text-bone' : 'text-bone-dim hover:bg-ink-2 hover:text-bone' }}">
                        <span class="absolute inset-y-2 left-0 w-0.5 rounded-full {{ $active ? 'bg-vermilion' : 'bg-transparent' }}"></span>
                        <span class="w-5 tabular-nums transition-colors {{ $active ? 'text-ember' : 'text-muted group-hover:text-ember' }}">
                            {{ sprintf('%02d', $n) }}
                        </span>
                        {{ $item['label'] }}
                    </a>
                @endcan
            @endif
        @endforeach
    </nav>

    <div class="border-t border-line-soft px-5 py-4 text-[9px] uppercase tracking-label text-muted">
        © {{ date('Y') }} Rental GPS
    </div>
</aside>