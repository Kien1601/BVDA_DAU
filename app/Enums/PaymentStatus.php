<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ thanh toán',
            self::Paid => 'Đã thanh toán',
            self::Failed => 'Thất bại',
            self::Cancelled => 'Đã hủy',
        };
    }

    /** Màu chấm của <x-status-badge>, cùng bộ màu với OrderStatus */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'neutral',
        };
    }
}