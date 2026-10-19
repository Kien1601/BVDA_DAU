<?php

namespace App\Enums;

enum AlertKind: string
{
    case PickupSoon = 'pickup_soon';
    case PickupLate = 'pickup_late';
    case ReturnSoon = 'return_soon';
    case Overdue = 'overdue';
    case NewBooking = 'new_booking';
    case DepositPaid = 'deposit_paid';
    case CustomerCancelled = 'customer_cancelled';
    case EarlyReturn = 'early_return';

    public function label(): string
    {
        return match ($this) {
            self::PickupSoon => 'Sắp giao xe',
            self::PickupLate => 'Khách chưa đến nhận',
            self::ReturnSoon => 'Sắp trả xe',
            self::Overdue => 'Quá hạn trả',
            self::NewBooking => 'Đơn mới',
            self::DepositPaid => 'Đã thanh toán cọc',
            self::CustomerCancelled => 'Khách hủy đơn',
            self::EarlyReturn => 'Khách báo trả sớm',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PickupSoon, self::ReturnSoon => 'info',
            self::PickupLate, self::EarlyReturn => 'warning',
            self::Overdue => 'danger',
            self::NewBooking, self::DepositPaid => 'success',
            self::CustomerCancelled => 'warning',
            
        };
    }
}