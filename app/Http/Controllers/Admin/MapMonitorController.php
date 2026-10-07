<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;

class MapMonitorController extends Controller
{
    public function index()
    {
        // Tâm bản đồ ban đầu: vị trí cửa hàng đầu tiên (mặc định là trung tâm TP.HCM)
        $store = Store::first();

        $center = [
            'lat' => $store?->latitude ?? 10.7769,
            'lng' => $store?->longitude ?? 106.7009,
        ];

        return view('admin.gps-monitor.monitor', compact('center'));
    }
}