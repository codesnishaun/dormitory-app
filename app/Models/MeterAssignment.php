<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class MeterAssignment extends Model
{
    protected $fillable = [
        'submeter_id',
        'room_id',
        'installed_by',
        'installed_at',
        'uninstalled_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $assignment): void {
            $submeter = Submeter::query()->find($assignment->submeter_id);

            if ($submeter === null || $submeter->status !== 'active') {
                throw ValidationException::withMessages([
                    'submeter_id' => 'Only active submeters can be assigned to a room.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'installed_at' => 'date',
            'uninstalled_at' => 'date',
        ];
    }

    public function submeter(): BelongsTo
    {
        return $this->belongsTo(Submeter::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }

    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function electricityBillSegments(): HasMany
    {
        return $this->hasMany(ElectricityBillSegment::class);
    }
}
