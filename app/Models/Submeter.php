<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Submeter extends Model
{
    protected $fillable = [
        'meter_number',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $submeter): void {
            if (
                $submeter->isDirty('status')
                && $submeter->status !== 'active'
                && $submeter->currentAssignment()->exists()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Close the active room assignment before deactivating or retiring this submeter.',
                ]);
            }
        });
    }

    public function meterAssignments(): HasMany
    {
        return $this->hasMany(MeterAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(MeterAssignment::class)
            ->whereNull('uninstalled_at')
            ->latest('installed_at')
            ->latest('id');
    }

    public function electricityBillSegments(): HasMany
    {
        return $this->hasManyThrough(
            ElectricityBillSegment::class,
            MeterAssignment::class,
            'submeter_id',
            'meter_assignment_id',
        );
    }
}
