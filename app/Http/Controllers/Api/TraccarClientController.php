<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Gps\GpsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/** G1 - nhận vị trí từ app Traccar Client (điện thoại giả làm thiết bị GPS). */
class TraccarClientController extends Controller
{
    private const MS_TO_KNOTS = 1.943844;

    public function store(Request $request, GpsService $gps): JsonResponse
    {
        $payload = $this->toTraccarFormat($request);

               if ($payload === null) {
            // App gửi bản tin không có tọa độ (chưa bắt được GPS, chỉ có pin).
            // Trả 200 để app xóa khỏi hàng đợi, không gửi lại mãi.
            Log::info('Traccar Client: bỏ qua bản tin không có tọa độ', ['id' => $request->input('id')]);

            return response()->json(['message' => 'ignored']);
        }

        $location = $gps->handle($payload);

        if (! $location) {
            return response()->json(['message' => 'Không tìm thấy xe với mã thiết bị này'], 404);
        }

        return response()->json(['message' => 'ok', 'id' => $location->id]);
    }

    /** Đổi dữ liệu của app sang dạng Traccar Server mà GpsService đang nhận. */
    private function toTraccarFormat(Request $request): ?array
    {
        if ($request->has('location.coords')) {
            // App bản mới: JSON, tốc độ m/s
            $id    = $request->input('device_id');
            $lat   = $request->input('location.coords.latitude');
            $lng   = $request->input('location.coords.longitude');
            $knots = max(0, (float) $request->input('location.coords.speed', 0)) * self::MS_TO_KNOTS;
            $time  = $request->input('location.timestamp');
        } else {
            // App bản cũ (giao thức OsmAnd): tham số trên URL, tốc độ knot
            $id    = $request->input('id', $request->input('deviceid'));
            $lat   = $request->input('lat');
            $lng   = $request->input('lon');
            $knots = max(0, (float) $request->input('speed', 0));
            $time  = $request->input('timestamp');
        }

        if (! $id || ! is_numeric($lat) || ! is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180) {
            return null;
        }

        return [
            'device'   => ['uniqueId' => (string) $id],
            'position' => [
                'latitude'   => (float) $lat,
                'longitude'  => (float) $lng,
                'speed'      => $knots,
                'deviceTime' => $this->parseTime($time)->toIso8601String(),
            ],
        ];
    }

    /** Thời gian có thể là số giây, mili giây, hoặc chuỗi ISO. */
    private function parseTime(mixed $time): Carbon
    {
        if (is_numeric($time)) {
            $seconds = $time > 1e12 ? intdiv((int) $time, 1000) : (int) $time;

            return Carbon::createFromTimestamp($seconds);
        }

        return $time ? Carbon::parse($time) : now();
    }
}