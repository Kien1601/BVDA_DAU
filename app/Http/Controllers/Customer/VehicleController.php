<?php

namespace App\Http\Controllers\Customer;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    private const SORTS = ['newest', 'price_asc', 'price_desc'];

    /** B1.2 - danh sách và bộ lọc. Giá trị lọc lạ bị bỏ qua thay vì báo lỗi. */
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'type' => in_array($request->query('type'), Vehicle::TYPES, true) ? $request->query('type') : null,
            'store' => $request->integer('store') ?: null,
            'max_price' => $request->integer('max_price') ?: null,
            'sort' => in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest',
        ];

        $vehicles = Vehicle::visibleToCustomers()
            ->with('store')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $kw = '%' . $filters['q'] . '%';
                // chỉ tìm theo tên và hãng; KHÔNG tìm theo IMEI ở phía khách
                $query->where(fn ($q) => $q->where('name', 'like', $kw)->orWhere('brand', 'like', $kw));
            })
            ->when($filters['type'], fn ($q, $type) => $q->where('type', $type))
            ->when($filters['store'], fn ($q, $id) => $q->where('store_id', $id))
            ->when($filters['max_price'], fn ($q, $max) => $q->where('price_per_day', '<=', $max))
            ->when($filters['sort'] === 'price_asc', fn ($q) => $q->orderBy('price_per_day'))
            ->when($filters['sort'] === 'price_desc', fn ($q) => $q->orderByDesc('price_per_day'))
            ->when($filters['sort'] === 'newest', fn ($q) => $q->latest())
            ->paginate(9)
            ->withQueryString();

        $stores = Store::orderBy('name')->pluck('name', 'id');
        $hasFilters = $filters['q'] !== '' || $filters['type'] || $filters['store'] || $filters['max_price'];

        return view('customer.vehicle-list.vehicleList', compact('vehicles', 'filters', 'stores', 'hasFilters'));
    }

    /** B1.3 - chi tiết xe. Xe bảo dưỡng và xe đã xóa trả 404. */
    public function show(Vehicle $vehicle)
    {
        abort_if($vehicle->status === VehicleStatus::Maintenance, 404);

        $vehicle->load('store');

        $related = Vehicle::visibleToCustomers()
            ->with('store')
            ->where('type', $vehicle->type)
            ->whereKeyNot($vehicle->id)
            ->take(3)
            ->get();

        // B1.4 - chỉ tọa độ CỬA HÀNG, không bao giờ là vị trí GPS của xe
        $storeMap = [[
            'name' => $vehicle->store->name,
            'address' => $vehicle->store->address,
            'lat' => $vehicle->store->latitude,
            'lng' => $vehicle->store->longitude,
            'vehicles' => $vehicle->store->vehicles()->visibleToCustomers()->count(),
        ]];

        return view('customer.vehicle-detail.vehicleDetail', compact('vehicle', 'related', 'storeMap'));
    }
}