<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VehicleRequest;
use App\Models\Store;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q'      => trim((string) $request->query('q')),
            'store'  => $request->query('store'),
            'status' => $request->query('status'),
        ];

        $vehicles = Vehicle::query()
            ->with('store')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $kw = '%' . $filters['q'] . '%';
                $query->where(fn ($q) => $q
                    ->where('license_plate', 'like', $kw)
                    ->orWhere('name', 'like', $kw)
                    ->orWhere('gps_device_id', 'like', $kw));
            })
            ->when($filters['store'], fn ($q, $id) => $q->where('store_id', $id))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->orderBy('license_plate')
            ->paginate(10)
            ->withQueryString();

        return view('admin.vehicle-management.vehicleList', [
            'vehicles' => $vehicles,
            'filters'  => $filters,
            'stores'   => Store::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('admin.vehicle-management.vehicleCreate', [
            'vehicle' => new Vehicle(),
            'stores'  => Store::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(VehicleRequest $request)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('vehicles', 'public');
        }

        Vehicle::create($data);

        return redirect()->route('admin.vehicles.index')->with('success', 'Đã thêm xe.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('admin.vehicle-management.vehicleEdit', [
            'vehicle' => $vehicle,
            'stores'  => Store::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(VehicleRequest $request, Vehicle $vehicle)
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $old = $vehicle->image;
            $data['image'] = $request->file('image')->store('vehicles', 'public');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        $vehicle->update($data);

        return redirect()->route('admin.vehicles.index')->with('success', 'Đã cập nhật xe ' . $vehicle->license_plate . '.');
    }

    public function destroy(Vehicle $vehicle)
    {
        if (! $vehicle->canBeDeleted()) {
            return back()->with('error', 'Không xóa được: xe ' . $vehicle->license_plate . ' đang cho thuê.');
        }

        // Gỡ thiết bị trước khi xóa mềm, để IMEI gắn được sang xe khác
        $vehicle->update(['gps_device_id' => null]);
        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Đã xóa xe ' . $vehicle->license_plate . '. Lịch sử thuê của xe vẫn được giữ lại.');
    }
}