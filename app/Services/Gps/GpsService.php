<?php

namespace App\Services\Gps;

use App\Models\GpsLocation;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\VehicleLocationUpdated;

class GpsService
{
    public function __construct(private TraccarPayloadMapper $mapper)
    {
    }

    /**
     * D2 + D3: tìm xe theo IMEI, lưu lịch sử, cập nhật vị trí cuối.
     * Trả về null nếu IMEI không khớp xe nào.
     */
   public function handle(array $payload): ?GpsLocation
{
    $data = $this->mapper->map($payload);

    $vehicle = Vehicle::where('gps_device_id', $data['imei'])->first();

    if (! $vehicle) {
        Log::warning('GPS: IMEI không khớp xe nào', ['imei' => $data['imei']]);

        return null;
    }

    $receivedAt = now();

    [$location, $isNewest] = DB::transaction(function () use ($vehicle, $data, $receivedAt) {
        $location = $vehicle->gpsLocations()->create([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'speed' => $data['speed'],
            'device_time' => $data['device_time'],
            'received_at' => $receivedAt,
        ]);

        $vehicle->last_signal_at = $receivedAt;

        $isNewest = ! $vehicle->last_device_time
            || $data['device_time']->greaterThanOrEqualTo($vehicle->last_device_time);

        if ($isNewest) {
            $vehicle->last_lat = $data['latitude'];
            $vehicle->last_lng = $data['longitude'];
            $vehicle->last_device_time = $data['device_time'];
        }

        $vehicle->save();

        return [$location, $isNewest];
    });

    // D4: phát sau khi transaction đã lưu xong.
    // Gói tin đến muộn thì không phát, để marker không bị nhảy lùi về vị trí cũ.
    if ($isNewest) {
        VehicleLocationUpdated::dispatch([
            'vehicle_id' => $vehicle->id,
            'license_plate' => $vehicle->license_plate,
            'lat' => $location->latitude,
            'lng' => $location->longitude,
            'speed' => $location->speed,
            'device_time' => $location->device_time->toIso8601String(),
            'last_signal_at' => $receivedAt->toIso8601String(),
        ]);
    }

    return $location;
}
}