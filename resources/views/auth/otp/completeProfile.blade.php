@php($byEmail = $channel === \App\Enums\LoginChannel::Email)

<x-guest-layout>
    <h1 class="text-lg font-semibold text-fg">Hoàn tất tài khoản</h1>
    <p class="mt-1 text-sm text-fg-soft">
        Bạn đã xác minh <strong class="text-fg">{{ $identifier }}</strong>.
        Cho cửa hàng biết tên{{ $byEmail ? ' và số điện thoại' : '' }} để liên hệ khi giao nhận xe.
    </p>

    <form method="POST" action="{{ route('otp.profile.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <x-input-label for="name" value="Họ và tên" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        @if ($byEmail)
            <div>
                <x-input-label for="phone" value="Số điện thoại di động" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                    :value="old('phone')" required autocomplete="tel" placeholder="0905123456" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        @else
            <div>
                <x-input-label for="email" value="Email (không bắt buộc, để nhận hóa đơn)" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                    :value="old('email')" autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        @endif

        <x-primary-button class="w-full justify-center">Hoàn tất và đăng nhập</x-primary-button>
    </form>
</x-guest-layout>