<?php

namespace App\Services;

use App\Enums\ViolationType;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Support\Facades\DB;

class ViolationService
{
    /** Ghi một vi phạm và cập nhật số lần vi phạm của khách */
    public function record(User $customer, ViolationType $type, array $data = [], ?User $staff = null): Violation
    {
        return DB::transaction(function () use ($customer, $type, $data, $staff) {
            $violation = Violation::create([
                'user_id' => $customer->id,
                'rental_order_id' => $data['rental_order_id'] ?? null,
                'type' => $type,
                'description' => $data['description'] ?? null,
                'fine_amount' => (int) ($data['fine_amount'] ?? 0),
                'occurred_at' => $data['occurred_at'] ?? now(),
                'recorded_by' => $staff?->id,
            ]);

            $this->syncCount($customer);

            return $violation;
        });
    }

    /** Gọi sau khi trả xe: quá giờ ân hạn thì ghi "Trả xe trễ" (mỗi đơn chỉ ghi một lần) */
    public function recordLateReturn(RentalOrder $order): ?Violation
    {
        $returnedAt = $order->actual_return_time;
        $graceEnd = $order->end_time->copy()->addMinutes(config('rental.late_grace_minutes'));

        if (! $returnedAt || $returnedAt->lte($graceEnd)) {
            return null;
        }

        if ($order->violations()->where('type', ViolationType::LateReturn)->exists()) {
            return null;
        }

        $minutes = (int) $order->end_time->diffInMinutes($returnedAt);

        return $this->record($order->user, ViolationType::LateReturn, [
            'rental_order_id' => $order->id,
            'description' => 'Trả xe trễ '.$this->duration($minutes).' so với giờ hẹn '
                .$order->end_time->format('H:i d/m/Y').'. Phụ thu đã tính vào đơn.',
            'occurred_at' => $returnedAt,
        ]);
    }

    public function delete(Violation $violation): void
    {
        DB::transaction(function () use ($violation) {
            $customer = $violation->user;
            $violation->delete();
            $this->syncCount($customer);
        });
    }

    private function syncCount(User $customer): void
    {
        $customer->forceFill(['violation_count' => $customer->violations()->count()])->save();
    }

    private function duration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return trim(($hours ? "{$hours} giờ " : '').($rest ? "{$rest} phút" : ''));
    }
}