<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerOrderService
{
    public function __construct(private PaymentService $payments)
    {
    }

    /** Đơn của khách, mới nhất trước. Xe chỉ lấy cột công khai, không lấy cột GPS */
    public function history(User $user, ?string $status): LengthAwarePaginator
    {
        return $user->rentalOrders()
            ->with('vehicle:id,name,license_plate,type')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function countByStatus(User $user): array
    {
        return $user->rentalOrders()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /** Số liệu cho hóa đơn: các khoản đã trả, tổng cần trả, còn thiếu */
    public function invoice(RentalOrder $order): array
    {
        return [
            'number' => 'HD'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'issuedAt' => $order->actual_return_time ?? $order->updated_at,
            'paidPayments' => $order->payments()
                ->where('status', PaymentStatus::Paid->value)
                ->orderBy('paid_at')
                ->get(),
            'grandTotal' => $order->total_amount + $order->surcharge_amount,
            'paidTotal' => $order->paidAmount(),
            'amountDue' => $this->payments->amountDue($order),
        ];
    }
}