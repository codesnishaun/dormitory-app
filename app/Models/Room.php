<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'capacity',
        'monthly_rent',
        'submeter_number',
        'status',
    ];

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function meterAssignments(): HasMany
    {
        return $this->hasMany(MeterAssignment::class);
    }

    public function currentMeterAssignment(): HasOne
    {
        return $this->hasOne(MeterAssignment::class)
            ->whereNull('uninstalled_at')
            ->latest('installed_at')
            ->latest('id');
    }
}
