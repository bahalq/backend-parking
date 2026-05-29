<?php

namespace Database\Factories;

use App\Models\OccupancyLog;
use App\Models\ParkingSpot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OccupancyLog>
 */
class OccupancyLogFactory extends Factory
{
    public function definition(): array
    {
        $spot = ParkingSpot::factory()->create();

        return [
            'parking_spot_id' => $spot->id,
            'parking_zone_id' => $spot->parking_zone_id,
            'vehicle_plate' => strtoupper(fake()->bothify('####-?-##')),
            'action' => fake()->randomElement(['Entry', 'Exit']),
            'timestamp' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
