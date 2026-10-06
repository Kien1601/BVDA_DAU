<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index()
    {
        // Giai đoạn 4: thay số 0 bằng truy vấn rental_orders, vehicles, payments
        $stats = [
            'pending_orders'     => 0,
            'active_rentals'     => 0,
            'available_vehicles' => 0,
        ];

        $revenue = Gate::allows('report.view')
            ? ['today' => 0, 'month' => 0]
            : null;

        $recentOrders = collect();

        return view('admin.dashboard.dashboard', compact('stats', 'revenue', 'recentOrders'));
    }
}