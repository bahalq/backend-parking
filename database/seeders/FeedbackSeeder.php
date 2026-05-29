<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\ParkingZone;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $zones = ParkingZone::pluck('id', 'name');

        $reviews = [
            ['Casablanca Marina Smart Parking', 5, 'Karim B.', 'Reservation rapide, place EV libre a l arrivee et equipe tres professionnelle.'],
            ['Casablanca Marina Smart Parking', 4, 'Sara B.', 'Bon emplacement pour Casa Port, tarifs clairs et sortie fluide meme en fin de journee.'],
            ['Rabat Agdal Station Parking', 5, 'Hajar L.', 'Parfait pour prendre le train a Agdal, le QR code a ete verifie en quelques secondes.'],
            ['Rabat Agdal Station Parking', 4, 'Anas K.', 'Parking propre, agents disponibles, il manque juste plus de places couvertes.'],
            ['Marrakech Gueliz City Hub', 5, 'Mehdi A.', 'Tres pratique pour Gueliz, les places handicapees sont bien signalees.'],
            ['Marrakech Gueliz City Hub', 4, 'Amina R.', 'Application simple et les disponibilites en direct correspondent bien au terrain.'],
        ];

        foreach ($reviews as $review) {
            Feedback::create([
                'ground_id' => $zones[$review[0]],
                'rating' => $review[1],
                'name' => $review[2],
                'message' => $review[3],
            ]);
        }
    }
}
