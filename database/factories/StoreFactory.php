<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Cửa hàng '.fake()->unique()->numberBetween(1, 9999),
            'address' => 'Đà Nẵng',
            'latitude' => 16.06,
            'longitude' => 108.22,
        ];
    }
}
