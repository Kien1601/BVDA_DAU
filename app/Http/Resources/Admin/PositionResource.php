<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
{
    return [
        'vehicle_id' => $this->id,
        'license_plate' => $this->license_plate,
        'name' => $this->name,
        'status' => $this->status->value,
        'lat' => $this->last_lat,
        'lng' => $this->last_lng,
        'speed' => $this->latestLocation?->speed,
        'device_time' => $this->last_device_time?->toIso8601String(),
        'last_signal_at' => $this->last_signal_at?->toIso8601String(),
        'renter' => null, // Giai đoạn 4: tên khách của đơn đang active
    ];
}
}
