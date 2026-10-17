<?php

namespace App\Services\Otp;

/** Gửi tin nhắn SMS. Đổi sang nhà cung cấp thật: viết lớp mới cài interface này rồi đổi bind trong AppServiceProvider */
interface SmsSender
{
    public function send(string $phone, string $message): void;
}