<?php

namespace Database\Factories;

use App\Models\ParkingZone;
use App\Models\TrafficAnalytic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficAnalytic>
 */
class TrafficAnalyticFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parking_zone_id' => ParkingZone::factory(),
            'hour_of_day' => fake()->numberBetween(0, 23),
            'day_of_week' => fake()->numberBetween(0, 6),
            'vehicle_count' => fake()->numberBetween(0, 40),
            'average_stay_duration_minutes' => fake()->numberBetween(35, 180),
            'occupancy_rate' => fake()->randomFloat(2, 5, 95),
        ];
    }
}
