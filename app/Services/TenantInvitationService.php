<?php

namespace App\Services;

use App\Mail\TenantAccountSetupMail;
use App\Models\Application;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\TenantAccountInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TenantInvitationService
{
    public function createTenantAndInvite(string $name, string $email, User $admin): bool
    {
        [$invitation, $token] = DB::transaction(function () use ($name, $email, $admin) {
            $tenant = new User;
            $tenant->forceFill([
                'name' => $name,
                'email' => $email,
                'role' => 'tenant',
                'password' => Str::random(64),
            ])->save();

            return $this->issueInvitation($tenant, $admin);
        });

        return $this->sendInvitation($invitation, $token);
    }

    /**
     * Confirm a room assignment and email the setup link.
     * Safe to call again for the same application: it never creates a second
     * account or assignment, it only issues a fresh link.
     *
     * Returns true if the email was sent, false if it should be retried.
     */
    public function confirmAssignment(int $applicationId, int $roomId, User $admin): bool
    {
        [$invitation, $token] = DB::transaction(function () use ($applicationId, $roomId, $admin) {
            $application = Application::lockForUpdate()->findOrFail($applicationId);

            if ($application->status !== 'approved') {
                throw ValidationException::withMessages([
                    'application' => 'Only approved applications can be assigned a room.',
                ]);
            }

            if ($application->user_id === null) {
                // First confirmation: assign the room and create the account.
                $room = Room::lockForUpdate()->findOrFail($roomId);

                $occupied = RoomAssignment::where('room_id', $room->id)
                    ->whereIn('status', ['reserved', 'occupied'])
                    ->count();

                if ($occupied >= $room->capacity) {
                    throw ValidationException::withMessages([
                        'room_id' => 'This room has no available capacity.',
                    ]);
                }

                if (User::withTrashed()->where('email', $application->email)->exists()) {
                    throw ValidationException::withMessages([
                        'application' => 'An account with this email already exists.',
                    ]);
                }

                $tenant = new User;
                $tenant->forceFill([
                    'name' => $application->applicant_name,
                    'email' => $application->email,
                    'role' => 'tenant',
                    'password' => Str::random(64), // hashed by the model cast; nobody knows it
                ])->save();

                RoomAssignment::create([
                    'user_id' => $tenant->id,
                    'room_id' => $room->id,
                    'application_id' => $application->id,
                    'status' => 'reserved',
                    'start_date' => $application->move_in_date,
                ]);

                $application->forceFill(['user_id' => $tenant->id])->save();
            } else {
                // Retry: the account and assignment already exist.
                $tenant = User::findOrFail($application->user_id);

                $alreadyActivated = TenantAccountInvitation::where('user_id', $tenant->id)
                    ->whereNotNull('used_at')
                    ->exists();

                if ($alreadyActivated) {
                    throw ValidationException::withMessages([
                        'application' => 'This tenant has already set up their account.',
                    ]);
                }
            }

            return $this->issueInvitation($tenant, $admin);
        });

        return $this->sendInvitation($invitation, $token);
    }

    private function issueInvitation(User $tenant, User $admin): array
    {
        // Retire earlier unused links so only the newest one works.
        TenantAccountInvitation::where('user_id', $tenant->id)
            ->whereNull('used_at')
            ->update(['expires_at' => now()]);

        $token = Str::random(64);

        $invitation = TenantAccountInvitation::create([
            'user_id' => $tenant->id,
            'issued_by' => $admin->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(60),
        ]);

        return [$invitation, $token];
    }

    private function sendInvitation(TenantAccountInvitation $invitation, string $token): bool
    {
        try {
            $setupUrl = route('tenant.setup.show', ['token' => $token]);

            Mail::to($invitation->user->email)->send(new TenantAccountSetupMail($setupUrl));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
