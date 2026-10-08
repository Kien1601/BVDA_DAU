<?php

namespace App\Support;

final class Money
{
    /** 150000 -> "150.000 đ" */
    public static function vnd(int|float|null $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . ' đ';
    }
}