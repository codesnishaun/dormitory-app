<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ElectricityBillSegment extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $segment): never {
            throw new LogicException('Finalized electricity bill readings cannot be changed.');
        });

        static::deleting(function (self $segment): never {
            throw new LogicException('Finalized electricity bill readings cannot be deleted.');
        });
    }

    protected $fillable = [
        'electricity_bill_id',
        'meter_assignment_id',
        'opening_reading_id',
        'closing_reading_id',
        'segment_start',
        'segment_end',
        'opening_reading',
        'closing_reading',
        'consumption_kwh',
    ];

    protected function casts(): array
    {
        return [
            'segment_start' => 'date',
            'segment_end' => 'date',
            'opening_reading' => 'decimal:3',
            'closing_reading' => 'decimal:3',
            'consumption_kwh' => 'decimal:3',
        ];
    }

    public function electricityBill(): BelongsTo
    {
        return $this->belongsTo(ElectricityBill::class);
    }

    public function meterAssignment(): BelongsTo
    {
        return $this->belongsTo(MeterAssignment::class);
    }

    public function openingReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class, 'opening_reading_id');
    }

    public function closingReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class, 'closing_reading_id');
    }
}
