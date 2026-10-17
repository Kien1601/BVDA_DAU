<x-guest-layout>
    <h1 class="text-lg font-semibold text-fg">Nhập mã xác nhận</h1>
    <p class="mt-1 text-sm text-fg-soft">
        Mã 6 số đã gửi tới <strong class="text-fg">{{ $identifier }}</strong> qua {{ $channel->label() }},
        có hiệu lực {{ $minutes }} phút.
    </p>

    @if ($debugCode)
        <div class="mt-4 rounded border border-dashed border-edge-strong bg-card-2 px-3 py-2 text-sm text-fg-soft">
            Mã thử (chỉ hiện khi chạy local):
            <span class="font-mono font-semibold text-fg">{{ $debugCode }}</span>
        </div>
    @endif

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('otp.check') }}" class="mt-6">
        @csrf
        <x-input-label for="code" value="Mã xác nhận" />
        <x-text-input id="code" name="code" type="text" inputmode="numeric" maxlength="6"
            autocomplete="one-time-code" required autofocus
            class="mt-1 block w-full text-center font-mono text-2xl tracking-[0.5em]" />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />

        <x-primary-button class="mt-6 w-full justify-center">Đăng nhập</x-primary-button>
    </form>

    <div class="mt-4 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('otp.resend') }}">
            @csrf
            <button type="submit" class="text-fg-soft underline hover:text-fg">Gửi lại mã</button>
        </form>
        <a href="{{ route('login') }}" class="text-fg-soft underline hover:text-fg">Đổi email/SĐT</a>
    </div>
    <x-input-error :messages="$errors->get('identifier')" class="mt-2" />
</x-guest-layout>