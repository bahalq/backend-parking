<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingReservation;
use App\Models\ParkingSpot;
use App\Models\ParkingZone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/stats — Returns all admin dashboard data.
     */
    public function adminStats()
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 1. Reservations overview
        $totalBookings = ParkingReservation::count();
        $todayBookingsCount = ParkingReservation::whereDate('date', $today)->count();
        $weekBookingsCount = ParkingReservation::whereBetween('date', [$startOfWeek, Carbon::now()->endOfWeek()])->count();
        $monthBookingsCount = ParkingReservation::whereBetween('date', [$startOfMonth, $endOfMonth])->count();

        $statusBreakdown = ParkingReservation::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // 2. Revenue estimate
        $totalRevenue = ParkingReservation::whereIn('status', ['Confirmed', 'Completed'])->sum('total_price');
        $weekRevenue = ParkingReservation::whereIn('status', ['Confirmed', 'Completed'])
            ->whereBetween('date', [$startOfWeek, Carbon::now()->endOfWeek()])
            ->sum('total_price');
        $monthRevenue = ParkingReservation::whereIn('status', ['Confirmed', 'Completed'])
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('total_price');

        // 3. High-use spots (previously terrains stats)
        $topTerrains = ParkingReservation::select('parking_spot_id as terrain_id', DB::raw('count(*) as count'))
            ->with('terrain:id,name') // Using terrain alias in model
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->groupBy('parking_spot_id')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(fn($b) => [
                'name' => $b->terrain?->name ?? 'Unknown Spot',
                'count' => $b->count
            ]);

        $mostBookedTerrain = $topTerrains->first();

        // Occupancy rate - Today's confirmed/completed reservations / (total spots * 12 hours)
        $totalSpotsCount = ParkingSpot::count();
        $totalAvailableSlotsToday = $totalSpotsCount * 12; 
        $confirmedBookingsToday = ParkingReservation::whereDate('date', $today)
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->count();
        
        $occupationRate = $totalAvailableSlotsToday > 0 
            ? round(($confirmedBookingsToday / $totalAvailableSlotsToday) * 100, 1) 
            : 0;

        // 4. Peak hours (based on confirmed/completed bookings)
        $peakHours = ParkingReservation::select(DB::raw('SUBSTR(start_time, 1, 5) as hour'), DB::raw('count(*) as count'))
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->groupBy('hour')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // 5. Recent activity (last 5 reservations)
        $recentBookings = ParkingReservation::with(['parkingSpot.parkingZone', 'driver'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'client_name' => ($b->driver?->first_name ?? '') . ' ' . ($b->driver?->last_name ?? ''),
                'terrain' => $b->parkingSpot?->name ?? 'N/A',
                'date' => $b->date,
                'status' => $b->status,
                'start_time' => substr((string)$b->start_time, 0, 5)
            ]);

        // Charts data preparation - Reservations per day (last 7 days)
        $bookingsLast7Days = collect(range(6, 0))->map(function($i) {
            $date = Carbon::today()->subDays($i);
            return [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D'), // e.g., 'Mon'
                'count' => ParkingReservation::whereDate('date', $date)->count()
            ];
        });

        // Status breakdown for pie chart
        $statusPieChartData = [
            ['name' => 'Confirmed', 'value' => ($statusBreakdown['Confirmed'] ?? 0) + ($statusBreakdown['Completed'] ?? 0), 'color' => '#10b981'], // green
            ['name' => 'Pending', 'value' => $statusBreakdown['Pending'] ?? 0, 'color' => '#f59e0b'],   // yellow
            ['name' => 'Cancelled', 'value' => $statusBreakdown['Cancelled'] ?? 0, 'color' => '#ef4444'], // red
        ];

        return response()->json([
            'success' => true,
            'stats' => [
                'bookings' => [
                    'total' => $totalBookings,
                    'today' => $todayBookingsCount,
                    'week' => $weekBookingsCount,
                    'month' => $monthBookingsCount,
                    'breakdown' => [
                        'confirmed' => ($statusBreakdown['Confirmed'] ?? 0) + ($statusBreakdown['Completed'] ?? 0),
                        'cancelled' => $statusBreakdown['Cancelled'] ?? 0,
                        'pending' => $statusBreakdown['Pending'] ?? 0,
                    ]
                ],
                'revenue' => [
                    'total' => (float)$totalRevenue,
                    'week' => (float)$weekRevenue,
                    'month' => (float)$monthRevenue,
                ],
                'terrains' => [
                    'most_booked' => $mostBookedTerrain,
                    'occupation_rate' => $occupationRate,
                    'top_5' => $topTerrains,
                ],
                'peak_hours' => $peakHours,
                'recent_activity' => $recentBookings,
                'charts' => [
                    'bookings_daily' => $bookingsLast7Days,
                    'status_pie' => $statusPieChartData
                ]
            ]
        ]);
    }

    /**
     * GET /api/staff/stats — Returns staff dashboard data for their assigned parking zone.
     */
    public function staffStats(Request $request)
    {
        $groundId = $request->user()->parking_zone_id;
        if (!$groundId) {
            return response()->json(['success' => false, 'message' => 'Staff is not assigned to any parking zone.'], 403);
        }

        $today = Carbon::today()->toDateString();

        // 1. Today's reservations for the staff's assigned zone
        $todayBookings = ParkingReservation::whereHas('parkingSpot', function($q) use ($groundId) {
            $q->where('parking_zone_id', $groundId);
        })
        ->whereDate('date', $today)
        ->with(['driver', 'parkingSpot.vehicleCategory'])
        ->orderBy('start_time')
        ->get()
        ->map(fn($b) => [
            'id' => $b->id,
            'terrain_name' => $b->parkingSpot?->name,
            'activity_name' => $b->parkingSpot?->vehicleCategory?->name,
            'client_name' => ($b->driver?->first_name ?? '') . ' ' . ($b->driver?->last_name ?? ''),
            'start_time' => substr((string)$b->start_time, 0, 5),
            'end_time' => substr((string)$b->end_time, 0, 5),
            'status' => $b->status,
        ]);

        // 2. Next upcoming reservation
        $nextBooking = ParkingReservation::whereHas('parkingSpot', function($q) use ($groundId) {
            $q->where('parking_zone_id', $groundId);
        })
        ->where('status', 'Confirmed')
        ->where(function($q) use ($today) {
            $q->where('date', '>', $today)
              ->orWhere(function($q2) use ($today) {
                  $q2->where('date', $today)
                    ->where('start_time', '>', now()->toTimeString());
              });
        })
        ->with(['driver', 'parkingSpot'])
        ->orderBy('date')
        ->orderBy('start_time')
        ->first();

        // 3. Total entry scans done today (Confirmed/Completed bookings with confirmed_at today)
        $scansToday = ParkingReservation::whereHas('parkingSpot', function($q) use ($groundId) {
            $q->where('parking_zone_id', $groundId);
        })
        ->whereDate('confirmed_at', $today)
        ->count();

        // 4. Pending bookings count
        $pendingCount = ParkingReservation::whereHas('parkingSpot', function($q) use ($groundId) {
            $q->where('parking_zone_id', $groundId);
        })
        ->where('status', 'Pending')
        ->count();

        // 5. Recent bookings list (last 5 for their ground)
        $recentBookings = ParkingReservation::whereHas('parkingSpot', function($q) use ($groundId) {
            $q->where('parking_zone_id', $groundId);
        })
        ->with(['driver', 'parkingSpot'])
        ->latest()
        ->take(5)
        ->get()
        ->map(fn($b) => [
            'id' => $b->id,
            'terrain_name' => $b->parkingSpot?->name,
            'client_name' => ($b->driver?->first_name ?? '') . ' ' . ($b->driver?->last_name ?? ''),
            'date' => $b->date,
            'status' => $b->status,
        ]);

        // Chart: Reservations per day this week for their zone
        $startOfWeek = Carbon::now()->startOfWeek();
        $chartData = collect(range(0, 6))->map(function($i) use ($startOfWeek, $groundId) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = ParkingReservation::whereHas('parkingSpot', function($q) use ($groundId) {
                $q->where('parking_zone_id', $groundId);
            })
            ->whereDate('date', $date->toDateString())
            ->count();

            return [
                'day' => $date->format('D'),
                'date' => $date->toDateString(),
                'count' => $count
            ];
        });

        return response()->json([
            'success' => true,
            'stats' => [
                'today_bookings' => $todayBookings,
                'next_booking' => $nextBooking ? [
                    'id' => $nextBooking->id,
                    'terrain_name' => $nextBooking->parkingSpot?->name,
                    'client_name' => ($nextBooking->driver?->first_name ?? '') . ' ' . ($nextBooking->driver?->last_name ?? ''),
                    'date' => $nextBooking->date,
                    'start_time' => substr((string)$nextBooking->start_time, 0, 5),
                ] : null,
                'scans_today' => $scansToday,
                'pending_count' => $pendingCount,
                'recent_bookings' => $recentBookings,
                'chart_data' => $chartData,
            ]
        ]);
    }
}
