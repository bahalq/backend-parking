<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\OccupancyLog;
use App\Models\ParkingZone;
use Carbon\Carbon;

class OccupancyService
{
    /**
     * Log a vehicle entering a parking spot.
     */
    public function logEntry(int $spotId, string $licensePlate): OccupancyLog
    {
        $spot = ParkingSpot::findOrFail($spotId);
        $spot->update(['status' => 'Occupied']);

        return OccupancyLog::create([
            'parking_spot_id' => $spotId,
            'parking_zone_id' => $spot->parking_zone_id,
            'vehicle_plate' => $licensePlate,
            'action' => 'Entry',
            'timestamp' => Carbon::now(),
        ]);
    }

    /**
     * Log a vehicle exiting a parking spot.
     */
    public function logExit(int $spotId, string $licensePlate): OccupancyLog
    {
        $spot = ParkingSpot::findOrFail($spotId);
        $spot->update(['status' => 'Available']);

        return OccupancyLog::create([
            'parking_spot_id' => $spotId,
            'parking_zone_id' => $spot->parking_zone_id,
            'vehicle_plate' => $licensePlate,
            'action' => 'Exit',
            'timestamp' => Carbon::now(),
        ]);
    }

    /**
     * Get live occupancy details for a parking zone.
     */
    public function getLiveOccupancy(int $zoneId): array
    {
        $zone = ParkingZone::findOrFail($zoneId);
        
        $totalSpots = $zone->spots()->count();
        $occupiedSpots = $zone->spots()->where('status', 'Occupied')->count();
        $reservedSpots = $zone->spots()->where('status', 'Reserved')->count();
        $availableSpots = $totalSpots - $occupiedSpots - $reservedSpots;
        
        $occupancyRate = $totalSpots > 0 ? round(($occupiedSpots / $totalSpots) * 100, 1) : 0;

        return [
            'total_spots' => $totalSpots,
            'occupied_spots' => $occupiedSpots,
            'reserved_spots' => $reservedSpots,
            'available_spots' => $availableSpots,
            'occupancy_rate' => $occupancyRate,
        ];
    }
}
