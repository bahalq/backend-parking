<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';
    protected $fillable = [
        'rating',
        'message',
        'name',
        'ground_id',
    ];

    public function ground()
    {
        return $this->belongsTo(ParkingZone::class, 'ground_id');
    }
}
