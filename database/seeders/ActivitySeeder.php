<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Compact',       'icon' => '🚗'],
            ['name' => 'Sedan',         'icon' => '🚙'],
            ['name' => 'SUV',           'icon' => '🚐'],
            ['name' => 'EV Charging',   'icon' => '⚡'],
            ['name' => 'Motorcycle',    'icon' => '🏍️'],
        ];

        foreach ($categories as $cat) {
            DB::table('vehicle_categories')->updateOrInsert(
                ['name' => $cat['name']],
                $cat
            );
        }
    }
}
