<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ElectricityBill extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $bill): never {
            throw new LogicException('Finalized electricity bills cannot be changed.');
        });

        static::deleting(function (self $bill): never {
            throw new LogicException('Finalized electricity bills cannot be deleted.');
        });
    }

    protected $fillable = [
        'room_id',
        'period_start',
        'period_end',
        'rate_per_kwh',
        'total_kwh',
        'total_amount',
        'finalized_by',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'rate_per_kwh' => 'decimal:4',
            'total_kwh' => 'decimal:3',
            'total_amount' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by')->withTrashed();
    }

    public function segments(): HasMany
    {
        return $this->hasMany(ElectricityBillSegment::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ElectricityBillAllocation::class);
    }
}
