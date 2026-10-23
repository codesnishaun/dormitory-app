<?php

namespace App\Services;

use App\Models\AbsenceRequest;
use App\Models\ElectricityBill;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Transaction;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ElectricityBillingService
{
    /**
     * @return array{
     *     room: Room,
     *     period_start: CarbonImmutable,
     *     period_end: CarbonImmutable,
     *     rate_per_kwh: ?string,
     *     cost_source: string,
     *     allocation_method: string,
     *     total_kwh: string,
     *     total_amount: string,
     *     total_person_days: int,
     *     allocated_total: string,
     *     reconciles: bool,
     *     segments: array<int, array<string, mixed>>,
     *     allocations: array<int, array<string, mixed>>
     * }
     */
    public function preview(
        Room $room,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        ?string $ratePerKwh,
        ?string $actualRoomCost,
    ): array {
        return $this->buildPreview($room, $periodStart, $periodEnd, $ratePerKwh, $actualRoomCost);
    }

    public function finalize(
        Room $room,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        ?string $ratePerKwh,
        ?string $actualRoomCost,
        User $admin,
        string $previewTokenHash,
    ): ElectricityBill {
        return DB::transaction(function () use ($room, $periodStart, $periodEnd, $ratePerKwh, $actualRoomCost, $admin, $previewTokenHash): ElectricityBill {
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
                    || (string) ($existingBill->rate_per_kwh ?? '') !== (string) ($ratePerKwh ?? '')
                    || $existingBill->cost_source !== ($actualRoomCost === null ? 'meter_rate' : 'provider_bill')
                    || ($actualRoomCost !== null && (string) $existingBill->total_amount !== $actualRoomCost)
                ) {
                    throw ValidationException::withMessages([
                        'preview_token' => 'This preview token has already been used for a different billing cycle.',
                    ]);
                }

                return $existingBill->load(['room', 'segments', 'allocations']);
            }

            $preview = $this->buildPreview($lockedRoom, $periodStart, $periodEnd, $ratePerKwh, $actualRoomCost, true);

            $bill = ElectricityBill::query()->create([
                'room_id' => $lockedRoom->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'rate_per_kwh' => $ratePerKwh,
                'cost_source' => $preview['cost_source'],
                'allocation_method' => $preview['allocation_method'],
                'total_kwh' => $preview['total_kwh'],
                'total_amount' => $preview['total_amount'],
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
                    'amount' => $allocation['allocated_amount'],
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
     *     rate_per_kwh: ?string,
     *     cost_source: string,
     *     allocation_method: string,
     *     total_kwh: string,
     *     total_amount: string,
     *     total_person_days: int,
     *     allocated_total: string,
     *     reconciles: bool,
     *     segments: array<int, array<string, mixed>>,
     *     allocations: array<int, array<string, mixed>>
     * }
     */
    private function buildPreview(
        Room $room,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        ?string $ratePerKwh,
        ?string $actualRoomCost,
        bool $lockRows = false,
    ): array {
        if (($ratePerKwh === null) === ($actualRoomCost === null)) {
            throw ValidationException::withMessages([
                'actual_cost' => 'Provide either an actual room utility-bill amount or a per-kWh tariff, but not both.',
            ]);
        }

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
        $totalKwh = BigDecimal::zero();

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

            $consumption = BigDecimal::of($closingReading->reading_value)
                ->minus($openingReading->reading_value)
                ->toScale(3);

            if (
                BigDecimal::of($openingReading->reading_value)->compareTo(0) < 0
                || BigDecimal::of($closingReading->reading_value)->compareTo(0) < 0
                || $consumption->compareTo(0) < 0
            ) {
                throw ValidationException::withMessages([
                    'period_end' => 'Meter readings must be non-negative and a closing reading cannot be lower than its opening reading.',
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
                'consumption_kwh' => (string) $consumption,
            ];

            $totalKwh = $totalKwh->plus($consumption);
            $cursor = $segmentEnd->addDay();
        }

        if ($cursor->lessThanOrEqualTo($periodEnd)) {
            throw ValidationException::withMessages([
                'room_id' => 'There is a gap in meter coverage during this billing period.',
            ]);
        }

        $totalKwh = $totalKwh->toScale(3);
        $totalAmount = $actualRoomCost === null
            ? $totalKwh->multipliedBy($ratePerKwh)->toScale(2, RoundingMode::HalfUp)
            : BigDecimal::of($actualRoomCost)->toScale(2);

        if ($totalAmount->compareTo('99999999.99') > 0) {
            throw ValidationException::withMessages([
                $actualRoomCost === null ? 'rate_per_kwh' : 'actual_cost' => 'The room electricity cost exceeds the maximum supported amount.',
            ]);
        }

        $roomAssignmentsQuery = RoomAssignment::query()
            ->with(['user', 'absenceRequests' => function (HasMany $query) use ($periodStart, $periodEnd, $lockRows): void {
                $query->whereDate('start_date', '<=', $periodEnd->toDateString())
                    ->whereDate('end_date', '>=', $periodStart->toDateString());

                if ($lockRows) {
                    $query->lockForUpdate();
                }
            }])
            ->where('room_id', $room->id)
            ->whereIn('status', ['reserved', 'occupied', 'ended'])
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

            if ($roomAssignment->status === 'ended' && $roomAssignment->end_date === null) {
                throw ValidationException::withMessages([
                    'room_id' => 'An ended tenant room assignment is missing its move-out date.',
                ]);
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
            $absenceSnapshots = $this->approvedAbsenceSnapshots(
                $roomAssignment->absenceRequests,
                $activeStart,
                $activeEnd,
            );
            $approvedAbsenceDays = $this->countApprovedAbsenceDays($absenceSnapshots);
            $chargeableDays = max($calendarDays - $approvedAbsenceDays, 0);

            $allocationInputs[] = [
                'room_assignment_id' => $roomAssignment->id,
                'user_id' => $roomAssignment->user_id,
                'tenant_name' => $roomAssignment->user->name,
                'assignment_start_date' => $roomAssignment->start_date->toDateString(),
                'assignment_end_date' => $roomAssignment->end_date?->toDateString(),
                'eligible_days' => $calendarDays,
                'approved_absence_days' => $approvedAbsenceDays,
                'absence_snapshots' => $absenceSnapshots,
                'chargeable_days' => $chargeableDays,
            ];
            $totalPersonDays += $chargeableDays;
        }

        if ($totalPersonDays === 0) {
            throw ValidationException::withMessages([
                'room_id' => 'No tenant person-days are eligible for allocation during this billing period.',
            ]);
        }

        $totalCents = $totalAmount->multipliedBy(100)->toInt();
        $baseAllocations = [];
        $remainders = [];
        $allocatedBaseCents = 0;

        foreach ($allocationInputs as $index => $allocation) {
            [$baseCents, $remainder] = BigInteger::of($totalCents)
                ->multipliedBy($allocation['chargeable_days'])
                ->quotientAndRemainder($totalPersonDays);
            $baseAllocations[$index] = $baseCents->toInt();
            $remainders[$index] = $remainder;
            $allocatedBaseCents += $baseAllocations[$index];
        }

        $remainingCents = $totalCents - $allocatedBaseCents;
        if ($remainingCents < 0 || $remainingCents >= count($allocationInputs)) {
            throw new LogicException('The electricity allocation rounding remainder is outside the expected range.');
        }

        $remainderOrder = array_keys($allocationInputs);
        usort($remainderOrder, function (int $left, int $right) use ($remainders, $allocationInputs): int {
            $remainderComparison = $remainders[$right]->compareTo($remainders[$left]);

            return $remainderComparison !== 0
                ? $remainderComparison
                : $allocationInputs[$left]['room_assignment_id'] <=> $allocationInputs[$right]['room_assignment_id'];
        });

        $roundingAdjustments = array_fill(0, count($allocationInputs), 0);
        for ($index = 0; $index < $remainingCents; $index++) {
            $roundingAdjustments[$remainderOrder[$index]] = 1;
        }

        $allocations = [];
        $allocatedCentsTotal = 0;

        foreach ($allocationInputs as $index => $allocation) {
            $allocatedCents = $baseAllocations[$index] + $roundingAdjustments[$index];
            $allocatedCentsTotal += $allocatedCents;
            $allocations[] = $allocation + [
                'allocated_amount' => $this->formatCents($allocatedCents),
                'rounding_adjustment_cents' => $roundingAdjustments[$index],
            ];
        }

        if ($allocatedCentsTotal !== $totalCents) {
            throw new LogicException('Tenant electricity allocations do not reconcile with the room cost.');
        }

        return [
            'room' => $room,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'rate_per_kwh' => $ratePerKwh,
            'cost_source' => $actualRoomCost === null ? 'meter_rate' : 'provider_bill',
            'allocation_method' => ElectricityBill::ALLOCATION_METHOD_OCCUPANCY_DAYS,
            'total_kwh' => (string) $totalKwh,
            'total_amount' => (string) $totalAmount,
            'total_person_days' => $totalPersonDays,
            'allocated_total' => $this->formatCents($allocatedCentsTotal),
            'reconciles' => $allocatedCentsTotal === $totalCents,
            'segments' => $segments,
            'allocations' => $allocations,
        ];
    }

    /**
     * @param  Collection<int, AbsenceRequest>  $absenceRequests
     * @return array<int, array{
     *     absence_request_id: int,
     *     requested_start_date: string,
     *     requested_end_date: string,
     *     eligible_start_date: string,
     *     eligible_end_date: string
     * }>
     */
    private function approvedAbsenceSnapshots(
        Collection $absenceRequests,
        CarbonImmutable $activeStart,
        CarbonImmutable $activeEnd,
    ): array {
        return $absenceRequests
            ->filter(fn (AbsenceRequest $absenceRequest): bool => $absenceRequest->status === AbsenceRequest::STATUS_APPROVED)
            ->map(function (AbsenceRequest $absenceRequest) use ($activeStart, $activeEnd): ?array {
                $startDate = CarbonImmutable::parse($absenceRequest->start_date);
                $endDate = CarbonImmutable::parse($absenceRequest->end_date);
                $eligibleStartDate = $startDate->greaterThan($activeStart) ? $startDate : $activeStart;
                $eligibleEndDate = $endDate->lessThan($activeEnd) ? $endDate : $activeEnd;

                if ($eligibleStartDate->greaterThan($eligibleEndDate)) {
                    return null;
                }

                return [
                    'absence_request_id' => $absenceRequest->id,
                    'requested_start_date' => $startDate->toDateString(),
                    'requested_end_date' => $endDate->toDateString(),
                    'eligible_start_date' => $eligibleStartDate->toDateString(),
                    'eligible_end_date' => $eligibleEndDate->toDateString(),
                ];
            })
            ->filter()
            ->sortBy('eligible_start_date')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{
     *     absence_request_id: int,
     *     requested_start_date: string,
     *     requested_end_date: string,
     *     eligible_start_date: string,
     *     eligible_end_date: string
     * }>  $absenceSnapshots
     */
    private function countApprovedAbsenceDays(array $absenceSnapshots): int
    {
        $absenceRanges = collect($absenceSnapshots)
            ->map(fn (array $snapshot): array => [
                CarbonImmutable::parse($snapshot['eligible_start_date']),
                CarbonImmutable::parse($snapshot['eligible_end_date']),
            ])
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

    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
