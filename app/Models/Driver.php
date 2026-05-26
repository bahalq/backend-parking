<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table = 'drivers';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'license_plate',
    ];

    public function reservations()
    {
        return $this->hasMany(ParkingReservation::class, 'driver_id');
    }
}
