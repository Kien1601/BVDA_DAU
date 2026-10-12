<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Chờ duyệt',
            self::Approved  => 'Đã duyệt',
            self::Active    => 'Đang thuê',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    /** Màu chấm của <x-status-badge> */
    public function tone(): string
    {
        return match ($this) {
            self::Pending   => 'warning',
            self::Approved  => 'info',
            self::Active    => 'success',
            self::Completed => 'neutral',
            self::Cancelled => 'danger',
        };
    }

    /** Các trạng thái đang giữ xe (B2.1) */
    public static function blocking(): array
    {
        return [self::Pending, self::Approved, self::Active];
    }

    /** B2.5 - khách tự hủy được */
    public function cancellableByCustomer(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }
}