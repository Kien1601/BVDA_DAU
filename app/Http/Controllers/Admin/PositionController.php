<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PositionResource;
use App\Models\Vehicle;

class PositionController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::whereNotNull('gps_device_id')
            ->with('latestLocation')
            ->orderBy('license_plate')
            ->get();

        return PositionResource::collection($vehicles);
    }
}