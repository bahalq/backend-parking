<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingZone extends Model
{
    use HasFactory;

    protected $table = 'parking_zones';

    protected $fillable = [
        'id_admin',
        'name',
        'city',
        'address',
        'description',
        'latitude',
        'longitude',
        'total_spots',
    ];

    // Backward-compatible accessors for legacy code expecting 'ground' attributes
    public function getGroundIdAttribute()
    {
        return $this->id;
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'id_admin');
    }

    public function images()
    {
        return $this->hasMany(ParkingZoneImage::class, 'parking_zone_id');
    }

    public function spots()
    {
        return $this->hasMany(ParkingSpot::class, 'parking_zone_id');
    }

    public function vehicleCategories()
    {
        return $this->belongsToMany(VehicleCategory::class, 'parking_spots', 'parking_zone_id', 'vehicle_category_id')
            ->withPivot(['id', 'name', 'type', 'status', 'price_per_hour'])
            ->distinct();
    }

    // Helper relation for terrains mapping
    public function terrains()
    {
        return $this->hasMany(ParkingSpot::class, 'parking_zone_id');
    }

    public function feedbacks()
    {
        // Feedback table still uses ground_id as defined in Feedback.php
        return $this->hasMany(Feedback::class, 'ground_id');
    }

    public function staff()
    {
        return $this->hasMany(User::class, 'parking_zone_id');
    }
}
