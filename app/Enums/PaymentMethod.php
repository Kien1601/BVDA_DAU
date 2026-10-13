<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Vnpay = 'vnpay';
    case Momo = 'momo';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tiền mặt tại cửa hàng',
            self::Vnpay => 'VNPay',
            self::Momo => 'MoMo',
        };
    }
}