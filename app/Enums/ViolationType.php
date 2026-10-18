<?php

namespace App\Enums;

enum ViolationType: string
{
    case LateReturn = 'late_return';
    case Damage = 'damage';
    case Traffic = 'traffic';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LateReturn => 'Trả xe trễ',
            self::Damage => 'Hư hỏng xe',
            self::Traffic => 'Vi phạm giao thông',
            self::Other => 'Khác',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::LateReturn => 'warning',
            self::Damage, self::Traffic => 'danger',
            self::Other => 'neutral',
        };
    }
}