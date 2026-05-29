<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingZoneImage extends Model
{
    use HasFactory;

    protected $table = 'parking_zone_images';

    protected $fillable = [
        'parking_zone_id',
        'image',
    ];

    public function parkingZone()
    {
        return $this->belongsTo(ParkingZone::class, 'parking_zone_id');
    }
}
