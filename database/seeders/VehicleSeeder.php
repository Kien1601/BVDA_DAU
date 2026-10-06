<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = \App\Models\Store::firstOrFail();

$vehicles = [
    ['59X1-111.11', 'Honda Vision', 'Honda', 'Tay ga', '860000000000001'],
    ['59X1-222.22', 'Yamaha Exciter', 'Yamaha', 'Côn tay', '860000000000002'],
    ['59X1-333.33', 'Honda Wave', 'Honda', 'Xe số', null],
];

foreach ($vehicles as [$plate, $name, $brand, $type, $imei]) {
            \App\Models\Vehicle::updateOrCreate(
                ['license_plate' => $plate],
                [
                    'store_id' => $store->id, 'name' => $name, 'brand' => $brand, 'type' => $type,
                    'price_per_hour' => 30000, 'price_per_day' => 150000, 'price_per_week' => 900000,
                    'gps_device_id' => $imei,
                ]
            );
        }
    }
}
