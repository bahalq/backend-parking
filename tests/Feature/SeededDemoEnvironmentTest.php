<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\ParkingReservation;
use App\Models\ParkingSpot;
use App\Models\ParkingZone;
use App\Models\TrafficAnalytic;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Services\MapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeededDemoEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_demo_environment_supports_core_flows(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'superadmin@smartparking.ma',
            'role' => 'Admin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'salma.alaoui@smartparking.ma',
            'role' => 'Staff',
        ]);

        $this->assertSame(3, ParkingZone::count());
        $this->assertGreaterThanOrEqual(60, ParkingSpot::count());
        $this->assertGreaterThanOrEqual(100, ParkingReservation::count());
        $this->assertGreaterThanOrEqual(250, TrafficAnalytic::count());
        $this->assertSame(6, Feedback::count());

        $requiredCategories = ['Sedan', 'SUV', 'EV', 'Motorcycle', 'Accessible'];
        ParkingZone::with('spots.vehicleCategory')->each(function (ParkingZone $zone) use ($requiredCategories) {
            $this->assertGreaterThan(0, $zone->spots->count(), $zone->name . ' should not be empty.');

            foreach ($requiredCategories as $category) {
                $spots = $zone->spots->filter(fn ($spot) => $spot->vehicleCategory?->name === $category);
                $this->assertGreaterThanOrEqual(2, $spots->count(), $zone->name . ' should include multiple ' . $category . ' spots.');
            }
        });

        $adminLoginResponse = $this->postJson('/api/admin/login', [
            'email' => 'superadmin@smartparking.ma',
            'password' => 'Admin@123',
        ])->assertOk();
        $adminToken = $adminLoginResponse->json('token');

        $staffLoginResponse = $this->postJson('/api/staff/login', [
            'email' => 'salma.alaoui@smartparking.ma',
            'password' => 'Staff@123',
        ])->assertOk()->assertJsonPath('user.role', 'Staff');
        $staffToken = $staffLoginResponse->json('token');
        $this->assertNotSame($adminToken, $staffToken);

        Sanctum::actingAs(User::where('email', 'superadmin@smartparking.ma')->firstOrFail());

        $this->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['stats' => ['bookings', 'revenue', 'terrains', 'peak_hours', 'recent_activity', 'charts']]);

        Sanctum::actingAs(User::where('email', 'salma.alaoui@smartparking.ma')->firstOrFail());

        $this->getJson('/api/staff/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['stats' => ['today_bookings', 'pending_count', 'recent_bookings', 'chart_data']]);

        $groundsResponse = $this->getJson('/api/grounds')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'grounds.data');

        $firstGround = $groundsResponse->json('grounds.data.0');
        $this->assertNotEmpty($firstGround['activities']);
        $this->assertNotEmpty($firstGround['terrains']);
        $this->assertNotContains(null, array_column($firstGround['activities'], 'id'));

        $detailsResponse = $this->getJson('/api/grounds/' . $firstGround['id'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertNotEmpty($detailsResponse->json('ground.activities'));
        $this->assertNotEmpty($detailsResponse->json('ground.terrains'));

        $spot = ParkingSpot::where('status', 'Available')->firstOrFail();
        $this->getJson('/api/terrains/availability?terrain_id=' . $spot->id . '&date=' . now()->toDateString())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['available_slots']);

        $this->getJson('/api/terrains/month-availability?terrain_id=' . $spot->id . '&year=' . now()->year . '&month=' . now()->month)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['availability', 'bookings_map', 'capacity_map']);

        $category = VehicleCategory::where('name', 'EV')->firstOrFail();
        $this->getJson('/api/terrains/by-activity?ground_id=' . $spot->parking_zone_id . '&activity_id=' . $category->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['terrains' => [['id', 'ground_id', 'activity_id', 'activity']]]);

        $markers = app(MapService::class)->getParkingMarkers();
        $this->assertCount(3, $markers);
        $this->assertGreaterThan(0, $markers[0]['available_spots']);
    }
}
