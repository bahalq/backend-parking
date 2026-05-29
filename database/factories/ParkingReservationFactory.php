<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\ParkingReservation;
use App\Models\ParkingSpot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ParkingReservation>
 */
class ParkingReservationFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(8, 20);
        $duration = fake()->numberBetween(1, 3);

        return [
            'parking_spot_id' => ParkingSpot::factory(),
            'driver_id' => Driver::factory(),
            'date' => fake()->dateTimeBetween('-14 days', '+7 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', min(22, $startHour + $duration)),
            'total_price' => fake()->randomFloat(2, 10, 120),
            'status' => fake()->randomElement(['Pending', 'Confirmed', 'Completed', 'Cancelled']),
            'reference' => 'PRK-' . strtoupper(Str::random(8)),
            'verification_code' => fake()->numerify('######'),
            'confirmed_at' => null,
        ];
    }
}
