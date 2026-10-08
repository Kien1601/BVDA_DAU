<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Store;

class StoreController extends Controller
{
    /** B1.4 - danh sách và bản đồ cửa hàng */
    public function index()
    {
        $stores = Store::withCount(['vehicles' => fn ($q) => $q->visibleToCustomers()])
            ->orderBy('name')
            ->get();

        $mapData = $stores->map(fn (Store $store) => [
            'name' => $store->name,
            'address' => $store->address,
            'lat' => $store->latitude,
            'lng' => $store->longitude,
            'vehicles' => $store->vehicles_count,
            'url' => route('vehicles.index', ['store' => $store->id]),
        ])->values();

        return view('customer.store-location.storeLocation', compact('stores', 'mapData'));
    }
}