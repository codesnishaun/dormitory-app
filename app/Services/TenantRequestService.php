<?php

namespace App\Services;

use App\Models\AbsenceRequest;
use App\Models\MaintenanceRequest;
use App\Models\RoomAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantRequestService
{
    public function createAbsenceRequest(User $tenant, array $input): AbsenceRequest
    {
        $this->ensureTenant($tenant);
        $data = $this->validateAbsenceRequest($input);

        return DB::transaction(function () use ($tenant, $data): AbsenceRequest {
            $assignment = $this->activeAssignment($tenant, 'reason');

            $overlappingRequestExists = AbsenceRequest::query()
                ->where('room_assignment_id', $assignment->id)
                ->whereIn('status', [AbsenceRequest::STATUS_PENDING, AbsenceRequest::STATUS_APPROVED])
                ->whereDate('start_date', '<=', $data['end_date'])
                ->whereDate('end_date', '>=', $data['start_date'])
                ->exists();

            if ($overlappingRequestExists) {
                throw ValidationException::withMessages([
                    'start_date' => 'These dates overlap an existing pending or approved absence request.',
                ]);
            }

            return $assignment->absenceRequests()->create([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'],
                'remarks' => $data['remarks'] ?? null,
                'status' => AbsenceRequest::STATUS_PENDING,
            ]);
        }, attempts: 3);
    }

    /**
     * @return array{start_date: string, end_date: string, reason: string, remarks?: string|null}
     */
    public function validateAbsenceRequest(array $input): array
    {
        return Validator::make($input, [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ])->validate();
    }

    public function createMaintenanceRequest(User $tenant, array $input): MaintenanceRequest
    {
        $this->ensureTenant($tenant);
        $data = $this->validateMaintenanceRequest($input);

        return DB::transaction(function () use ($tenant, $data): MaintenanceRequest {
            $assignment = $this->activeAssignment($tenant, 'title');

            return MaintenanceRequest::query()->create([
                'user_id' => $tenant->id,
                'room_assignment_id' => $assignment->id,
                'title' => $data['title'],
                'category' => $data['category'],
                'priority' => $data['priority'],
                'description' => $data['description'],
                'status' => 'Pending',
            ]);
        }, attempts: 3);
    }

    /**
     * @return array{title: string, category: string, priority: string, description: string}
     */
    public function validateMaintenanceRequest(array $input): array
    {
        return Validator::make($input, [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(MaintenanceRequest::CATEGORIES)],
            'priority' => ['required', Rule::in(MaintenanceRequest::PRIORITIES)],
            'description' => ['required', 'string', 'max:2000'],
        ])->validate();
    }

    private function activeAssignment(User $tenant, string $errorKey): RoomAssignment
    {
        $assignment = $tenant->roomAssignments()
            ->whereIn('status', ['reserved', 'occupied'])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($assignment === null) {
            throw ValidationException::withMessages([
                $errorKey => 'You need an active room assignment before you can submit this request.',
            ]);
        }

        return $assignment;
    }

    private function ensureTenant(User $user): void
    {
        if ($user->role !== 'tenant') {
            throw new AuthorizationException('This operation is available to tenants only.');
        }
    }
}
