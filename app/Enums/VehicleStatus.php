<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case Rented = 'rented';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available   => 'Sẵn sàng',
            self::Rented      => 'Đang cho thuê',
            self::Maintenance => 'Bảo dưỡng',
        };
    }

    /** Màu chấm trạng thái, khớp với component <x-status-badge> */
    public function tone(): string
    {
        return match ($this) {
            self::Available   => 'success',
            self::Rented      => 'info',
            self::Maintenance => 'warning',
        };
    }

    /** Các trạng thái admin được tự tay chọn (C2.5) */
    public static function manual(): array
    {
        return [self::Available, self::Maintenance];
    }
}