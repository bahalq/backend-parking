<?php

namespace App\Services;

use App\Models\ParkingZone;

class MapService
{
    /**
     * Compile a listing of all zones with geographic markers and real-time availability counts.
     */
    public function getParkingMarkers(): array
    {
        $zones = ParkingZone::with('spots')->get();

        return $zones->map(function ($zone) {
            $totalSpots = $zone->spots->count();
            $occupiedSpots = $zone->spots->where('status', 'Occupied')->count();
            $reservedSpots = $zone->spots->where('status', 'Reserved')->count();
            $availableSpots = $totalSpots - $occupiedSpots - $reservedSpots;
            
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'city' => $zone->city,
                'address' => $zone->address,
                'latitude' => (float)$zone->latitude,
                'longitude' => (float)$zone->longitude,
                'total_spots' => $totalSpots,
                'available_spots' => $availableSpots,
                'price_per_hour' => round($zone->spots->avg('price_per_hour') ?? 15.00, 2),
            ];
        })->toArray();
    }

    /**
     * Find nearest parking zones using distance math (Haversine formula).
     */
    public function findNearestZones(float $latitude, float $longitude, float $radiusKm = 10.0): array
    {
        $zones = $this->getParkingMarkers();
        $filtered = [];

        foreach ($zones as $zone) {
            if (!$zone['latitude'] || !$zone['longitude']) {
                continue;
            }

            $distance = $this->calculateDistance($latitude, $longitude, $zone['latitude'], $zone['longitude']);
            
            if ($distance <= $radiusKm) {
                $zone['distance_km'] = round($distance, 2);
                $filtered[] = $zone;
            }
        }

        // Sort by distance ascending
        usort($filtered, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return $filtered;
    }

    /**
     * Calculate geospatial distance between two coordinates in kilometers.
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
