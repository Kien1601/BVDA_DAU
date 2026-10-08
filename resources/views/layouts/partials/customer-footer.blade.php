<footer class="border-t border-edge-soft bg-page">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-14 md:grid-cols-[1.4fr_repeat(3,1fr)] lg:px-8">
        <div>
            <div class="flex items-center gap-3">
                <span class="h-2 w-2 rounded-full bg-vermilion"></span>
                <span class="text-xs font-medium tracking-[.26em]">RENTAL GPS</span>
            </div>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-fg-muted">
                Thuê xe máy tại TP.HCM. Đặt nhanh, nhận xe tại cửa hàng, đội xe được theo dõi bằng GPS.
            </p>
        </div>

        <div>
            <h4 class="mb-4 text-[10px] font-medium uppercase tracking-label text-fg-muted">Khám phá</h4>
            <ul class="space-y-2.5 text-sm">
                <li><a href="{{ route('vehicles.index') }}" class="text-fg-soft transition-colors hover:text-fg">Xe cho thuê</a></li>
                <li><a href="{{ route('stores.index') }}" class="text-fg-soft transition-colors hover:text-fg">Cửa hàng</a></li>
            </ul>
        </div>

        <div>
            <h4 class="mb-4 text-[10px] font-medium uppercase tracking-label text-fg-muted">Loại xe</h4>
            <ul class="space-y-2.5 text-sm">
                @foreach (\App\Models\Vehicle::TYPES as $type)
                    <li><a href="{{ route('vehicles.index', ['type' => $type]) }}" class="text-fg-soft transition-colors hover:text-fg">{{ $type }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h4 class="mb-4 text-[10px] font-medium uppercase tracking-label text-fg-muted">Tài khoản</h4>
            <ul class="space-y-2.5 text-sm">
                @guest
                    <li><a href="{{ route('login') }}" class="text-fg-soft transition-colors hover:text-fg">Đăng nhập</a></li>
                    <li><a href="{{ route('register') }}" class="text-fg-soft transition-colors hover:text-fg">Đăng ký</a></li>
                @else
                    <li><span class="text-fg-soft">{{ auth()->user()->name }}</span></li>
                @endguest
            </ul>
        </div>
    </div>

    <div class="border-t border-edge-soft">
        <div class="mx-auto flex max-w-7xl flex-wrap justify-between gap-4 px-5 py-5 text-[10px] uppercase tracking-label text-fg-muted lg:px-8">
            <span>© {{ date('Y') }} Rental GPS</span>
            <span>Laravel · Three.js · Leaflet</span>
        </div>
    </div>
</footer>