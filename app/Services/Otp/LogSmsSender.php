<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

/** SMS giả lập: chỉ ghi vào storage/logs/laravel.log, không gửi thật */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::info("[SMS giả lập] Gửi tới {$phone}: {$message}");
    }
}