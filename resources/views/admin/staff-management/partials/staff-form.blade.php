@csrf
@php($currentRole = old('role', $staff ? \App\Services\StaffService::roleOf($staff) : 'staff'))

<div class="space-y-4">
    <div>
        <x-input-label for="name" value="Họ tên" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $staff?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="email" value="Email đăng nhập" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $staff?->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="phone" value="Số điện thoại (không bắt buộc)" />
        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $staff?->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="role" value="Vai trò" />
        <select id="role" name="role" class="mt-1 block w-full rounded border-edge-soft bg-card text-fg">
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected($currentRole === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-fg-muted">Quản trị có thêm quyền quản lý tài khoản nhân viên.</p>
        <x-input-error :messages="$errors->get('role')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password" :value="$staff ? 'Mật khẩu mới (để trống nếu không đổi)' : 'Mật khẩu'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Nhập lại mật khẩu" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
    </div>

    <div class="flex items-center gap-4 pt-2">
        <x-button type="submit">Lưu</x-button>
        <a href="{{ route('admin.staff.index') }}" class="text-sm text-fg-soft hover:text-vermilion">Hủy</a>
    </div>
</div>