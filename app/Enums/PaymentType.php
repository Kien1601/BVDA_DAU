<?php

namespace App\Enums;

enum PaymentType: string
{
    case Deposit = 'deposit';   // tiền cọc
    case Balance = 'balance';   // phần còn lại + phụ thu, thu khi trả xe
    case Refund = 'refund';     // hoàn tiền

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Tiền cọc',
            self::Balance => 'Thanh toán còn lại',
            self::Refund => 'Hoàn tiền',
        };
    }
}