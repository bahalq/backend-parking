<?php

namespace Database\Factories;

use App\Models\ParkingSpot;
use App\Models\ParkingZone;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParkingSpot>
 */
class ParkingSpotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parking_zone_id' => ParkingZone::factory(),
            'vehicle_category_id' => VehicleCategory::factory(),
            'name' => strtoupper(fake()->bothify('??-##')),
            'type' => fake()->randomElement(['Standard', 'EV Charging', 'Disabled Access', 'Motorcycle', 'Large Vehicle']),
            'status' => fake()->randomElement(['Available', 'Occupied', 'Reserved', 'Maintenance']),
            'price_per_hour' => fake()->randomFloat(2, 6, 35),
        ];
    }
}
