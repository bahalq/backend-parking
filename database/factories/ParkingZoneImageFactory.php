<?php

namespace Database\Factories;

use App\Models\ParkingZone;
use App\Models\ParkingZoneImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParkingZoneImage>
 */
class ParkingZoneImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parking_zone_id' => ParkingZone::factory(),
            'image' => fake()->randomElement(['casablanca-marina.jpg', 'rabat-agdal.jpg', 'marrakech-gueliz.jpg']),
        ];
    }
}
