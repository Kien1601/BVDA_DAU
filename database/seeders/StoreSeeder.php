<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Store::firstOrCreate(
            ['name' => 'Cửa hàng Quận 1'],
            ['address' => 'Nguyễn Huệ, Quận 1, TP.HCM', 'latitude' => 10.7769, 'longitude' => 106.7009, 'phone' => '0280000000']
        );
    }
}
