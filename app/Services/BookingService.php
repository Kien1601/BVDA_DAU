<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\VehicleStatus;
use App\Models\Promotion;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * B2 - Đặt thuê xe. Mọi quy tắc nghiệp vụ nằm ở đây; controller chỉ gọi lại.
 * Lỗi nghiệp vụ được ném dạng ValidationException theo tên trường, nên:
 *   - ở form thường: Laravel tự quay lại trang và hiện lỗi dưới đúng ô;
 *   - ở API báo giá (B2-2): tự trả về JSON 422.
 */
class BookingService
{
    /** B2.2 - tiền thuê theo giờ / ngày / tuần, có trần có lợi cho khách */
    public function rentFor(Vehicle $vehicle, CarbonInterface $start, CarbonInterface $end): array
    {
        $hours = max(1, (int) ceil($start->diffInMinutes($end) / 60));   // lẻ phút làm tròn lên 1 giờ

        $weeks = intdiv($hours, 168);
        $rest = $hours % 168;
        $days = intdiv($rest, 24);
        $remainingHours = $rest % 24;

        $hoursCost = min($remainingHours * $vehicle->price_per_hour, $vehicle->price_per_day); // giờ lẻ ≤ 1 ngày
        $restCost = min($days * $vehicle->price_per_day + $hoursCost, $vehicle->price_per_week); // ngày lẻ ≤ 1 tuần

        return [
            'hours' => $hours,
            'weeks' => $weeks,
            'days' => $days,
            'remaining_hours' => $remainingHours,
            'amount' => $weeks * $vehicle->price_per_week + $restCost,
        ];
    }

    /** B2.2 - tiền cọc theo config/rental.php */
    public function depositFor(int $total): int
    {
        $raw = (int) ceil($total * config('rental.deposit_percent') / 100);
        $step = (int) config('rental.deposit_round');
        $rounded = (int) (ceil($raw / $step) * $step);

        return max($rounded, (int) config('rental.deposit_min'));
    }

    /**
     * B2.1 + B2.2 + B2.3 - kiểm tra đặt được không và báo giá đầy đủ.
     * $ignoreOrderId: dùng khi gia hạn (C5.3), để đơn không tự trùng với chính nó.
     */
    public function quote(
        Vehicle $vehicle,
        CarbonInterface $start,
        CarbonInterface $end,
        ?string $promotionCode = null,
        ?int $ignoreOrderId = null,
    ): array {
        $this->assertBookable($vehicle);
        $this->assertTimeRange($start, $end);
        $this->assertAvailable($vehicle, $start, $end, $ignoreOrderId);

        $rent = $this->rentFor($vehicle, $start, $end);
        $promotion = $this->resolvePromotion($promotionCode);
        $discount = $promotion?->discountFor($rent['amount']) ?? 0;
        $total = $rent['amount'] - $discount;

        return $rent + [
            'subtotal' => $rent['amount'],
            'discount' => $discount,
            'total' => $total,
            'deposit' => $this->depositFor($total),
            'promotion' => $promotion,
        ];
    }

    /** B2.4 - tạo đơn chờ duyệt, giữ chỗ theo config('rental.hold_minutes') */
    public function create(
        User $customer,
        Vehicle $vehicle,
        CarbonInterface $start,
        CarbonInterface $end,
        ?string $promotionCode = null,
        ?string $note = null,
    ): RentalOrder {
        return DB::transaction(function () use ($customer, $vehicle, $start, $end, $promotionCode, $note) {
            // Khóa dòng xe: hai khách bấm đặt cùng lúc thì người sau phải chờ người trước
            // ghi xong, rồi mới kiểm tra trùng lịch → không thể có hai đơn trùng giờ.
            $locked = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();

            $quote = $this->quote($locked, $start, $end, $promotionCode);

            return RentalOrder::create([
                'user_id' => $customer->id,
                'vehicle_id' => $locked->id,
                'promotion_id' => $quote['promotion']?->id,
                'start_time' => $start,
                'end_time' => $end,
                'status' => OrderStatus::Pending,
                'subtotal_amount' => $quote['subtotal'],
                'discount_amount' => $quote['discount'],
                'total_amount' => $quote['total'],
                'deposit_amount' => $quote['deposit'],
                'expires_at' => now()->addMinutes((int) config('rental.hold_minutes')),
                'note' => $note,
            ]);
        });
    }

    /** B2.5 - khách hủy đơn của mình */
    public function cancelByCustomer(RentalOrder $order, ?string $reason = null): RentalOrder
    {
        if (! $order->status->cancellableByCustomer()) {
            throw ValidationException::withMessages([
                'order' => 'Đơn đang thuê hoặc đã kết thúc, không thể hủy.',
            ]);
        }

        $order->forceFill([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason ?: 'Khách hủy',
        ])->save();

        // TODO (B3): nếu đã thanh toán online thì tạo bản ghi hoàn tiền (payments.type = refund)

        return $order;
    }

    /**
     * Giữ chỗ 30 phút: hủy các đơn chờ duyệt đã quá hạn.
     * Việc kiểm tra trùng lịch đã bỏ qua đơn quá hạn ngay lập tức (scopeBlocking),
     * lệnh này chỉ để trạng thái trong CSDL khớp với thực tế.
     */
    public function expirePending(): int
    {
        return RentalOrder::query()
            ->where('status', OrderStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => OrderStatus::Cancelled->value,
                'cancelled_at' => now(),
                'cancel_reason' => 'Quá hạn giữ chỗ',
                'updated_at' => now(),
            ]);
    }

    /* ------------------------------------------------------------- kiểm tra */

    private function assertBookable(Vehicle $vehicle): void
    {
        if ($vehicle->trashed() || $vehicle->status === VehicleStatus::Maintenance) {
            throw ValidationException::withMessages(['vehicle' => 'Xe này hiện không nhận đặt thuê.']);
        }
    }

    private function assertTimeRange(CarbonInterface $start, CarbonInterface $end): void
    {
        if ($end->lte($start)) {
            throw ValidationException::withMessages(['end_time' => 'Thời gian trả xe phải sau thời gian nhận xe.']);
        }

        $lead = (int) config('rental.min_lead_minutes');
        if ($start->lt(now()->addMinutes($lead))) {
            throw ValidationException::withMessages([
                'start_time' => "Thời gian nhận xe phải cách hiện tại ít nhất {$lead} phút.",
            ]);
        }

        $maxDays = (int) config('rental.max_days');
        if ($start->diffInDays($end) > $maxDays) {
            throw ValidationException::withMessages(['end_time' => "Mỗi đơn thuê tối đa {$maxDays} ngày."]);
        }
    }

    private function assertAvailable(Vehicle $vehicle, CarbonInterface $start, CarbonInterface $end, ?int $ignoreOrderId): void
    {
        $taken = RentalOrder::query()
            ->where('vehicle_id', $vehicle->id)
            ->blocking()
            ->overlapping($start, $end)
            ->when($ignoreOrderId, fn ($q) => $q->whereKeyNot($ignoreOrderId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'start_time' => 'Xe đã có người đặt trong khoảng thời gian này. Vui lòng chọn thời gian hoặc xe khác.',
            ]);
        }
    }

    private function resolvePromotion(?string $code): ?Promotion
    {
        $code = mb_strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $promotion = Promotion::where('code', $code)->first();

        if (! $promotion || ! $promotion->isUsableOn(now())) {
            throw ValidationException::withMessages([
                'promotion_code' => 'Mã khuyến mãi không tồn tại hoặc đã hết hạn.',
            ]);
        }

        return $promotion;
    }
}