<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingZone;
use App\Models\ParkingZoneImage;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GroundController extends Controller
{
    /**
     * Public API: List all parking zones.
     */
    public function index(Request $request)
    {
        $zones = ParkingZone::with(['images', 'spots.vehicleCategory'])
            ->orderBy('name')
            ->paginate(10);

        // Map zones for grounds-compatible format in React frontend
        $zones->getCollection()->transform(function ($zone) {
            $totalSpots = $zone->spots->count();
            $occupied = $zone->spots->where('status', 'Occupied')->count();
            $reserved = $zone->spots->where('status', 'Reserved')->count();
            $available = $totalSpots - $occupied - $reserved;

            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'city' => $zone->city,
                'address' => $zone->address,
                'description' => $zone->description,
                'latitude' => $zone->latitude,
                'longitude' => $zone->longitude,
                'total_spots' => $totalSpots,
                'available_spots' => $available,
                'images' => $zone->images->map(fn($img) => [
                    'id' => $img->id,
                    'image' => $img->image,
                ]),
                'activities' => $this->mapZoneCategories($zone->spots),
                'terrains' => $this->mapZoneSpots($zone->spots),
            ];
        });

        return response()->json([
            'success' => true,
            'grounds' => $zones,
        ]);
    }

    /**
     * Public API: Show details of a single parking zone.
     */
    public function show($id)
    {
        $zone = ParkingZone::with(['images', 'spots.vehicleCategory'])->find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Parking Zone not found.',
            ], 404);
        }

        $totalSpots = $zone->spots->count();
        $occupied = $zone->spots->where('status', 'Occupied')->count();
        $reserved = $zone->spots->where('status', 'Reserved')->count();
        $available = $totalSpots - $occupied - $reserved;

        $groundData = [
            'id' => $zone->id,
            'name' => $zone->name,
            'city' => $zone->city,
            'address' => $zone->address,
            'description' => $zone->description,
            'latitude' => $zone->latitude,
            'longitude' => $zone->longitude,
            'total_spots' => $totalSpots,
            'available_spots' => $available,
            'images' => $zone->images->map(fn($img) => [
                'id' => $img->id,
                'image' => $img->image,
            ]),
            'activities' => $this->mapZoneCategories($zone->spots),
            'terrains' => $this->mapZoneSpots($zone->spots),
        ];

        return response()->json([
            'success' => true,
            'ground' => $groundData,
        ]);
    }

    /**
     * Public API: List vehicle categories (repurposing activities).
     */
    public function activities()
    {
        $categories = VehicleCategory::withCount('parkingSpots')
            ->whereHas('parkingSpots')
            ->orderBy('name')
            ->get();

        // Map categories for activities-compatible format in React frontend
        $activities = $categories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'icon' => $cat->icon,
            'spots_count' => $cat->parking_spots_count,
        ]);

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);
    }

    /**
     * Admin-only API: Create a new parking zone.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $zone = ParkingZone::create([
            'id_admin' => $request->user()->id,
            'name' => $request->name,
            'city' => $request->city,
            'address' => $request->address,
            'description' => $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'total_spots' => 0,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('grounds', 'public');
                ParkingZoneImage::create([
                    'parking_zone_id' => $zone->id,
                    'image' => basename($path),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Parking Zone created successfully.',
            'ground' => $zone,
        ], 201);
    }

    /**
     * Admin-only API: Delete a parking zone.
     */
    public function destroy($id)
    {
        $zone = ParkingZone::find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Parking Zone not found.',
            ], 404);
        }

        // Delete images
        foreach ($zone->images as $img) {
            Storage::disk('public')->delete('grounds/' . $img->image);
            $img->delete();
        }

        $zone->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parking Zone deleted successfully.',
        ]);
    }

    private function mapZoneCategories(Collection $spots)
    {
        return $spots
            ->filter(fn($spot) => $spot->vehicleCategory !== null)
            ->groupBy('vehicle_category_id')
            ->map(function ($categorySpots) {
                $category = $categorySpots->first()->vehicleCategory;

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'spots_count' => $categorySpots->count(),
                    'available_spots_count' => $categorySpots
                        ->whereNotIn('status', ['Occupied', 'Reserved', 'Maintenance'])
                        ->count(),
                    'min_price_per_hour' => (float) $categorySpots->min('price_per_hour'),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    private function mapZoneSpots(Collection $spots)
    {
        return $spots->map(fn($spot) => [
            'id' => $spot->id,
            'ground_id' => $spot->parking_zone_id,
            'activity_id' => $spot->vehicle_category_id,
            'activity_name' => $spot->vehicleCategory?->name,
            'activity_icon' => $spot->vehicleCategory?->icon,
            'name' => $spot->name,
            'type' => $spot->type,
            'status' => $spot->status,
            'price_per_hour' => (float) $spot->price_per_hour,
        ])->values();
    }
}
