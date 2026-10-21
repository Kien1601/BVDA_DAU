<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\FleetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FleetController extends Controller
{
    public function __construct(private FleetService $fleet)
    {
    }

    /** C10 - Tình trạng đội xe: thẻ tổng quan + bảng theo loại, lọc theo cửa hàng. */
    public function index(Request $request): View
    {
        $storeId = $request->integer('store_id') ?: null;

        return view('admin.fleet-management.fleetOverview', [
            'summary' => $this->fleet->summary($storeId),
            'types'   => $this->fleet->byType($storeId),
            'stores'  => Store::orderBy('name')->pluck('name', 'id'),
            'storeId' => $storeId,
        ]);
    }
}