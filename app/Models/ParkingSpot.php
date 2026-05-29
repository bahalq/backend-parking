<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingSpot extends Model
{
    use HasFactory;

    protected $table = 'parking_spots';

    protected $fillable = [
        'parking_zone_id',
        'vehicle_category_id',
        'name',
        'type',
        'status',
        'price_per_hour',
    ];

    // Relations
    public function parkingZone()
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }

    public function vehicleCategory()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function reservations()
    {
        return $this->hasMany(ParkingReservation::class, 'parking_spot_id');
    }

    public function occupancyLogs()
    {
        return $this->hasMany(OccupancyLog::class, 'parking_spot_id');
    }

    // --- Backward Compatibility Aliases for Pitch / Terrain ---
    
    public function ground()
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }

    public function activity()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function getGroundIdAttribute()
    {
        return $this->parking_zone_id;
    }

    public function getActivityIdAttribute()
    {
        return $this->vehicle_category_id;
    }
}
