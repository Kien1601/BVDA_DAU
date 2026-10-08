<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Vehicle;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Vehicle::visibleToCustomers()->with('store')->latest()->take(6)->get();

        $stats = [
            'vehicles' => Vehicle::visibleToCustomers()->count(),
            'stores' => Store::count(),
            'types' => Vehicle::visibleToCustomers()->distinct()->count('type'),
            'from' => Vehicle::visibleToCustomers()->min('price_per_day'),
        ];

        $stores = Store::withCount(['vehicles' => fn ($q) => $q->visibleToCustomers()])
            ->orderBy('name')
            ->take(4)
            ->get();

        return view('customer.home.home', compact('featured', 'stats', 'stores'));
    }
}