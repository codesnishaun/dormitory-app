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
        'assignment_start_date',
        'assignment_end_date',
        'eligible_days',
        'approved_absence_days',
        'absence_snapshots',
        'chargeable_days',
        'rounding_adjustment_cents',
        'allocated_amount',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'assignment_start_date' => 'date',
            'assignment_end_date' => 'date',
            'eligible_days' => 'integer',
            'approved_absence_days' => 'integer',
            'absence_snapshots' => 'array',
            'chargeable_days' => 'integer',
            'rounding_adjustment_cents' => 'integer',
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
