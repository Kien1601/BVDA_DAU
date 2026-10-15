<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Models\Vehicle;
use App\Services\ReportService;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(ReportService $reports)
    {
        $stats = [
            // Đơn chờ duyệt còn hạn giữ chỗ (đơn quá hạn coi như đã nhả xe)
            'pending_orders' => RentalOrder::where('status', OrderStatus::Pending->value)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count(),
            'active_rentals' => RentalOrder::where('status', OrderStatus::Active->value)->count(),
            'available_vehicles' => Vehicle::where('status', 'available')->count(),
        ];

        // Doanh thu chỉ admin thấy (quyền report.view)
        $revenue = Gate::allows('report.view')
            ? [
                'today' => $reports->revenue(today(), today()),
                'month' => $reports->revenue(today()->startOfMonth(), today()),
            ]
            : null;

        $recentOrders = RentalOrder::with(['user:id,name', 'vehicle:id,name,license_plate'])
            ->latest('id')
            ->limit(8)
            ->get();

        return view('admin.dashboard.dashboard', compact('stats', 'revenue', 'recentOrders'));
    }
}