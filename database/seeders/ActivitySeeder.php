<?php

namespace Database\Seeders;

use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Compact', 'icon' => 'car'],
            ['name' => 'Sedan', 'icon' => 'sedan'],
            ['name' => 'SUV', 'icon' => 'suv'],
            ['name' => 'EV', 'icon' => 'ev'],
            ['name' => 'Motorcycle', 'icon' => 'moto'],
            ['name' => 'Accessible', 'icon' => 'access'],
        ];

        foreach ($categories as $category) {
            VehicleCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
