<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'cin',
        'parking_zone_id',
        'ground_id', // for backward compatibility in mass-assignments
    ];

    protected $guarded = ['role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Smart Parking Relations
    public function parkingZones()
    {
        return $this->hasMany(ParkingZone::class, 'id_admin');
    }

    public function parkingZone()
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }

    public function parkingReservations()
    {
        // For staff assigned to a zone or drivers linked to users if any
        return $this->hasMany(ParkingReservation::class);
    }

    // --- Backward Compatibility Support ---

    public function getGroundIdAttribute()
    {
        return $this->parking_zone_id;
    }

    public function setGroundIdAttribute($value)
    {
        $this->parking_zone_id = $value;
    }

    public function grounds()
    {
        return $this->hasMany(ParkingZone::class, 'id_admin');
    }

    public function ground()
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }

    public function bookings()
    {
        return $this->hasMany(ParkingReservation::class);
    }
}
