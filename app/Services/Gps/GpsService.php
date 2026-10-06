<?php

namespace App\Services\Gps;

use App\Models\GpsLocation;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        return DB::transaction(function () use ($vehicle, $data, $receivedAt) {
            $location = $vehicle->gpsLocations()->create([
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'speed' => $data['speed'],
                'device_time' => $data['device_time'],
                'received_at' => $receivedAt,
            ]);

            // Luôn cập nhật: thiết bị vẫn đang gửi tín hiệu
            $vehicle->last_signal_at = $receivedAt;

            // Chỉ ghi đè vị trí cuối nếu gói tin này mới hơn (bỏ qua gói gửi bù đến muộn)
            $isNewest = ! $vehicle->last_device_time
                || $data['device_time']->greaterThanOrEqualTo($vehicle->last_device_time);

            if ($isNewest) {
                $vehicle->last_lat = $data['latitude'];
                $vehicle->last_lng = $data['longitude'];
                $vehicle->last_device_time = $data['device_time'];
            }

            $vehicle->save();

            // D4 (phát event real-time) sẽ thêm ở phần 3D

            return $location;
        });
    }
}