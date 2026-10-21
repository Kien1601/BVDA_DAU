<?php

use App\Http\Controllers\Api\GpsController;
use App\Http\Middleware\VerifyGpsToken;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TraccarClientController;

Route::post('/gps/update', [GpsController::class, 'update'])
    ->middleware([VerifyGpsToken::class, 'throttle:600,1']);
// G1 - app Traccar Client trên điện thoại
Route::match(['get', 'post'], '/gps/traccar/{token}', [TraccarClientController::class, 'store'])
    ->middleware([VerifyGpsToken::class, 'throttle:600,1']);
