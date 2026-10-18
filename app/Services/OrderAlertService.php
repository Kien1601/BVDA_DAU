<?php

namespace App\Services;

use App\Enums\AlertKind;
use App\Enums\OrderStatus;
use App\Models\RentalOrder;
use App\Models\User;
use App\Notifications\OrderAlert;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrderAlertService
{
    public function scan(): int
    {
        $now = now();
        $sent = 0;
        $send = function (RentalOrder $order, AlertKind $kind, CarbonInterface $dueAt) use (&$sent) {
            $sent += $this->notifyOnce($order, $kind, $dueAt);
        };

        $this->orders(OrderStatus::Approved)
            ->whereBetween('start_time', [$now, $now->copy()->addMinutes(config('rental.alert_pickup_before_minutes'))])
            ->each(fn ($o) => $send($o, AlertKind::PickupSoon, $o->start_time));

        $this->orders(OrderStatus::Approved)
            ->where('start_time', '<=', $now->copy()->subMinutes(config('rental.alert_pickup_late_minutes')))
            ->where('end_time', '>', $now)
            ->each(fn ($o) => $send($o, AlertKind::PickupLate, $o->start_time));

        $this->orders(OrderStatus::Active)
            ->whereBetween('end_time', [$now, $now->copy()->addMinutes(config('rental.alert_return_before_minutes'))])
            ->each(fn ($o) => $send($o, AlertKind::ReturnSoon, $o->end_time));

        $this->orders(OrderStatus::Active)
            ->where('end_time', '<', $now->copy()->subMinutes(config('rental.late_grace_minutes')))
            ->each(fn ($o) => $send($o, AlertKind::Overdue, $o->end_time));

        return $sent;
    }

    public function notifyOnce(RentalOrder $order, AlertKind $kind, CarbonInterface $dueAt): int
    {
        $inserted = DB::table('order_reminders')->insertOrIgnore([
            'rental_order_id' => $order->id,
            'kind' => $kind->value,
            'due_at' => $dueAt,
            'sent_at' => now(),
        ]);

        if ($inserted === 0) {
            return 0;
        }

        Notification::send($this->recipients(), new OrderAlert($order, $kind));

        return 1;
    }

    public function recipients(): Collection
    {
        return User::whereIn('role', ['staff', 'admin'])->where('is_active', true)->get();
    }

    private function orders(OrderStatus $status)
    {
        return RentalOrder::with(['user', 'vehicle'])->where('status', $status);
    }
}