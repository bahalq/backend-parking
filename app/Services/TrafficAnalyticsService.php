<?php

namespace App\Services;

use App\Models\ParkingReservation;
use App\Models\OccupancyLog;
use App\Models\TrafficAnalytic;
use App\Models\ParkingZone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrafficAnalyticsService
{
    /**
     * Compute and save traffic indicators for a parking zone.
     */
    public function generateHourlyAggregates(int $zoneId, int $hour, int $dayOfWeek): TrafficAnalytic
    {
        $today = Carbon::today();
        
        // Count entries in this hour today
        $vehicleCount = OccupancyLog::where('parking_zone_id', $zoneId)
            ->whereDate('timestamp', $today)
            ->whereRaw("strftime('%H', timestamp) = ?", [sprintf('%02d', $hour)])
            ->where('action', 'Entry')
            ->count();

        // Calculate average stay duration (Exit timestamp - Entry timestamp for same plate in this zone today)
        $entries = OccupancyLog::where('parking_zone_id', $zoneId)
            ->whereDate('timestamp', $today)
            ->where('action', 'Entry')
            ->get();
            
        $totalMinutes = 0;
        $completedStays = 0;
        
        foreach ($entries as $entry) {
            $exit = OccupancyLog::where('parking_spot_id', $entry->parking_spot_id)
                ->where('vehicle_plate', $entry->vehicle_plate)
                ->where('action', 'Exit')
                ->where('timestamp', '>', $entry->timestamp)
                ->orderBy('timestamp', 'asc')
                ->first();
                
            if ($exit) {
                $totalMinutes += $entry->timestamp->diffInMinutes($exit->timestamp);
                $completedStays++;
            }
        }
        
        $avgStayDuration = $completedStays > 0 ? (int)($totalMinutes / $completedStays) : 60; // default to 60 mins

        // Calculate occupancy rate
        $zone = ParkingZone::find($zoneId);
        $totalSpots = $zone ? $zone->spots()->count() : 1;
        $occupiedSpots = ParkingReservation::whereHas('parkingSpot', function ($q) use ($zoneId) {
                $q->where('parking_zone_id', $zoneId);
            })
            ->whereDate('date', $today)
            ->whereRaw("? BETWEEN start_time AND end_time", [sprintf('%02d:00:00', $hour)])
            ->count();
            
        $occupancyRate = $totalSpots > 0 ? round(($occupiedSpots / $totalSpots) * 100, 2) : 0;

        return TrafficAnalytic::updateOrCreate(
            [
                'parking_zone_id' => $zoneId,
                'hour_of_day' => $hour,
                'day_of_week' => $dayOfWeek,
            ],
            [
                'vehicle_count' => $vehicleCount,
                'average_stay_duration_minutes' => $avgStayDuration,
                'occupancy_rate' => $occupancyRate,
            ]
        );
    }

    /**
     * Get historical congestion and peak times for a parking zone.
     */
    public function getCongestionReport(int $zoneId): array
    {
        $analytics = TrafficAnalytic::where('parking_zone_id', $zoneId)
            ->orderBy('hour_of_day', 'asc')
            ->get();
            
        return $analytics->map(fn($a) => [
            'hour' => sprintf('%02d:00', $a->hour_of_day),
            'vehicle_count' => $a->vehicle_count,
            'occupancy_rate' => (float)$a->occupancy_rate,
            'avg_stay_duration' => $a->average_stay_duration_minutes,
        ])->toArray();
    }
}
