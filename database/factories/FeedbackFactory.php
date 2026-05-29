<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\ParkingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rating' => fake()->numberBetween(3, 5),
            'message' => fake()->sentence(14),
            'name' => fake()->name(),
            'ground_id' => ParkingZone::factory(),
        ];
    }
}
