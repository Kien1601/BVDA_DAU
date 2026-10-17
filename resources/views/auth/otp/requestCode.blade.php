<x-guest-layout>
    <h1 class="text-lg font-semibold text-fg">Đăng nhập</h1>
    <p class="mt-1 text-sm text-fg-soft">
        Nhập email hoặc số điện thoại, chúng tôi gửi mã 6 số để bạn đăng nhập.
        Lần đầu dùng sẽ tự tạo tài khoản, không cần mật khẩu.
    </p>

    <form method="POST" action="{{ route('otp.send') }}" class="mt-6">
        @csrf
        <x-input-label for="identifier" value="Email hoặc số điện thoại" />
        <x-text-input id="identifier" name="identifier" type="text" class="mt-1 block w-full"
            :value="old('identifier')" required autofocus autocomplete="username"
            placeholder="0905123456 hoặc ban@gmail.com" />
        <x-input-error :messages="$errors->get('identifier')" class="mt-2" />

        <x-primary-button class="mt-6 w-full justify-center">Gửi mã</x-primary-button>
    </form>
</x-guest-layout>