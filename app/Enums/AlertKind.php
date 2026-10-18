<?php

namespace App\Enums;

enum AlertKind: string
{
    case PickupSoon = 'pickup_soon';
    case PickupLate = 'pickup_late';
    case ReturnSoon = 'return_soon';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::PickupSoon => 'Sắp giao xe',
            self::PickupLate => 'Khách chưa đến nhận',
            self::ReturnSoon => 'Sắp trả xe',
            self::Overdue => 'Quá hạn trả',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PickupSoon, self::ReturnSoon => 'info',
            self::PickupLate => 'warning',
            self::Overdue => 'danger',
        };
    }
}