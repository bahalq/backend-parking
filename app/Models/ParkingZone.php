<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingZone extends Model
{
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
