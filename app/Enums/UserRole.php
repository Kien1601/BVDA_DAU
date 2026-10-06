<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Admin = 'admin';

    public function isManager(): bool
    {
        return in_array($this, [self::Staff, self::Admin], true);
    }
}