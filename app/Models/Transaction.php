<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'type', 'description', 'amount', 'transaction_date',
        'due_date', 'payment_method', 'notes', 'recorded_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $transaction): void {
            if ($transaction->electricityBillAllocation()->exists()) {
                throw new \LogicException('Electricity bill charges cannot be changed.');
            }
        });

        static::deleting(function (self $transaction): void {
            if ($transaction->electricityBillAllocation()->exists()) {
                throw new \LogicException('Electricity bill charges cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    public function electricityBillAllocation(): HasOne
    {
        return $this->hasOne(ElectricityBillAllocation::class);
    }
}
