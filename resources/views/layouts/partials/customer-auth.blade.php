@auth
    @if (auth()->user()->role->isManager())
        <x-button variant="outline-light" :href="route('admin.dashboard')">Khu quản lý</x-button>
    @else
        <span class="text-sm text-fg-soft">{{ auth()->user()->name }}</span>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="text-[11px] font-medium uppercase tracking-label text-fg-soft transition-colors hover:text-fg">
            Đăng xuất
        </button>
    </form>
@else
    <a href="{{ route('login') }}" class="text-[11px] font-medium uppercase tracking-label text-fg-soft transition-colors hover:text-fg">
        Đăng nhập
    </a>
    <x-button variant="light" :href="route('register')">Đăng ký</x-button>
@endauth