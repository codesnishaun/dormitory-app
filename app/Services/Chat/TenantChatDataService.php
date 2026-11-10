<?php

namespace App\Services\Chat;

use App\Models\AbsenceRequest;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\ElectricityBill;
use App\Models\ElectricityBillAllocation;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class TenantChatDataService
{
    public function currentRoom(User $tenant): ?array
    {
        $this->ensureTenant($tenant);

        $assignment = $tenant->roomAssignments()
            ->with('room')
            ->whereIn('status', ['reserved', 'occupied'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($assignment === null) {
            return null;
        }

        return [
            'room_number' => $assignment->room?->room_number,
            'monthly_rent' => $assignment->room?->monthly_rent,
            'assignment_status' => $assignment->status,
            'start_date' => $assignment->start_date?->toDateString(),
            'end_date' => $assignment->end_date?->toDateString(),
        ];
    }

    public function applicationStatus(User $tenant, int $limit = 10): array
    {
        $this->ensureTenant($tenant);

        return Application::query()
            ->where('user_id', $tenant->id)
            ->with('preferredRoom')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Application $application): array => [
                'status' => $application->status,
                'preferred_room' => $application->preferredRoom?->room_number,
                'move_in_date' => $application->move_in_date,
                'submitted_at' => $application->created_at?->toDateString(),
            ])
            ->all();
    }

    public function balance(User $tenant): array
    {
        $this->ensureTenant($tenant);

        $charges = (float) $tenant->transactions()->where('type', 'charge')->sum('amount');
        $payments = (float) $tenant->transactions()->where('type', 'payment')->sum('amount');

        return [
            'charges_total' => round($charges, 2),
            'payments_total' => round($payments, 2),
            'outstanding_balance' => round($charges - $payments, 2),
        ];
    }

    public function paymentHistory(User $tenant, int $limit = 20): array
    {
        $this->ensureTenant($tenant);

        return $tenant->transactions()
            ->where('type', 'payment')
            ->latest('transaction_date')
            ->latest('id')
            ->limit($limit)
            ->get(['description', 'amount', 'transaction_date', 'payment_method'])
            ->map(fn (Transaction $transaction): array => [
                'description' => $transaction->description ?: 'Payment',
                'amount' => $transaction->amount,
                'date' => $transaction->transaction_date?->toDateString(),
                'payment_method' => $transaction->payment_method,
            ])
            ->all();
    }

    public function electricityBills(User $tenant, int $limit = 12): array
    {
        $this->ensureTenant($tenant);

        return ElectricityBillAllocation::query()
            ->where('user_id', $tenant->id)
            ->with('electricityBill.room')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (ElectricityBillAllocation $allocation): array => [
                'period_start' => $allocation->electricityBill?->period_start?->toDateString(),
                'period_end' => $allocation->electricityBill?->period_end?->toDateString(),
                'room_number' => $allocation->electricityBill?->room?->room_number,
                'allocated_amount' => $allocation->allocated_amount,
                'chargeable_days' => $allocation->chargeable_days,
                'approved_absence_days' => $allocation->approved_absence_days,
            ])
            ->all();
    }

    public function maintenanceStatus(User $tenant, int $limit = 20): array
    {
        $this->ensureTenant($tenant);

        return MaintenanceRequest::query()
            ->whereBelongsTo($tenant)
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'title', 'category', 'priority', 'status', 'created_at', 'resolved_at'])
            ->map(fn (MaintenanceRequest $request): array => [
                'reference' => $request->reference,
                'title' => $request->title,
                'category' => $request->category,
                'priority' => $request->priority,
                'status' => $request->status,
                'submitted_at' => $request->created_at?->toDateString(),
                'resolved_at' => $request->resolved_at?->toDateString(),
            ])
            ->all();
    }

    public function absenceStatus(User $tenant, int $limit = 20): array
    {
        $this->ensureTenant($tenant);

        return AbsenceRequest::query()
            ->whereHas('roomAssignment', fn ($query) => $query->whereBelongsTo($tenant))
            ->with('roomAssignment.room')
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'room_assignment_id', 'start_date', 'end_date', 'status', 'reviewed_at'])
            ->map(fn (AbsenceRequest $request): array => [
                'reference' => $request->reference,
                'start_date' => $request->start_date->toDateString(),
                'end_date' => $request->end_date->toDateString(),
                'status' => $request->status,
                'reviewed_at' => $request->reviewed_at?->toDateString(),
            ])
            ->all();
    }

    public function announcements(int $limit = 10): array
    {
        return Announcement::query()
            ->published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_at')
            ->limit($limit)
            ->get(['title', 'category', 'body', 'publish_at', 'ends_at'])
            ->map(fn (Announcement $announcement): array => [
                'title' => $announcement->title,
                'category' => $announcement->category,
                'body' => $announcement->body,
                'published_at' => $announcement->publish_at?->toIso8601String(),
                'ends_at' => $announcement->ends_at?->toDateString(),
            ])
            ->all();
    }

    public function currentUser(?User $user): array
    {
        return [
            'authenticated' => $user !== null,
            'name' => $user?->name,
            'role' => $user?->role ?? 'guest',
        ];
    }

    public function roomAvailability(bool $availableOnly = false, ?int $limit = null): array
    {
        $rooms = Room::query()
            ->withCount([
                'roomAssignments as occupied_assignments_count' => fn ($query) => $query->where('status', 'occupied'),
                'roomAssignments as reserved_assignments_count' => fn ($query) => $query->where('status', 'reserved'),
            ])
            ->orderBy('room_number')
            ->get(['id', 'room_number', 'capacity', 'status'])
            ->map(function (Room $room): array {
                $remainingCapacity = max(
                    0,
                    $room->capacity - $room->occupied_assignments_count - $room->reserved_assignments_count,
                );

                return [
                    'room_number' => $room->room_number,
                    'capacity' => $room->capacity,
                    'occupied_assignments' => $room->occupied_assignments_count,
                    'reserved_assignments' => $room->reserved_assignments_count,
                    'remaining_capacity' => $remainingCapacity,
                    'unassigned_spaces' => $remainingCapacity,
                    'availability_status' => $room->status === 'available' && $remainingCapacity > 0
                        ? 'available'
                        : 'unavailable',
                ];
            });

        if ($availableOnly) {
            $rooms = $rooms->where('availability_status', 'available')->values();
        }

        return ($limit === null ? $rooms : $rooms->take($limit))
            ->values()
            ->all();
    }

    public function adminOverview(): array
    {
        $roomCounts = Room::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $tenantFinancials = User::query()
            ->where('role', 'tenant')
            ->withSum(['transactions as charges_total' => fn ($query) => $query->where('type', 'charge')], 'amount')
            ->withSum(['transactions as payments_total' => fn ($query) => $query->where('type', 'payment')], 'amount')
            ->get(['id']);

        return [
            'rooms_total' => (int) $roomCounts->sum(),
            'rooms_occupied' => (int) ($roomCounts['occupied'] ?? 0),
            'rooms_available' => (int) ($roomCounts['available'] ?? 0),
            'rooms_reserved_or_other' => max(
                (int) $roomCounts->sum() - (int) ($roomCounts['occupied'] ?? 0) - (int) ($roomCounts['available'] ?? 0),
                0,
            ),
            'active_tenants' => $tenantFinancials->count(),
            'pending_applications' => Application::query()->where('status', 'pending')->count(),
            'pending_absence_requests' => AbsenceRequest::query()->where('status', AbsenceRequest::STATUS_PENDING)->count(),
            'pending_maintenance_requests' => MaintenanceRequest::query()->where('status', 'Pending')->count(),
            'outstanding_balance' => round($tenantFinancials->sum(fn (User $tenant): float => max(
                (float) ($tenant->charges_total ?? 0) - (float) ($tenant->payments_total ?? 0),
                0,
            )), 2),
        ];
    }

    public function adminTenants(int $limit = 50): array
    {
        return User::query()
            ->where('role', 'tenant')
            ->with([
                'roomAssignments' => fn ($query) => $query
                    ->whereIn('status', ['reserved', 'occupied'])
                    ->with('room:id,room_number')
                    ->latest('id'),
            ])
            ->withSum(['transactions as charges_total' => fn ($query) => $query->where('type', 'charge')], 'amount')
            ->withSum(['transactions as payments_total' => fn ($query) => $query->where('type', 'payment')], 'amount')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(function (User $tenant): array {
                $assignment = $tenant->roomAssignments->first();

                return [
                    'name' => $tenant->name,
                    'room_number' => $assignment?->room?->room_number,
                    'assignment_status' => $assignment?->status,
                    'outstanding_balance' => round(max(
                        (float) ($tenant->charges_total ?? 0) - (float) ($tenant->payments_total ?? 0),
                        0,
                    ),
                        2),
                ];
            })
            ->all();
    }

    public function adminPendingApplications(int $limit = 20): array
    {
        return Application::query()
            ->with('preferredRoom:id,room_number')
            ->where('status', 'pending')
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'applicant_name', 'preferred_room_id', 'move_in_date', 'created_at'])
            ->map(fn (Application $application): array => [
                'applicant_name' => $application->applicant_name,
                'preferred_room' => $application->preferredRoom?->room_number,
                'move_in_date' => $application->move_in_date,
                'submitted_at' => $application->created_at?->toDateString(),
                'status' => $application->status,
            ])
            ->all();
    }

    public function adminPendingAbsences(int $limit = 20): array
    {
        return AbsenceRequest::query()
            ->with([
                'roomAssignment.user:id,name',
                'roomAssignment.room:id,room_number',
            ])
            ->where('status', AbsenceRequest::STATUS_PENDING)
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'room_assignment_id', 'start_date', 'end_date', 'reason', 'created_at'])
            ->map(fn (AbsenceRequest $request): array => [
                'reference' => $request->reference,
                'tenant_name' => $request->roomAssignment?->user?->name,
                'room_number' => $request->roomAssignment?->room?->room_number,
                'start_date' => $request->start_date->toDateString(),
                'end_date' => $request->end_date->toDateString(),
                'reason' => $request->reason,
                'submitted_at' => $request->created_at?->toDateString(),
            ])
            ->all();
    }

    public function adminMaintenanceRequests(int $limit = 20): array
    {
        return MaintenanceRequest::query()
            ->with([
                'user:id,name',
                'roomAssignment.room:id,room_number',
            ])
            ->whereIn('status', ['Pending', 'In Progress'])
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'user_id', 'room_assignment_id', 'title', 'category', 'priority', 'description', 'status', 'created_at'])
            ->map(fn (MaintenanceRequest $request): array => [
                'reference' => $request->reference,
                'tenant_name' => $request->user?->name,
                'room_number' => $request->roomAssignment?->room?->room_number,
                'title' => $request->title,
                'category' => $request->category,
                'priority' => $request->priority,
                'description' => $request->description,
                'status' => $request->status,
                'submitted_at' => $request->created_at?->toDateString(),
            ])
            ->all();
    }

    public function adminElectricityBills(int $limit = 12): array
    {
        return ElectricityBill::query()
            ->with('room:id,room_number')
            ->withCount('allocations')
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'room_id', 'period_start', 'period_end', 'total_kwh', 'total_amount'])
            ->map(fn (ElectricityBill $bill): array => [
                'room_number' => $bill->room?->room_number,
                'period_start' => $bill->period_start->toDateString(),
                'period_end' => $bill->period_end->toDateString(),
                'total_kwh' => $bill->total_kwh,
                'total_amount' => $bill->total_amount,
                'tenant_allocations' => $bill->allocations_count,
            ])
            ->all();
    }

    private function ensureTenant(User $tenant): void
    {
        if ($tenant->role !== 'tenant') {
            throw new AuthorizationException('This operation is available to tenants only.');
        }
    }
}
