<?php

namespace App\Services\Gps;

use Illuminate\Support\Carbon;

class TraccarPayloadMapper
{
    public const KNOTS_TO_KMH = 1.852;

    /**
     * Chuyển payload Traccar thành dữ liệu chuẩn của hệ thống.
     */
    public function map(array $payload): array
    {
        $speed = (float) data_get($payload, 'position.speed', 0);

        if (config('gps.speed_unit') === 'knots') {
            $speed *= self::KNOTS_TO_KMH;
        }

        return [
            'imei' => (string) data_get($payload, 'device.uniqueId'),
            'latitude' => (float) data_get($payload, 'position.latitude'),
            'longitude' => (float) data_get($payload, 'position.longitude'),
            'speed' => round($speed, 2),
            'device_time' => Carbon::parse(data_get($payload, 'position.deviceTime'))
                ->setTimezone(config('app.timezone')),
        ];
    }
}