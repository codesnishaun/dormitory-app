<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class MeterReading extends Model
{
    protected $fillable = [
        'meter_assignment_id',
        'reading_value',
        'reading_date',
        'recorder_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $reading): void {
            if ($reading->isLockedByFinalizedBill()) {
                throw ValidationException::withMessages([
                    'reading_date' => 'Meter readings used by a finalized electricity bill cannot be changed.',
                ]);
            }
        });

        static::deleting(function (self $reading): void {
            if ($reading->isLockedByFinalizedBill()) {
                throw ValidationException::withMessages([
                    'reading_date' => 'Meter readings used by a finalized electricity bill cannot be deleted.',
                ]);
            }
        });

        static::creating(function (self $reading): void {
            $assignment = MeterAssignment::query()
                ->with('submeter')
                ->find($reading->meter_assignment_id);

            if ($assignment === null || $assignment->uninstalled_at !== null) {
                throw ValidationException::withMessages([
                    'reading_date' => 'New readings can only be recorded for an active meter assignment.',
                ]);
            }

            if ($assignment->submeter?->status !== 'active') {
                throw ValidationException::withMessages([
                    'reading_date' => 'Only active submeters can receive new readings.',
                ]);
            }

            $readingDate = Carbon::parse($reading->reading_date);

            if ($readingDate->lt($assignment->installed_at) || $readingDate->isAfter(today())) {
                throw ValidationException::withMessages([
                    'reading_date' => 'The reading date must be on or after installation and cannot be in the future.',
                ]);
            }

            if (ElectricityBillSegment::query()
                ->where('meter_assignment_id', $assignment->id)
                ->whereDate('segment_start', '<=', $readingDate->toDateString())
                ->whereDate('segment_end', '>=', $readingDate->toDateString())
                ->exists()) {
                throw ValidationException::withMessages([
                    'reading_date' => 'Readings that belong to a finalized electricity bill cannot be changed.',
                ]);
            }

            $previousReading = MeterReading::query()
                ->where('meter_assignment_id', $assignment->id)
                ->latest('reading_date')
                ->latest('id')
                ->first();

            if ($previousReading !== null) {
                if ($readingDate->lte($previousReading->reading_date)) {
                    throw ValidationException::withMessages([
                        'reading_date' => 'Readings must be recorded in date order, with only one reading per day.',
                    ]);
                }

                if ((float) $reading->reading_value < (float) $previousReading->reading_value) {
                    throw ValidationException::withMessages([
                        'reading_value' => 'A new meter reading cannot be lower than the previous reading.',
                    ]);
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'reading_date' => 'date',
            'reading_value' => 'decimal:3',
        ];
    }

    private function isLockedByFinalizedBill(): bool
    {
        $readingId = $this->getKey();
        $originalAssignmentId = (int) $this->getRawOriginal('meter_assignment_id');
        $originalReadingDate = (string) $this->getRawOriginal('reading_date');
        $assignmentId = (int) $this->meter_assignment_id;
        $readingDate = $this->reading_date->toDateString();

        return ElectricityBillSegment::query()
            ->where(function ($query) use ($readingId): void {
                $query->where('opening_reading_id', $readingId)
                    ->orWhere('closing_reading_id', $readingId);
            })
            ->exists()
            || $this->isInsideFinalizedSegment($originalAssignmentId, $originalReadingDate)
            || $this->isInsideFinalizedSegment($assignmentId, $readingDate);
    }

    private function isInsideFinalizedSegment(int $meterAssignmentId, string $readingDate): bool
    {
        return ElectricityBillSegment::query()
            ->where('meter_assignment_id', $meterAssignmentId)
            ->whereDate('segment_start', '<=', $readingDate)
            ->whereDate('segment_end', '>=', $readingDate)
            ->exists();
    }

    public function meterAssignment(): BelongsTo
    {
        return $this->belongsTo(MeterAssignment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorder_by');
    }
}
