<?php

use App\Http\Controllers\Api\GpsController;
use App\Http\Middleware\VerifyGpsToken;
use Illuminate\Support\Facades\Route;

Route::post('/gps/update', [GpsController::class, 'update'])
    ->middleware([VerifyGpsToken::class, 'throttle:600,1']);