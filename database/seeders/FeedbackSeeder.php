<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feedback;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        Feedback::insert([
            [
                'rating' => 5,
                'message' => 'Excellent service ! La réservation était simple et rapide. La place avec chargeur électrique était libre et prête.',
                'name' => 'Karim B.',
                'ground_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rating' => 4,
                'message' => 'Très bonne expérience, le guidage en temps réel vers ma place de stationnement était super précis. Je recommande !',
                'name' => 'Anonyme',
                'ground_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rating' => 5,
                'message' => 'Application facile à utiliser, validation du ticket QR instantanée par le staff à l\'entrée. Plus besoin de chercher sa place pendant des heures !',
                'name' => 'Youssef M.',
                'ground_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}