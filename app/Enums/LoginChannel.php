<?php

namespace App\Enums;

enum LoginChannel: string
{
    case Email = 'email';
    case Sms = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'email',
            self::Sms => 'tin nhắn SMS',
        };
    }
}