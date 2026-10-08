<x-guest-layout>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-[10px] font-medium uppercase tracking-label text-fg-muted">
            <span class="h-1 w-1 rounded-full bg-vermilion"></span>Chào mừng trở lại
        </div>
        <h1 class="mt-1.5 text-2xl font-normal text-fg">Đăng nhập</h1>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-form.input type="email" name="email" label="Email" required autofocus autocomplete="username" />
        <x-form.input type="password" name="password" label="Mật khẩu" required autocomplete="current-password" />

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-fg-soft">
                <input type="checkbox" name="remember"
                       class="h-4 w-4 rounded border-edge-strong bg-card text-vermilion focus:ring-vermilion/40">
                Ghi nhớ đăng nhập
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-fg-soft underline-offset-4 hover:text-fg hover:underline">
                    Quên mật khẩu?
                </a>
            @endif
        </div>

        <x-button class="w-full">Đăng nhập</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-fg-muted">
        Chưa có tài khoản?
        <a href="{{ route('register') }}" class="text-fg underline-offset-4 hover:underline">Đăng ký</a>
    </p>
</x-guest-layout>