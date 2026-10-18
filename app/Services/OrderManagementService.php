<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\VehicleStatus;
use App\Models\RentalOrder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    public function __construct(
        private BookingService $booking,
        private PaymentService $payments,
        private ViolationService $violations,
    ) {
    }

    /** Danh sách đơn cho nhân viên: lọc trạng thái, tìm theo mã đơn / tên, SĐT khách / biển số. */
    public function search(?string $status, ?string $keyword): LengthAwarePaginator
    {
        $keyword = trim((string) $keyword);

        return RentalOrder::query()
            ->with(['user', 'vehicle'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($q) use ($keyword) {
                    $id = ltrim($keyword, '#');
                    if (ctype_digit($id)) {
                        $q->orWhere('id', (int) $id);
                    }
                    $q->orWhereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%"))
                      ->orWhereHas('vehicle', fn ($v) => $v
                        ->where('license_plate', 'like', "%{$keyword}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /** Số đơn theo từng trạng thái, dùng cho các tab lọc. */
    public function countByStatus(): array
    {
        return RentalOrder::query()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function approve(RentalOrder $order, User $staff): RentalOrder
    {
        return DB::transaction(function () use ($order, $staff) {
            $order = $this->lock($order);
            $this->requireStatus($order, [OrderStatus::Pending], 'Chỉ duyệt được đơn đang chờ duyệt.');

            if ($order->expires_at && $order->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'status' => 'Đơn đã hết thời gian giữ chỗ, không thể duyệt.',
                ]);
            }

            $order->update([
                'status' => OrderStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $staff->id,
                'expires_at' => null,
            ]);

            return $order;
        });
    }

    public function pickUp(RentalOrder $order, User $staff): RentalOrder
    {
        return DB::transaction(function () use ($order, $staff) {
            $order = $this->lock($order);
            $this->requireStatus($order, [OrderStatus::Approved], 'Chỉ giao xe cho đơn đã duyệt.');

            $earliest = $order->start_time->copy()->subMinutes(config('rental.pickup_early_minutes'));
            if (now()->lt($earliest)) {
                throw ValidationException::withMessages([
                    'status' => 'Chưa tới giờ giao xe. Sớm nhất lúc '.$earliest->format('H:i d/m/Y').'.',
                ]);
            }
            if (now()->gte($order->end_time)) {
                throw ValidationException::withMessages([
                    'status' => 'Đơn đã quá giờ trả, không thể giao xe. Hãy hủy đơn.',
                ]);
            }

            $vehicle = Vehicle::whereKey($order->vehicle_id)->lockForUpdate()->firstOrFail();
            if ($vehicle->status !== VehicleStatus::Available) {
                throw ValidationException::withMessages([
                    'status' => 'Xe đang không sẵn sàng (đang cho thuê hoặc bảo trì).',
                ]);
            }

            $vehicle->update(['status' => VehicleStatus::Rented]);
            $order->update([
                'status' => OrderStatus::Active,
                'picked_up_at' => now(),
                'picked_up_by' => $staff->id,
            ]);

            return $order;
        });
    }

    public function extend(RentalOrder $order, Carbon $newEnd): RentalOrder
    {
        return DB::transaction(function () use ($order, $newEnd) {
            $order = $this->lock($order);
            $this->requireStatus(
                $order,
                [OrderStatus::Approved, OrderStatus::Active],
                'Chỉ gia hạn được đơn đã duyệt hoặc đang thuê.'
            );

            if ($newEnd->lte($order->end_time)) {
                throw ValidationException::withMessages([
                    'end_time' => 'Giờ trả mới phải sau giờ trả hiện tại.',
                ]);
            }

            // Khóa dòng xe như BookingService::create để không bị đặt chen vào cùng lúc
            $vehicle = Vehicle::whereKey($order->vehicle_id)->lockForUpdate()->firstOrFail();

            // Không dùng quote(): giờ nhận của đơn đã duyệt/đang thuê có thể đã qua.
            // Chỉ cần khoảng kéo dài thêm không trùng đơn khác. Giữ nguyên số tiền giảm cũ.
            $this->booking->assertAvailable($vehicle, $order->end_time, $newEnd, $order->id);
            $subtotal = $this->booking->rentFor($vehicle, $order->start_time, $newEnd)['amount'];

            $order->update([
                'original_end_time' => $order->original_end_time ?? $order->end_time,
                'end_time' => $newEnd,
                'subtotal_amount' => $subtotal,
                'total_amount' => max(0, $subtotal - (int) $order->discount_amount),
            ]);

            return $order;
        });
    }

    public function complete(RentalOrder $order, User $staff, int $extraFee = 0, ?string $note = null): RentalOrder
    {
        return DB::transaction(function () use ($order, $staff, $extraFee, $note) {
            $order = $this->lock($order);
            $this->requireStatus($order, [OrderStatus::Active], 'Chỉ trả xe cho đơn đang thuê.');

            $returnedAt = now();
            $lateFee = $this->lateFee($order, $returnedAt);

            $vehicle = Vehicle::whereKey($order->vehicle_id)->lockForUpdate()->firstOrFail();
            if ($vehicle->status === VehicleStatus::Rented) {
                $vehicle->update(['status' => VehicleStatus::Available]);
            }

            $order->update([
                'status' => OrderStatus::Completed,
                'actual_return_time' => $returnedAt,
                'returned_by' => $staff->id,
                'surcharge_amount' => $lateFee + $extraFee,
                'surcharge_note' => $note,
            ]);
            $this->violations->recordLateReturn($order);
            return $order;
        });
    }

    public function cancelByStaff(RentalOrder $order, User $staff, string $reason): RentalOrder
    {
        return DB::transaction(function () use ($order, $staff, $reason) {
            $order = $this->lock($order);
            $this->requireStatus(
                $order,
                [OrderStatus::Pending, OrderStatus::Approved],
                'Chỉ hủy được đơn chờ duyệt hoặc đã duyệt. Đơn đang thuê phải làm thủ tục trả xe.'
            );

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'cancelled_by' => $staff->id,
            ]);
            $this->payments->handleCancelled($order, $staff, $reason);
            return $order;
        });
    }

    /** Phụ thu trả trễ: quá thời gian ân hạn thì tính tiền thuê cho khoảng trễ. */
    public function lateFee(RentalOrder $order, Carbon $returnedAt): int
    {
        $graceEnd = $order->end_time->copy()->addMinutes(config('rental.late_grace_minutes'));

        if ($returnedAt->lte($graceEnd)) {
            return 0;
        }

        return $this->booking->rentFor($order->vehicle, $order->end_time, $returnedAt)['amount'];
    }

    private function lock(RentalOrder $order): RentalOrder
    {
        return RentalOrder::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
    }

    private function requireStatus(RentalOrder $order, array $allowed, string $message): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}