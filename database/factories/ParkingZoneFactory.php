<?php

namespace Database\Factories;

use App\Models\ParkingZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParkingZone>
 */
class ParkingZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_admin' => User::factory()->admin(),
            'name' => fake()->company() . ' Parking',
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'description' => fake()->paragraph(),
            'latitude' => fake()->latitude(27.5, 35.9),
            'longitude' => fake()->longitude(-13.2, -1.0),
            'total_spots' => 0,
        ];
    }
}
