<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codes = [
            // mã,         %,    số tiền, bắt đầu,                 kết thúc,                  bật
            ['KHAITRUONG', 10,   null,    today(),                 today()->addDays(30),      true],
            ['GIAM50K',    null, 50000,   today()->addDays(7),     today()->addDays(37),      true],
            ['TET2026',    15,   null,    today()->subDays(60),    today()->subDays(30),      true],
            ['THUNGHIEM',  5,    null,    today(),                 today()->addDays(10),      false],
        ];

        foreach ($codes as [$code, $percent, $amount, $start, $end, $active]) {
            \App\Models\Promotion::updateOrCreate(['code' => $code], [
                'discount_percent' => $percent, 'discount_amount' => $amount,
                'start_date' => $start, 'end_date' => $end, 'is_active' => $active,
            ]);
        }
    }
}
