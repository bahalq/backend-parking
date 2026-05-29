<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingReservation extends Model
{
    use HasFactory;

    protected $table = 'parking_reservations';

    protected $fillable = [
        'parking_spot_id',
        'driver_id',
        'date',
        'start_time',
        'end_time',
        'total_price',
        'status',
        'reference',
        'verification_code',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function parkingSpot()
    {
        return $this->belongsTo(ParkingSpot::class, 'parking_spot_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    // --- Backward Compatibility Aliases for Booking ---

    public function terrain()
    {
        return $this->belongsTo(ParkingSpot::class, 'parking_spot_id');
    }

    public function client()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function getTerrainIdAttribute()
    {
        return $this->parking_spot_id;
    }

    public function getClientIdAttribute()
    {
        return $this->driver_id;
    }
}
