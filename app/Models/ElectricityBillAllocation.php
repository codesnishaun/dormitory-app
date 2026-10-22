<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ElectricityBillAllocation extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $allocation): never {
            throw new LogicException('Finalized electricity bill allocations cannot be changed.');
        });

        static::deleting(function (self $allocation): never {
            throw new LogicException('Finalized electricity bill allocations cannot be deleted.');
        });
    }

    protected $fillable = [
        'electricity_bill_id',
        'room_assignment_id',
        'user_id',
        'tenant_name',
        'chargeable_days',
        'allocated_amount',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'chargeable_days' => 'integer',
            'allocated_amount' => 'decimal:2',
        ];
    }

    public function electricityBill(): BelongsTo
    {
        return $this->belongsTo(ElectricityBill::class);
    }

    public function roomAssignment(): BelongsTo
    {
        return $this->belongsTo(RoomAssignment::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
