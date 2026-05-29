<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OccupancyLog extends Model
{
    use HasFactory;

    protected $table = 'occupancy_logs';

    protected $fillable = [
        'parking_spot_id',
        'parking_zone_id',
        'vehicle_plate',
        'action',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function parkingSpot()
    {
        return $this->belongsTo(ParkingSpot::class, 'parking_spot_id');
    }

    public function parkingZone()
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }
}
