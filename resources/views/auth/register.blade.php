<x-guest-layout>
    <div class="mb-6">
        <div class="flex items-center gap-2 text-[10px] font-medium uppercase tracking-label text-fg-muted">
            <span class="h-1 w-1 rounded-full bg-vermilion"></span>Tài khoản khách hàng
        </div>
        <h1 class="mt-1.5 text-2xl font-normal text-fg">Đăng ký</h1>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <x-form.input name="name" label="Họ tên" required autofocus autocomplete="name" />
        <x-form.input type="email" name="email" label="Email" required autocomplete="username" />
        <x-form.input name="phone" label="Số điện thoại" required autocomplete="tel" inputmode="tel" />
        <x-form.input type="password" name="password" label="Mật khẩu" required autocomplete="new-password" />
        <x-form.input type="password" name="password_confirmation" label="Xác nhận mật khẩu" required autocomplete="new-password" />

        <x-button class="w-full">Tạo tài khoản</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-fg-muted">
        Đã có tài khoản?
        <a href="{{ route('login') }}" class="text-fg underline-offset-4 hover:underline">Đăng nhập</a>
    </p>
</x-guest-layout>