<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default activities
        $this->call(ActivitySeeder::class);

        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@booking.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'role' => 'Admin',
            ]
        );

        // Seed example grounds, images and terrains
        $this->call(GroundsSeeder::class);
    }
}
