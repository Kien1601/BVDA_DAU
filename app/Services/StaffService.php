<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** C7 - Quản lý tài khoản nhân viên (chỉ admin) */
class StaffService
{
    public const ROLES = ['staff' => 'Nhân viên', 'admin' => 'Quản trị'];

    public function search(?string $keyword): LengthAwarePaginator
    {
        return User::query()
            ->whereIn('role', array_keys(self::ROLES))
            ->when($keyword, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        return User::forceCreate([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function update(User $staff, array $data, User $actor): User
    {
        if ($data['role'] !== self::roleOf($staff)) {
            $this->guardSelf($staff, $actor, 'role', 'đổi vai trò');
        }

        $staff->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
        ]);
        if (! empty($data['password'])) {
            $staff->password = $data['password'];
        }
        $staff->save();

        return $staff;
    }

    public function toggleLock(User $staff, User $actor): User
    {
        $locking = (bool) $staff->is_active;
        if ($locking) {
            $this->guardSelf($staff, $actor, 'staff', 'khóa');
        }

        $staff->forceFill(['is_active' => ! $locking]);
        if ($locking) {
            $staff->setRememberToken(Str::random(60)); // mất "ghi nhớ đăng nhập"
        }
        $staff->save();

        if ($locking && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $staff->id)->delete();
        }

        return $staff;
    }

    public static function roleOf(User $user): string
    {
        return $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
    }

    private function guardSelf(User $staff, User $actor, string $field, string $action): void
    {
        if ($staff->is($actor)) {
            throw ValidationException::withMessages([
                $field => "Không thể tự {$action} tài khoản của chính mình.",
            ]);
        }
    }
}