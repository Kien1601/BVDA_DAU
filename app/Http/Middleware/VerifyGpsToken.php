<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyGpsToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('gps.api_token');
        $given = $request->bearerToken() ?? $request->header('X-GPS-Token');

        // hash_equals: so sánh an toàn, chống đoán token qua thời gian phản hồi
        if (! $expected || ! is_string($given) || ! hash_equals($expected, $given)) {
            Log::warning('GPS: token không hợp lệ', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Token không hợp lệ'], 401);
        }

        return $next($request);
    }
}