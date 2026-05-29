<?php

namespace Database\Seeders;

use App\Models\ParkingSpot;
use App\Models\ParkingZone;
use App\Models\ParkingZoneImage;
use App\Models\User;
use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GroundsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'superadmin@smartparking.ma'],
            [
                'first_name' => 'Yassine',
                'last_name' => 'El Mansouri',
                'password' => Hash::make('Admin@123'),
                'phone' => '+212522440100',
                'cin' => 'BE123456',
                'role' => 'Admin',
            ]
        );

        $categoryIds = VehicleCategory::pluck('id', 'name');

        $zones = [
            [
                'code' => 'CAS',
                'name' => 'Casablanca Marina Smart Parking',
                'city' => 'Casablanca',
                'address' => 'Boulevard Sidi Mohamed Ben Abdellah, Casablanca',
                'description' => 'Secure waterfront parking near Casa Port, Marina Mall, and the business district with EV charging and accessible bays.',
                'latitude' => 33.602102,
                'longitude' => -7.617719,
                'image' => 'casablanca-marina.jpg',
                'staff' => [
                    ['first_name' => 'Salma', 'last_name' => 'Alaoui', 'email' => 'salma.alaoui@smartparking.ma', 'phone' => '+212661120101', 'cin' => 'BK445210'],
                    ['first_name' => 'Hamza', 'last_name' => 'Bennani', 'email' => 'hamza.bennani@smartparking.ma', 'phone' => '+212662230202', 'cin' => 'BE778421'],
                ],
                'counts' => ['Compact' => 5, 'Sedan' => 8, 'SUV' => 3, 'EV' => 3, 'Motorcycle' => 2, 'Accessible' => 2],
                'base_price' => 14,
            ],
            [
                'code' => 'RAB',
                'name' => 'Rabat Agdal Station Parking',
                'city' => 'Rabat',
                'address' => 'Avenue Hassan II, Agdal, Rabat',
                'description' => 'High-turnover station parking for commuters, university visitors, and taxis with clear wayfinding and staffed entry gates.',
                'latitude' => 33.999219,
                'longitude' => -6.849787,
                'image' => 'rabat-agdal.jpg',
                'staff' => [
                    ['first_name' => 'Nawal', 'last_name' => 'Tazi', 'email' => 'nawal.tazi@smartparking.ma', 'phone' => '+212663340303', 'cin' => 'AD335908'],
                ],
                'counts' => ['Compact' => 6, 'Sedan' => 7, 'SUV' => 2, 'EV' => 2, 'Motorcycle' => 3, 'Accessible' => 2],
                'base_price' => 11,
            ],
            [
                'code' => 'MRK',
                'name' => 'Marrakech Gueliz City Hub',
                'city' => 'Marrakech',
                'address' => 'Avenue Mohammed V, Gueliz, Marrakech',
                'description' => 'Central Gueliz parking close to shops and hotels, optimized for tourist traffic, short stays, and evening peak demand.',
                'latitude' => 31.634236,
                'longitude' => -8.010057,
                'image' => 'marrakech-gueliz.jpg',
                'staff' => [
                    ['first_name' => 'Ayoub', 'last_name' => 'Skalli', 'email' => 'ayoub.skalli@smartparking.ma', 'phone' => '+212664450404', 'cin' => 'EE901245'],
                ],
                'counts' => ['Compact' => 4, 'Sedan' => 8, 'SUV' => 4, 'EV' => 2, 'Motorcycle' => 2, 'Accessible' => 2],
                'base_price' => 13,
            ],
        ];

        foreach ($zones as $zoneData) {
            $zone = ParkingZone::create([
                'id_admin' => $admin->id,
                'name' => $zoneData['name'],
                'city' => $zoneData['city'],
                'address' => $zoneData['address'],
                'description' => $zoneData['description'],
                'latitude' => $zoneData['latitude'],
                'longitude' => $zoneData['longitude'],
                'total_spots' => array_sum($zoneData['counts']),
            ]);

            ParkingZoneImage::create([
                'parking_zone_id' => $zone->id,
                'image' => $zoneData['image'],
            ]);

            foreach ($zoneData['staff'] as $staff) {
                User::create([
                    ...$staff,
                    'password' => Hash::make('Staff@123'),
                    'role' => 'Staff',
                    'parking_zone_id' => $zone->id,
                ]);
            }

            $sequence = 1;
            foreach ($zoneData['counts'] as $category => $count) {
                for ($i = 1; $i <= $count; $i++) {
                    ParkingSpot::create([
                        'parking_zone_id' => $zone->id,
                        'vehicle_category_id' => $categoryIds[$category],
                        'name' => sprintf('%s-%02d', $zoneData['code'], $sequence),
                        'type' => $this->spotType($category),
                        'status' => $this->spotStatus($sequence),
                        'price_per_hour' => $this->priceFor($category, $zoneData['base_price']),
                    ]);

                    $sequence++;
                }
            }
        }
    }

    private function spotType(string $category): string
    {
        return match ($category) {
            'EV' => 'EV Charging',
            'Accessible' => 'Accessible',
            'Motorcycle' => 'Motorcycle',
            'SUV' => 'Large Vehicle',
            default => 'Standard',
        };
    }

    private function priceFor(string $category, int $basePrice): int
    {
        return match ($category) {
            'EV' => $basePrice + 14,
            'Accessible' => $basePrice,
            'Motorcycle' => max(6, $basePrice - 5),
            'SUV' => $basePrice + 7,
            'Sedan' => $basePrice + 3,
            default => $basePrice,
        };
    }

    private function spotStatus(int $sequence): string
    {
        return match (true) {
            $sequence % 17 === 0 => 'Maintenance',
            $sequence % 11 === 0 => 'Occupied',
            $sequence % 7 === 0 => 'Reserved',
            default => 'Available',
        };
    }
}
