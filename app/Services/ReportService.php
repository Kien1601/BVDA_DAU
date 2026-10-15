<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\RentalOrder;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /** Doanh thu thực thu trong kỳ: tiền vào trừ tiền hoàn */
    public function revenue(CarbonInterface $from, CarbonInterface $to): int
    {
        $row = $this->paidBetween($from, $to)
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN 0 ELSE amount END), 0) as money_in', [PaymentType::Refund->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as money_out', [PaymentType::Refund->value])
            ->first();

        return (int) $row->money_in - (int) $row->money_out;
    }

    /** Doanh thu từng ngày, đủ mọi ngày trong kỳ (ngày không có tiền = 0) */
    public function revenueByDay(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = $this->paidBetween($from, $to)
            ->selectRaw('DATE(paid_at) as day')
            ->selectRaw('SUM(CASE WHEN type = ? THEN 0 ELSE amount END) as money_in', [PaymentType::Refund->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as money_out', [PaymentType::Refund->value])
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = [];
        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            $key = $date->toDateString();
            $row = $rows->get($key);
            $days[$key] = $row ? (int) $row->money_in - (int) $row->money_out : 0;
        }

        return $days;
    }

    /** Đơn tạo trong kỳ theo trạng thái, và số lượt giao xe trong kỳ */
    public function orderStats(CarbonInterface $from, CarbonInterface $to): array
    {
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $counts = RentalOrder::query()
            ->toBase()
            ->whereBetween('created_at', $range)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'byStatus' => $counts,
            'newOrders' => array_sum($counts),
            'rentals' => RentalOrder::whereBetween('picked_up_at', $range)->count(),
        ];
    }

    /** Xe được giao nhiều nhất trong kỳ */
    public function topVehicles(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection
    {
        return RentalOrder::query()
            ->toBase()
            ->join('vehicles', 'vehicles.id', '=', 'rental_orders.vehicle_id')
            ->whereBetween('rental_orders.picked_up_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('vehicles.id', 'vehicles.name', 'vehicles.license_plate', 'vehicles.type')
            ->select('vehicles.id', 'vehicles.name', 'vehicles.license_plate', 'vehicles.type')
            ->selectRaw('COUNT(*) as rentals')
            ->selectRaw('SUM(rental_orders.total_amount + rental_orders.surcharge_amount) as amount')
            ->orderByDesc('rentals')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();
    }

    private function paidBetween(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Payment::query()
            ->toBase()
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }
}