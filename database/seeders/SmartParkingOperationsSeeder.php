<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\OccupancyLog;
use App\Models\ParkingReservation;
use App\Models\ParkingSpot;
use App\Models\ParkingZone;
use App\Models\TrafficAnalytic;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SmartParkingOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = $this->seedDrivers();
        $this->seedReservationsAndLogs($drivers);
        $this->seedTrafficAnalytics();
        $this->syncLiveSpotStatuses();
    }

    private function seedDrivers()
    {
        $drivers = [
            ['Karim', 'Bennani', 'karim.bennani@example.ma', '+212661001122', '12345-A-6'],
            ['Fatima', 'El Fassi', 'fatima.elfassi@example.ma', '+212662118800', '84219-B-6'],
            ['Mehdi', 'Ait Lahcen', 'mehdi.aitlahcen@example.ma', '+212663776655', '55102-D-1'],
            ['Hajar', 'Lahlou', 'hajar.lahlou@example.ma', '+212664982211', '73014-H-7'],
            ['Youssef', 'Amrani', 'youssef.amrani@example.ma', '+212665443322', '41982-J-6'],
            ['Amina', 'Raji', 'amina.raji@example.ma', '+212666220011', '90811-C-6'],
            ['Omar', 'Kettani', 'omar.kettani@example.ma', '+212667334455', '33770-A-1'],
            ['Leila', 'Mansouri', 'leila.mansouri@example.ma', '+212668771144', '68145-D-7'],
            ['Anas', 'El Idrissi', 'anas.elidrissi@example.ma', '+212669345678', '24680-B-1'],
            ['Sara', 'Berrada', 'sara.berrada@example.ma', '+212660998877', '12598-H-6'],
            ['Imane', 'Tazi', 'imane.tazi@example.ma', '+212661987654', '77201-C-1'],
            ['Nabil', 'Chraibi', 'nabil.chraibi@example.ma', '+212662556677', '60224-J-7'],
            ['Meryem', 'Ouazzani', 'meryem.ouazzani@example.ma', '+212663102938', '34002-A-6'],
            ['Rachid', 'Sbai', 'rachid.sbai@example.ma', '+212664564738', '91003-D-1'],
        ];

        return collect($drivers)->map(fn ($driver) => Driver::create([
            'first_name' => $driver[0],
            'last_name' => $driver[1],
            'email' => $driver[2],
            'phone' => $driver[3],
            'license_plate' => $driver[4],
        ]));
    }

    private function seedReservationsAndLogs($drivers): void
    {
        $today = Carbon::today();
        $reference = 1;
        $statusCycle = ['Completed', 'Confirmed', 'Pending', 'Completed', 'Cancelled', 'Confirmed'];

        foreach (ParkingZone::with('spots')->orderBy('id')->get() as $zoneIndex => $zone) {
            $spots = $zone->spots->where('status', '!=', 'Maintenance')->values();

            for ($dayOffset = -6; $dayOffset <= 5; $dayOffset++) {
                $date = $today->copy()->addDays($dayOffset);
                $dailyBookings = $dayOffset === 0 ? 5 : 4;

                for ($slot = 0; $slot < $dailyBookings; $slot++) {
                    $spot = $spots[($slot + ($dayOffset + 6) * 3 + $zoneIndex * 5) % $spots->count()];
                    $driver = $drivers[($reference + $slot + $zoneIndex) % $drivers->count()];
                    $startHour = 8 + (($slot * 3 + $zoneIndex + max(0, $dayOffset + 6)) % 12);
                    $duration = (($slot + $zoneIndex) % 3) + 1;
                    $endHour = min(22, $startHour + $duration);
                    $status = $this->statusFor($dayOffset, $slot, $statusCycle);
                    $confirmedAt = $status === 'Completed' ? $date->copy()->setTime($startHour, 5) : null;

                    $reservation = ParkingReservation::create([
                        'parking_spot_id' => $spot->id,
                        'driver_id' => $driver->id,
                        'date' => $date->toDateString(),
                        'start_time' => sprintf('%02d:00:00', $startHour),
                        'end_time' => sprintf('%02d:00:00', $endHour),
                        'total_price' => $spot->price_per_hour * ($endHour - $startHour),
                        'status' => $status,
                        'reference' => sprintf('PRK-DEMO-%03d', $reference),
                        'verification_code' => sprintf('%06d', 430000 + $reference),
                        'confirmed_at' => $confirmedAt,
                        'created_at' => $date->copy()->subDays(1)->setTime(15, ($reference * 7) % 60),
                        'updated_at' => now(),
                    ]);

                    $this->createOccupancyLogs($reservation, $driver->license_plate);
                    $reference++;
                }
            }
        }
    }

    private function statusFor(int $dayOffset, int $slot, array $statusCycle): string
    {
        if ($dayOffset < -1) {
            return $slot === 3 ? 'Cancelled' : 'Completed';
        }

        if ($dayOffset === -1) {
            return $slot === 0 ? 'Cancelled' : 'Completed';
        }

        if ($dayOffset === 0) {
            return ['Completed', 'Confirmed', 'Pending', 'Cancelled', 'Confirmed'][$slot];
        }

        $status = $statusCycle[($dayOffset + $slot) % count($statusCycle)];

        return $status === 'Completed' ? 'Confirmed' : $status;
    }

    private function createOccupancyLogs(ParkingReservation $reservation, string $plate): void
    {
        if ($reservation->status !== 'Completed') {
            return;
        }

        $entryAt = Carbon::parse($reservation->date . ' ' . $reservation->start_time)->addMinutes(4);
        $exitAt = Carbon::parse($reservation->date . ' ' . $reservation->end_time)->subMinutes(6);
        $spot = $reservation->parkingSpot;

        OccupancyLog::create([
            'parking_spot_id' => $spot->id,
            'parking_zone_id' => $spot->parking_zone_id,
            'vehicle_plate' => $plate,
            'action' => 'Entry',
            'timestamp' => $entryAt,
            'created_at' => $entryAt,
            'updated_at' => $entryAt,
        ]);

        if ($exitAt->greaterThan($entryAt) && Carbon::parse($reservation->date)->lt(Carbon::today())) {
            OccupancyLog::create([
                'parking_spot_id' => $spot->id,
                'parking_zone_id' => $spot->parking_zone_id,
                'vehicle_plate' => $plate,
                'action' => 'Exit',
                'timestamp' => $exitAt,
                'created_at' => $exitAt,
                'updated_at' => $exitAt,
            ]);
        }
    }

    private function seedTrafficAnalytics(): void
    {
        foreach (ParkingZone::withCount('spots')->get() as $zone) {
            for ($day = 0; $day <= 6; $day++) {
                for ($hour = 8; $hour <= 21; $hour++) {
                    $isWeekend = in_array($day, [0, 6], true);
                    $peakBoost = (($hour >= 8 && $hour <= 10) || ($hour >= 17 && $hour <= 20)) ? 0.25 : 0.0;
                    $base = $isWeekend ? 0.42 : 0.55;
                    $zoneBoost = ($zone->id % 3) * 0.04;
                    $occupancyRate = min(96, round(($base + $peakBoost + $zoneBoost) * 100, 2));
                    $vehicleCount = (int) round(($occupancyRate / 100) * max(1, $zone->spots_count));

                    TrafficAnalytic::create([
                        'parking_zone_id' => $zone->id,
                        'hour_of_day' => $hour,
                        'day_of_week' => $day,
                        'vehicle_count' => $vehicleCount,
                        'average_stay_duration_minutes' => $isWeekend ? 118 : 84,
                        'occupancy_rate' => $occupancyRate,
                    ]);
                }
            }
        }
    }

    private function syncLiveSpotStatuses(): void
    {
        ParkingSpot::where('status', '!=', 'Maintenance')->update(['status' => 'Available']);

        $todayReservations = ParkingReservation::with('parkingSpot')
            ->whereDate('date', Carbon::today())
            ->whereIn('status', ['Pending', 'Confirmed', 'Completed'])
            ->get();

        foreach ($todayReservations as $reservation) {
            if (!$reservation->parkingSpot || $reservation->parkingSpot->status === 'Maintenance') {
                continue;
            }

            $reservation->parkingSpot->update([
                'status' => $reservation->status === 'Completed' ? 'Occupied' : 'Reserved',
            ]);
        }
    }
}
