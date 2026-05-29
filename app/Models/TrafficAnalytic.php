<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrafficAnalytic extends Model
{
    use HasFactory;

    protected $table = 'traffic_analytics';

    protected $fillable = [
        'parking_zone_id',
        'hour_of_day',
        'day_of_week',
        'vehicle_count',
        'average_stay_duration_minutes',
        'occupancy_rate',
    ];

    public function parkingZone()
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }
}
