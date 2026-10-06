<?php

return [
    'api_token' => env('GPS_API_TOKEN'),
    'speed_unit' => env('GPS_SPEED_UNIT', 'knots'), // Traccar gửi tốc độ theo knots
    'retention_days' => (int) env('GPS_RETENTION_DAYS', 90),
];