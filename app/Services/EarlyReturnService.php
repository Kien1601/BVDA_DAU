<?php

namespace App\Services;

use App\Enums\AlertKind;
use App\Enums\OrderStatus;
use App\Models\RentalOrder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** C9.6 - Khách báo trước giờ trả sớm để cửa hàng chuẩn bị nhận xe */
class EarlyReturnService
{
    public function __construct(private OrderAlertService $alerts) {}

    public function request(RentalOrder $order, CarbonInterface $at, ?string $note = null): RentalOrder
    {
        $this->ensureActive($order);

        $min = (int) config('rental.early_return_min_minutes');
        if ($at->lt(now()->addMinutes($min))) {
            throw ValidationException::withMessages([
                'early_return_at' => "Giờ trả phải cách hiện tại ít nhất {$min} phút.",
            ]);
        }
        if ($at->gte($order->end_time)) {
            throw ValidationException::withMessages([
                'early_return_at' => 'Giờ trả sớm phải trước giờ hẹn '.$order->end_time->format('H:i d/m/Y').'. Muốn trả muộn hơn thì liên hệ cửa hàng để gia hạn.',
            ]);
        }

        $order->forceFill([
            'early_return_at' => $at,
            'early_return_note' => $note,
            'early_return_requested_at' => now(),
        ])->save();

        // Mốc là giờ khách báo: đổi giờ thì nhân viên nhận thông báo mới
        $this->alerts->notifyOnce($order->loadMissing(['user', 'vehicle']), AlertKind::EarlyReturn, $order->early_return_at);

        return $order;
    }

    public function cancel(RentalOrder $order): RentalOrder
    {
        $this->ensureActive($order);

        $order->forceFill([
            'early_return_at' => null,
            'early_return_note' => null,
            'early_return_requested_at' => null,
        ])->save();

        // Hủy rồi báo lại đúng giờ cũ thì vẫn phải báo nhân viên
        DB::table('order_reminders')
            ->where('rental_order_id', $order->id)
            ->where('kind', AlertKind::EarlyReturn->value)
            ->delete();

        return $order;
    }

    private function ensureActive(RentalOrder $order): void
    {
        if ($order->status !== OrderStatus::Active) {
            throw ValidationException::withMessages([
                'early_return_at' => 'Chỉ báo trả sớm được khi đang thuê xe.',
            ]);
        }
    }
}