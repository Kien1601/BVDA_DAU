<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerService
{
    /** Từ số lần vi phạm này trở lên thì hiện cảnh báo */
    public const WARN_VIOLATIONS = 3;

    public function search(?string $filter, ?string $keyword): LengthAwarePaginator
    {
        $keyword = trim((string) $keyword);

        return User::query()
            ->where('role', 'customer')
            ->withCount('rentalOrders')
            ->when($filter === 'locked', fn ($q) => $q->where('is_active', false))
            ->when($filter === 'violating', fn ($q) => $q->where('violation_count', '>', 0))
            ->when($keyword !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('id_card_number', 'like', "%{$keyword}%")))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /** Số khách cho các tab lọc */
    public function counts(): array
    {
        $base = User::where('role', 'customer');

        return [
            'all' => (clone $base)->count(),
            'locked' => (clone $base)->where('is_active', false)->count(),
            'violating' => (clone $base)->where('violation_count', '>', 0)->count(),
        ];
    }

    public function summary(User $customer): array
    {
        $orderIds = $customer->rentalOrders()->select('id');
        $paid = Payment::whereIn('rental_order_id', $orderIds)->where('status', PaymentStatus::Paid);

        $in = (clone $paid)->whereIn('type', [PaymentType::Deposit, PaymentType::Balance])->sum('amount');
        $out = (clone $paid)->where('type', PaymentType::Refund)->sum('amount');

        return [
            'orders' => $customer->rentalOrders()->count(),
            'completed' => $customer->rentalOrders()->where('status', OrderStatus::Completed)->count(),
            'cancelled' => $customer->rentalOrders()->where('status', OrderStatus::Cancelled)->count(),
            'spent' => (int) $in - (int) $out,
            'fines' => (int) $customer->violations()->sum('fine_amount'),
        ];
    }

    /** Khóa thì đăng xuất khách khỏi mọi thiết bị; OTP đã tự chặn đăng nhập lại */
    public function setActive(User $customer, bool $active): void
    {
        $customer->forceFill(['is_active' => $active]);

        if (! $active) {
            $customer->forceFill(['remember_token' => Str::random(60)]);

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $customer->id)->delete();
            }
        }

        $customer->save();
    }
}