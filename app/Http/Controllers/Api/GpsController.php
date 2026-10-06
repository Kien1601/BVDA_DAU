<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GpsUpdateRequest;
use App\Services\Gps\GpsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GpsController extends Controller
{
    public function update(GpsUpdateRequest $request, GpsService $gps): JsonResponse
    {
        Log::debug('GPS payload thô', $request->all());

        $location = $gps->handle($request->validated());

        if (! $location) {
            return response()->json(['message' => 'Không tìm thấy xe với IMEI này'], 404);
        }

        return response()->json(['message' => 'ok', 'id' => $location->id], 201);
    }
}