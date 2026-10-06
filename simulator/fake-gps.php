<?php

/**
 * D5 - Giả lập thiết bị GPS.
 *
 * Gửi dữ liệu đúng định dạng Traccar (forward.json) vào /api/gps/update.
 * Script đứng ngoài Laravel, giống một thiết bị thật: chỉ biết URL và token.
 *
 * Chạy:     dexec php simulator/fake-gps.php
 * Tùy chọn: --url=http://nginx/api/gps/update  --interval=15  --stop-after=6
 */

$opts      = getopt('', ['url::', 'interval::', 'stop-after::']);
$url       = $opts['url'] ?? 'http://nginx/api/gps/update';
$interval  = max(1, (int) ($opts['interval'] ?? 15));
$stopAfter = (int) ($opts['stop-after'] ?? 6);

$token = readEnvValue(__DIR__ . '/../.env', 'GPS_API_TOKEN');
if (! $token) {
    fwrite(STDERR, "Không tìm thấy GPS_API_TOKEN trong .env\n");
    exit(1);
}

$route = json_decode((string) @file_get_contents(__DIR__ . '/routes/sample-route.json'), true);
if (! is_array($route) || count($route) < 2) {
    fwrite(STDERR, "simulator/routes/sample-route.json không hợp lệ\n");
    exit(1);
}

$vehicles = [
    ['imei' => '860000000000001', 'mode' => 'moving', 'step' => 0],
    ['imei' => '860000000000002', 'mode' => 'parked', 'lat' => 10.7769, 'lng' => 106.7009, 'sent' => 0],
];

echo "Giả lập GPS -> {$url}, chu kỳ {$interval}s. Nhấn Ctrl+C để dừng.\n";

while (true) {
    foreach ($vehicles as &$v) {
        if ($v['mode'] === 'moving') {
            $from  = $route[$v['step'] % count($route)];
            $point = $route[($v['step'] + 1) % count($route)];
            $kmh   = distanceKm($from, $point) / ($interval / 3600);
            $v['step']++;
        } else {
            if ($v['sent'] >= $stopAfter) {
                continue; // xe 2 đã "mất kết nối", không gửi nữa
            }
            $point = ['lat' => $v['lat'], 'lng' => $v['lng']];
            $kmh   = 0;
            $v['sent']++;
        }

        // Traccar gửi tốc độ theo knots, nên giả lập cũng gửi knots
        $status = send($url, $token, $v['imei'], $point['lat'], $point['lng'], $kmh / 1.852);

        printf("%s  %s  %.5f, %.5f  %5.1f km/h  -> HTTP %s\n",
            date('H:i:s'), $v['imei'], $point['lat'], $point['lng'], $kmh, $status);

        if ($v['mode'] === 'parked' && $v['sent'] === $stopAfter) {
            echo "  [{$v['imei']}] ngừng gửi (giả lập mất kết nối)\n";
        }
    }
    unset($v);

    sleep($interval);
}

function send(string $url, string $token, string $imei, float $lat, float $lng, float $knots): string
{
    $payload = json_encode([
        'device'   => ['uniqueId' => $imei],
        'position' => [
            'latitude'   => $lat,
            'longitude'  => $lng,
            'speed'      => round($knots, 2),
            'deviceTime' => gmdate('Y-m-d\TH:i:s\Z'),
        ],
    ]);

    $context = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\nAuthorization: Bearer {$token}\r\n",
        'content'       => $payload,
        'timeout'       => 5,
        'ignore_errors' => true, // vẫn đọc được mã lỗi 401/404/422
    ]]);

    $result = @file_get_contents($url, false, $context);

    if ($result === false && empty($http_response_header)) {
        return 'lỗi kết nối';
    }

    preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0] ?? '', $m);

    return $m[1] ?? '???';
}

function distanceKm(array $a, array $b): float
{
    $r    = 6371; // bán kính Trái Đất (km)
    $dLat = deg2rad($b['lat'] - $a['lat']);
    $dLng = deg2rad($b['lng'] - $a['lng']);
    $h    = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;

    return 2 * $r * asin(sqrt($h));
}

function readEnvValue(string $file, string $key): ?string
{
    if (! is_file($file)) {
        return null;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if (str_starts_with($line, $key . '=')) {
            return trim(substr($line, strlen($key) + 1), " \"'");
        }
    }

    return null;
}