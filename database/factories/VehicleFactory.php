<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Store;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'license_plate' => fake()->unique()->numerify('43B1-###.##'),
            'name' => 'Xe test',
            'type' => 'Tay ga',
            'price_per_hour' => 30000,
            'price_per_day' => 150000,
            'price_per_week' => 900000,
            'status' => VehicleStatus::Available,
        ];
    }
}
