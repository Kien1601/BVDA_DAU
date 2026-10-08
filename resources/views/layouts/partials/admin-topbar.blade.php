<header class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-edge bg-page/90 px-6 backdrop-blur lg:px-8">
    <div class="text-[10px] font-medium uppercase tracking-label text-fg-muted">
        Hôm nay · {{ now()->format('d/m/Y') }}
    </div>

    <div class="flex items-center gap-5">
        <div class="text-right leading-tight">
            <div class="text-sm text-fg">{{ auth()->user()->name }}</div>
            <div class="text-[10px] uppercase tracking-label text-fg-muted">{{ auth()->user()->role->value }}</div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button variant="secondary">Đăng xuất</x-button>
        </form>
    </div>
</header>