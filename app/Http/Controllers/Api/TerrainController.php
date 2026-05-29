<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSpot;
use App\Models\ParkingZone;
use App\Models\ParkingReservation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TerrainController extends Controller
{
    /**
     * Admin-only API: List all spots with relations.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ParkingSpot::with(['parkingZone', 'vehicleCategory']);

        // Staff are limited to their assigned zone
        if ($user && $user->role === 'Staff' && $user->parking_zone_id) {
            $query->where('parking_zone_id', $user->parking_zone_id);
        }

        if ($request->filled('ground_id')) {
            $query->where('parking_zone_id', $request->ground_id);
        }

        $spots = $query->orderBy('name')->paginate(15);

        // Map spots for terrains-compatible format in React frontend
        $spots->getCollection()->transform(function ($spot) {
            return [
                'id' => $spot->id,
                'ground_id' => $spot->parking_zone_id,
                'activity_id' => $spot->vehicle_category_id,
                'name' => $spot->name,
                'type' => $spot->type,
                'status' => $spot->status,
                'price_per_hour' => $spot->price_per_hour,
                'ground' => $spot->parkingZone ? [
                    'id' => $spot->parkingZone->id,
                    'name' => $spot->parkingZone->name,
                ] : null,
                'activity' => $spot->vehicleCategory ? [
                    'id' => $spot->vehicleCategory->id,
                    'name' => $spot->vehicleCategory->name,
                    'icon' => $spot->vehicleCategory->icon,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'terrains' => $spots,
        ]);
    }

    /**
     * Public API: List parking spots filtered by zone (ground) and category (activity).
     */
    public function byActivity(Request $request)
    {
        $request->validate([
            'ground_id' => 'required|integer',
            'activity_id' => 'required|integer',
        ]);

        $spots = ParkingSpot::where('parking_zone_id', $request->ground_id)
            ->where('vehicle_category_id', $request->activity_id)
            ->where('status', '!=', 'Maintenance')
            ->with('vehicleCategory')
            ->get();

        $terrains = $spots->map(fn($spot) => [
            'id' => $spot->id,
            'ground_id' => $spot->parking_zone_id,
            'activity_id' => $spot->vehicle_category_id,
            'name' => $spot->name,
            'type' => $spot->type,
            'status' => $spot->status,
            'price_per_hour' => (float) $spot->price_per_hour,
            'activity' => $spot->vehicleCategory ? [
                'id' => $spot->vehicleCategory->id,
                'name' => $spot->vehicleCategory->name,
                'icon' => $spot->vehicleCategory->icon,
            ] : null,
        ]);

        return response()->json([
            'success' => true,
            'terrains' => $terrains,
        ]);
    }

    /**
     * Public API: Fetch hourly availability for a spot on a given date.
     */
    public function availability(Request $request)
    {
        $request->validate([
            'terrain_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $spotId = $request->terrain_id;
        $date = $request->date;

        $reservations = ParkingReservation::where('parking_spot_id', $spotId)
            ->where('date', $date)
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->get();

        $slots = [];
        $availableSlots = [];
        // Support daily parking slots from 08:00 to 22:00
        for ($h = 8; $h <= 21; $h++) {
            $start = sprintf('%02d:00', $h);
            $end = sprintf('%02d:00', $h + 1);

            // Check if slot overlaps with any active reservation
            $isBooked = $reservations->contains(function ($res) use ($start, $end) {
                return ($start >= $res->start_time && $start < $res->end_time) ||
                       ($end > $res->start_time && $end <= $res->end_time) ||
                       ($res->start_time >= $start && $res->start_time < $end);
            });

            $slots[] = [
                'id' => $h,
                'start_time' => $start,
                'end_time' => $end,
                'available' => !$isBooked,
            ];

            // Frontend expects available_slots with time/display format
            if (!$isBooked) {
                $availableSlots[] = [
                    'time' => $start,
                    'display' => $start . ' - ' . $end,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'slots' => $slots,
            'available_slots' => $availableSlots,
        ]);
    }

    /**
     * Public API: Fetch daily calendar availability status for a spot during a month.
     */
    public function monthAvailability(Request $request)
    {
        $request->validate([
            'terrain_id' => 'required|integer',
        ]);

        $spotId = $request->terrain_id;

        // Accept both combined 'month=2026-05' and separate 'year=2026&month=5' params
        if ($request->filled('year')) {
            $monthStr = sprintf('%04d-%02d', $request->year, $request->month);
        } else {
            $monthStr = $request->month;
        }

        $daysInMonth = Carbon::parse($monthStr)->daysInMonth;
        $availability = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%s-%02d', $monthStr, $day);

            $reservations = ParkingReservation::where('parking_spot_id', $spotId)
                ->where('date', $dateStr)
                ->whereIn('status', ['Pending', 'Confirmed'])
                ->get();

            $bookedSlotsCount = 0;
            for ($h = 8; $h <= 21; $h++) {
                $start = sprintf('%02d:00', $h);
                $isBooked = $reservations->contains(function ($res) use ($start) {
                    return $start >= $res->start_time && $start < $res->end_time;
                });
                if ($isBooked) {
                    $bookedSlotsCount++;
                }
            }

            // Total slots per day is 14 (from 8:00 to 22:00)
            $availability[$dateStr] = $bookedSlotsCount < 14;
        }

        return response()->json([
            'success' => true,
            'availability' => $availability,
        ]);
    }

    /**
     * Admin-only API: Create a new parking spot.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ground_id' => 'required|integer|exists:parking_zones,id',
            'activity_id' => 'required|integer|exists:vehicle_categories,id',
            'name' => 'required|string|max:50',
            'type' => 'required|string|max:50', // e.g. Standard, EV Charging
            'price_per_hour' => 'required|numeric|min:0',
        ]);

        $spot = ParkingSpot::create([
            'parking_zone_id' => $request->ground_id,
            'vehicle_category_id' => $request->activity_id,
            'name' => $request->name,
            'type' => $request->type,
            'status' => 'Available',
            'price_per_hour' => $request->price_per_hour,
        ]);

        // Increment total spot count for this zone
        $zone = ParkingZone::find($request->ground_id);
        if ($zone) {
            $zone->increment('total_spots');
        }

        return response()->json([
            'success' => true,
            'message' => 'Parking Spot created successfully.',
            'terrain' => $spot,
        ], 201);
    }

    /**
     * Admin-only API: Update a parking spot.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:Available,Occupied,Reserved,Maintenance',
            'price_per_hour' => 'nullable|numeric|min:0',
        ]);

        $spot = ParkingSpot::find($id);

        if (!$spot) {
            return response()->json([
                'success' => false,
                'message' => 'Parking Spot not found.',
            ], 404);
        }

        $spot->update($request->only(['name', 'type', 'status', 'price_per_hour']));

        return response()->json([
            'success' => true,
            'message' => 'Parking Spot updated successfully.',
            'terrain' => $spot,
        ]);
    }

    /**
     * Admin-only API: Delete a parking spot.
     */
    public function destroy($id)
    {
        $spot = ParkingSpot::find($id);

        if (!$spot) {
            return response()->json([
                'success' => false,
                'message' => 'Parking Spot not found.',
            ], 404);
        }

        $zone = ParkingZone::find($spot->parking_zone_id);
        if ($zone) {
            $zone->decrement('total_spots');
        }

        $spot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parking Spot deleted successfully.',
        ]);
    }
}
