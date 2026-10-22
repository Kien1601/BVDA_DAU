<?php

namespace App\Enums;

enum SenderType: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case System = 'system';
}