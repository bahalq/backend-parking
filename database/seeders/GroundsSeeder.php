<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParkingZone;
use App\Models\ParkingZoneImage;
use App\Models\ParkingSpot;
use App\Models\VehicleCategory;
use App\Models\User;

class GroundsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@booking.com')->first() 
              ?? User::where('role', 'Admin')->first();
        $adminId = $admin ? $admin->id : 1;

        $compact = VehicleCategory::where('name', 'Compact')->first();
        $sedan = VehicleCategory::where('name', 'Sedan')->first();
        $suv = VehicleCategory::where('name', 'SUV')->first();
        $ev = VehicleCategory::where('name', 'EV Charging')->first();
        $moto = VehicleCategory::where('name', 'Motorcycle')->first();

        $compactId = $compact ? $compact->id : null;
        $sedanId = $sedan ? $sedan->id : null;
        $suvId = $suv ? $suv->id : null;
        $evId = $ev ? $ev->id : null;
        $motoId = $moto ? $moto->id : null;

        $parkingZones = [
            [
                "name" => "Casablanca Central Plaza Parking",
                "city" => "Casablanca",
                "address" => "12 Boulevard Mohamed V, Casablanca",
                "description" => "Premium downtown parking garage with dynamic spaces, secure CCTV surveillance, and fast EV charging bays.",
                "latitude" => 33.5731104,
                "longitude" => -7.5898434,
                "image" => "elite.jpg",
                "spots" => [
                    ["name" => "Spot A-01", "type" => "Standard", "category_id" => $compactId, "price" => 15],
                    ["name" => "Spot A-02", "type" => "Standard", "category_id" => $compactId, "price" => 15],
                    ["name" => "Spot B-01", "type" => "Standard", "category_id" => $sedanId, "price" => 20],
                    ["name" => "Spot EV-01", "type" => "EV Charger", "category_id" => $evId, "price" => 30],
                    ["name" => "Spot M-01", "type" => "Motorcycle Only", "category_id" => $motoId, "price" => 10],
                ],
            ],
            [
                "name" => "Rabat Station Parking lot",
                "city" => "Rabat",
                "address" => "Avenue de France, Rabat",
                "description" => "Convenient public parking located adjacent to the Rabat Ville railway station. Features automated ticketing and security patrol.",
                "latitude" => 34.020882,
                "longitude" => -6.841650,
                "image" => "green.jpg",
                "spots" => [
                    ["name" => "Spot A-01", "type" => "Standard", "category_id" => $compactId, "price" => 12],
                    ["name" => "Spot C-01", "type" => "Large SUV Spot", "category_id" => $suvId, "price" => 25],
                    ["name" => "Spot EV-02", "type" => "EV Charger", "category_id" => $evId, "price" => 28],
                ],
            ],
        ];

        foreach ($parkingZones as $z) {
            $zone = ParkingZone::create([
                'id_admin' => $adminId,
                'name' => $z['name'],
                'city' => $z['city'],
                'address' => $z['address'],
                'description' => $z['description'],
                'latitude' => $z['latitude'],
                'longitude' => $z['longitude'],
                'total_spots' => count($z['spots']),
            ]);

            if (!empty($z['image'])) {
                ParkingZoneImage::create([
                    'parking_zone_id' => $zone->id,
                    'image' => $z['image'],
                ]);
            }

            foreach ($z['spots'] as $s) {
                ParkingSpot::create([
                    'parking_zone_id' => $zone->id,
                    'vehicle_category_id' => $s['category_id'],
                    'name' => $s['name'],
                    'type' => $s['type'],
                    'status' => 'Available',
                    'price_per_hour' => $s['price'],
                ]);
            }
        }
    }
}
