<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\VehicleStatus;
use App\Models\RentalOrder;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FleetService
{
    /** Còn từ 1 đến 2 xe sẵn sàng thì báo "Sắp hết". */
    public const LOW_STOCK = 2;

    /** Thẻ tổng quan: tổng, sẵn sàng, đã đặt, đang thuê, bảo trì. */
    public function summary(?int $storeId = null): array
    {
        $counts = $this->vehicles($storeId)
            ->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $booked = $this->booked($this->vehicles($storeId))->count();
        $available = (int) ($counts[VehicleStatus::Available->value] ?? 0);

        return [
            'total'       => (int) $counts->sum(),
            'available'   => $available - $booked,
            'booked'      => $booked,
            'rented'      => (int) ($counts[VehicleStatus::Rented->value] ?? 0),
            'maintenance' => (int) ($counts[VehicleStatus::Maintenance->value] ?? 0),
        ];
    }

    /** Bảng theo loại xe: mỗi loại một dòng. */
    public function byType(?int $storeId = null): Collection
    {
        $bookedByType = $this->booked($this->vehicles($storeId))
            ->toBase()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return $this->vehicles($storeId)
            ->toBase()
            ->selectRaw('type, status, COUNT(*) as total')
            ->groupBy('type', 'status')
            ->get()
            ->groupBy('type')
            ->map(function ($rows, $type) use ($bookedByType) {
                $by = $rows->pluck('total', 'status');
                $total = (int) $by->sum();
                $booked = (int) ($bookedByType[$type] ?? 0);
                $available = (int) ($by[VehicleStatus::Available->value] ?? 0) - $booked;
                $rented = (int) ($by[VehicleStatus::Rented->value] ?? 0);

                return [
                    'type'        => $type,
                    'total'       => $total,
                    'available'   => $available,
                    'booked'      => $booked,
                    'rented'      => $rented,
                    'maintenance' => (int) ($by[VehicleStatus::Maintenance->value] ?? 0),
                    'usage'       => $total ? (int) round(($rented + $booked) * 100 / $total) : 0,
                    'stock'       => $available === 0 ? 'out' : ($available <= self::LOW_STOCK ? 'low' : 'ok'),
                ];
            })
            ->sortBy('type')
            ->values();
    }

    /** Xe (đã lọc cửa hàng nếu có). */
    private function vehicles(?int $storeId): Builder
    {
        return Vehicle::query()->when($storeId, fn ($q) => $q->where('store_id', $storeId));
    }

    /** Xe available nhưng đang có đơn chờ duyệt hoặc đã duyệt, chưa nhận xe. */
    private function booked(Builder $query): Builder
    {
        return $query
            ->where('status', VehicleStatus::Available->value)
            ->whereIn('id', RentalOrder::query()
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Approved->value])
                ->select('vehicle_id'));
    }
}