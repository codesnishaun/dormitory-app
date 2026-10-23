<?php

namespace App\Services;

use App\Models\AbsenceRequest;
use App\Models\ElectricityBill;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectricityBillingService
{
    /**
     * @return array{
     *     room: Room,
     *     period_start: CarbonImmutable,
     *     period_end: CarbonImmutable,
     *     rate_per_kwh: string,
     *     total_kwh: float,
     *     total_amount: float,
     *     total_person_days: int,
     *     segments: array<int, array<string, mixed>>,
     *     allocations: array<int, array<string, mixed>>
     * }
     */
    public function preview(
        Room $room,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        string $ratePerKwh,
    ): array {
        return $this->buildPreview($room, $periodStart, $periodEnd, $ratePerKwh);
    }

    public function finalize(
        Room $room,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        string $ratePerKwh,
        User $admin,
        string $previewTokenHash,
    ): ElectricityBill {
        return DB::transaction(function () use ($room, $periodStart, $periodEnd, $ratePerKwh, $admin, $previewTokenHash): ElectricityBill {
            $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);

            $existingBill = ElectricityBill::query()
                ->where('preview_token_hash', $previewTokenHash)
                ->lockForUpdate()
                ->first();

            if ($existingBill !== null) {
                if (
                    $existingBill->room_id !== $lockedRoom->id
                    || ! $existingBill->period_start->isSameDay($periodStart)
                    || ! $existingBill->period_end->isSameDay($periodEnd)
                    || (string) $existingBill->rate_per_kwh !== $ratePerKwh
                ) {
                    throw ValidationException::withMessages([
                        'preview_token' => 'This preview token has already been used for a different billing cycle.',
                    ]);
                }

                return $existingBill->load(['room', 'segments', 'allocations']);
            }

            $preview = $this->buildPreview($lockedRoom, $periodStart, $periodEnd, $ratePerKwh, true);

            $bill = ElectricityBill::query()->create([
                'room_id' => $lockedRoom->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'rate_per_kwh' => $ratePerKwh,
                'total_kwh' => number_format($preview['total_kwh'], 3, '.', ''),
                'total_amount' => number_format($preview['total_amount'], 2, '.', ''),
                'preview_token_hash' => $previewTokenHash,
                'finalized_by' => $admin->id,
                'finalized_at' => now(),
            ]);

            foreach ($preview['segments'] as $segment) {
                $segmentData = $segment;
                unset($segmentData['meter_number']);
                $bill->segments()->create($segmentData);
            }

            foreach ($preview['allocations'] as $allocation) {
                $transaction = Transaction::query()->create([
                    'user_id' => $allocation['user_id'],
                    'type' => 'charge',
                    'description' => 'Electricity bill for '.$lockedRoom->room_number,
                    'amount' => number_format($allocation['allocated_amount'], 2, '.', ''),
                    'transaction_date' => today(),
                    'due_date' => null,
                    'notes' => sprintf(
                        'Electricity bill #%d (%s to %s).',
                        $bill->id,
                        $periodStart->toDateString(),
                        $periodEnd->toDateString(),
                    ),
                    'recorded_by' => $admin->id,
                ]);

                $bill->allocations()->create($allocation + [
                    'electricity_bill_id' => $bill->id,
                    'transaction_id' => $transaction->id,
                ]);
            }

            return $bill->load(['room', 'segments', 'allocations']);
        });
    }

    /**
     * @return array{
     *     room: Room,
     *     period_start: CarbonImmutable,
     *     period_end: CarbonImmutable,
     *     rate_per_kwh: string,
     *     total_kwh: float,
     *     total_amount: float,
     *     total_person_days: int,
     *     segments: array<int, array<string, mixed>>,
     *     allocations: array<int, array<string, mixed>>
     * }
     */
    private function buildPreview(
        Room $room,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        string $ratePerKwh,
        bool $lockRows = false,
    ): array {
        $overlappingBillQuery = ElectricityBill::query()
            ->where('room_id', $room->id)
            ->whereDate('period_start', '<=', $periodEnd->toDateString())
            ->whereDate('period_end', '>=', $periodStart->toDateString());

        if ($lockRows) {
            $overlappingBillQuery->lockForUpdate();
        }

        if ($overlappingBillQuery->exists()) {
            throw ValidationException::withMessages([
                'period_start' => 'This period overlaps an electricity bill that has already been finalized for the room.',
            ]);
        }

        $assignmentsQuery = $room->meterAssignments()
            ->with('submeter')
            ->whereDate('installed_at', '<=', $periodEnd->toDateString())
            ->where(function (Builder $query) use ($periodStart): void {
                $query->whereNull('uninstalled_at')
                    ->orWhereDate('uninstalled_at', '>=', $periodStart->toDateString());
            })
            ->orderBy('installed_at')
            ->orderBy('id');

        if ($lockRows) {
            $assignmentsQuery->lockForUpdate();
        }

        $assignments = $assignmentsQuery->get();

        if ($assignments->isEmpty()) {
            throw ValidationException::withMessages([
                'room_id' => 'The room has no meter assignment covering this billing period.',
            ]);
        }

        $segments = [];
        $cursor = $periodStart;
        $totalKwh = 0.0;

        foreach ($assignments as $assignment) {
            $segmentStart = CarbonImmutable::parse($assignment->installed_at)
                ->greaterThan($periodStart)
                    ? CarbonImmutable::parse($assignment->installed_at)
                    : $periodStart;
            $assignmentEnd = $assignment->uninstalled_at
                ? CarbonImmutable::parse($assignment->uninstalled_at)
                : $periodEnd;
            $segmentEnd = $assignmentEnd->lessThan($periodEnd) ? $assignmentEnd : $periodEnd;

            if ($segmentEnd->lessThan($segmentStart)) {
                throw ValidationException::withMessages([
                    'room_id' => 'A meter assignment has an invalid date range for this billing period.',
                ]);
            }

            if ($segmentStart->greaterThan($cursor)) {
                throw ValidationException::withMessages([
                    'room_id' => 'There is a gap in meter coverage during this billing period.',
                ]);
            }

            if ($segmentStart->lessThan($cursor)) {
                throw ValidationException::withMessages([
                    'room_id' => 'Meter assignments overlap during this billing period.',
                ]);
            }

            $readingsQuery = $assignment->meterReadings()
                ->whereIn('reading_date', [$segmentStart->toDateString(), $segmentEnd->toDateString()])
                ->orderBy('reading_date')
                ->orderBy('id');

            if ($lockRows) {
                $readingsQuery->lockForUpdate();
            }

            $readings = $readingsQuery->get()->keyBy(fn ($reading) => $reading->reading_date->toDateString());
            $openingReading = $readings->get($segmentStart->toDateString());
            $closingReading = $readings->get($segmentEnd->toDateString());

            if ($openingReading === null || $closingReading === null) {
                throw ValidationException::withMessages([
                    'period_end' => sprintf(
                        'Meter %s needs readings on %s and %s before this bill can be previewed.',
                        $assignment->submeter?->meter_number ?? 'unknown',
                        $segmentStart->toDateString(),
                        $segmentEnd->toDateString(),
                    ),
                ]);
            }

            $consumption = round(
                (float) $closingReading->reading_value - (float) $openingReading->reading_value,
                3,
            );

            if ($consumption < 0) {
                throw ValidationException::withMessages([
                    'period_end' => 'A closing meter reading cannot be lower than its opening reading.',
                ]);
            }

            $segments[] = [
                'meter_assignment_id' => $assignment->id,
                'meter_number' => $assignment->submeter?->meter_number ?? 'Unknown meter',
                'opening_reading_id' => $openingReading->id,
                'closing_reading_id' => $closingReading->id,
                'segment_start' => $segmentStart->toDateString(),
                'segment_end' => $segmentEnd->toDateString(),
                'opening_reading' => $openingReading->reading_value,
                'closing_reading' => $closingReading->reading_value,
                'consumption_kwh' => number_format($consumption, 3, '.', ''),
            ];

            $totalKwh += $consumption;
            $cursor = $segmentEnd->addDay();
        }

        if ($cursor->lessThanOrEqualTo($periodEnd)) {
            throw ValidationException::withMessages([
                'room_id' => 'There is a gap in meter coverage during this billing period.',
            ]);
        }

        $totalKwh = round($totalKwh, 3);
        $totalAmount = round($totalKwh * (float) $ratePerKwh, 2);

        if ($totalAmount > 99999999.99) {
            throw ValidationException::withMessages([
                'rate_per_kwh' => 'The calculated bill exceeds the maximum supported amount.',
            ]);
        }

        $roomAssignmentsQuery = RoomAssignment::query()
            ->with(['user', 'absenceRequests' => fn (HasMany $query) => $query
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $periodEnd->toDateString())
                ->whereDate('end_date', '>=', $periodStart->toDateString())])
            ->where('room_id', $room->id)
            ->whereIn('status', ['reserved', 'occupied'])
            ->whereDate('start_date', '<=', $periodEnd->toDateString())
            ->where(function (Builder $query) use ($periodStart): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $periodStart->toDateString());
            })
            ->orderBy('id');

        if ($lockRows) {
            $roomAssignmentsQuery->lockForUpdate();
        }

        $roomAssignments = $roomAssignmentsQuery->get();
        $allocationInputs = [];
        $totalPersonDays = 0;

        foreach ($roomAssignments as $roomAssignment) {
            if ($roomAssignment->user === null) {
                continue;
            }

            $activeStart = CarbonImmutable::parse($roomAssignment->start_date)
                ->greaterThan($periodStart)
                    ? CarbonImmutable::parse($roomAssignment->start_date)
                    : $periodStart;
            $activeEnd = $roomAssignment->end_date
                ? CarbonImmutable::parse($roomAssignment->end_date)
                : $periodEnd;
            $activeEnd = $activeEnd->lessThan($periodEnd) ? $activeEnd : $periodEnd;

            if ($activeEnd->lessThan($activeStart)) {
                throw ValidationException::withMessages([
                    'room_id' => 'A tenant room assignment has an invalid date range for this billing period.',
                ]);
            }

            $calendarDays = (int) $activeStart->diffInDays($activeEnd) + 1;
            $approvedAbsenceDays = $this->countApprovedAbsenceDays(
                $roomAssignment->absenceRequests,
                $activeStart,
                $activeEnd,
            );
            $chargeableDays = max($calendarDays - $approvedAbsenceDays, 0);

            if ($chargeableDays === 0) {
                continue;
            }

            $allocationInputs[] = [
                'room_assignment_id' => $roomAssignment->id,
                'user_id' => $roomAssignment->user_id,
                'tenant_name' => $roomAssignment->user->name,
                'chargeable_days' => $chargeableDays,
            ];
            $totalPersonDays += $chargeableDays;
        }

        if ($totalPersonDays === 0) {
            throw ValidationException::withMessages([
                'room_id' => 'No tenant person-days are eligible for allocation during this billing period.',
            ]);
        }

        $totalCents = (int) round($totalAmount * 100);
        $remainingCents = $totalCents;
        $allocations = [];
        $roundedAllocations = [];

        foreach ($allocationInputs as $index => $allocation) {
            $allocatedCents = intdiv(
                $totalCents * $allocation['chargeable_days'],
                $totalPersonDays,
            );
            $remainingCents -= $allocatedCents;
            $roundedAllocations[$index] = $allocatedCents;
        }

        foreach ($roundedAllocations as $index => $allocatedCents) {
            if ($remainingCents > 0) {
                $roundedAllocations[$index]++;
                $remainingCents--;
            }
        }

        foreach ($allocationInputs as $index => $allocation) {
            $allocations[] = $allocation + [
                'allocated_amount' => $roundedAllocations[$index] / 100,
            ];
        }

        return [
            'room' => $room,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'rate_per_kwh' => $ratePerKwh,
            'total_kwh' => $totalKwh,
            'total_amount' => $totalAmount,
            'total_person_days' => $totalPersonDays,
            'segments' => $segments,
            'allocations' => $allocations,
        ];
    }

    /**
     * @param  Collection<int, AbsenceRequest>  $absenceRequests
     */
    private function countApprovedAbsenceDays(
        Collection $absenceRequests,
        CarbonImmutable $activeStart,
        CarbonImmutable $activeEnd,
    ): int {
        $absenceRanges = $absenceRequests
            ->map(function (AbsenceRequest $absenceRequest) use ($activeStart, $activeEnd): array {
                $startDate = CarbonImmutable::parse($absenceRequest->start_date);
                $endDate = CarbonImmutable::parse($absenceRequest->end_date);
                $startDate = $startDate->greaterThan($activeStart) ? $startDate : $activeStart;
                $endDate = $endDate->lessThan($activeEnd) ? $endDate : $activeEnd;

                return [$startDate, $endDate];
            })
            ->filter(fn (array $range): bool => $range[0]->lessThanOrEqualTo($range[1]))
            ->sortBy(fn (array $range): string => $range[0]->toDateString())
            ->values();

        $totalAbsenceDays = 0;
        $mergedStart = null;
        $mergedEnd = null;

        foreach ($absenceRanges as [$startDate, $endDate]) {
            if ($mergedStart === null) {
                $mergedStart = $startDate;
                $mergedEnd = $endDate;

                continue;
            }

            if ($startDate->lessThanOrEqualTo($mergedEnd->addDay())) {
                $mergedEnd = $endDate->greaterThan($mergedEnd) ? $endDate : $mergedEnd;

                continue;
            }

            $totalAbsenceDays += (int) $mergedStart->diffInDays($mergedEnd) + 1;
            $mergedStart = $startDate;
            $mergedEnd = $endDate;
        }

        if ($mergedStart !== null && $mergedEnd !== null) {
            $totalAbsenceDays += (int) $mergedStart->diffInDays($mergedEnd) + 1;
        }

        return $totalAbsenceDays;
    }
}
