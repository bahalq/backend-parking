<?php

namespace App\Services;

use App\Models\ParkingSpot;
use App\Models\ParkingReservation;
use Carbon\Carbon;

class ParkingStatusService
{
    /**
     * Transition a parking spot's status safely.
     */
    public function updateSpotStatus(int $spotId, string $status): bool
    {
        $allowed = ['Available', 'Occupied', 'Reserved', 'Maintenance'];
        
        if (!in_array($status, $allowed)) {
            return false;
        }

        $spot = ParkingSpot::findOrFail($spotId);
        return $spot->update(['status' => $status]);
    }

    /**
     * Scan and release spots where driver reservations have expired.
     */
    public function autoReleaseExpiredReservations(): int
    {
        $now = Carbon::now();
        $today = Carbon::today()->toDateString();
        $graceThreshold = $now->subMinutes(30)->toTimeString();

        // Find confirmed reservations starting in the past that are not checked in
        $expired = ParkingReservation::where('status', 'Confirmed')
            ->where('date', $today)
            ->where('start_time', '<', $graceThreshold)
            ->whereNull('confirmed_at')
            ->get();

        $releasedCount = 0;
        
        foreach ($expired as $reservation) {
            $reservation->update(['status' => 'Cancelled']);
            
            $spot = $reservation->parkingSpot;
            if ($spot && $spot->status === 'Reserved') {
                $spot->update(['status' => 'Available']);
            }
            
            $releasedCount++;
        }

        return $releasedCount;
    }
}
