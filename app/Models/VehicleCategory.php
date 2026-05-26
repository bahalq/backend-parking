<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleCategory extends Model
{
    protected $table = 'vehicle_categories';

    protected $fillable = [
        'name',
        'icon',
    ];

    public function parkingSpots()
    {
        return $this->hasMany(ParkingSpot::class, 'vehicle_category_id');
    }
}
