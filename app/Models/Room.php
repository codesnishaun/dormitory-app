<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number', 'floor_number', 'capacity',
        'monthly_rent', 'submeter_number', 'status',

    ];
}
