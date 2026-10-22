<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\TenantAccountInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(): View
    {
        $rooms = Room::query()
            ->with([
                'currentMeterAssignment.submeter',
                'roomAssignments' => fn ($query) => $query
                    ->where('status', 'occupied')
                    ->whereHas('user', fn ($query) => $query->whereNull('deleted_at'))
                    ->with([
                        'application',
                        'user.roomAssignments.room',
                        'user.tenantAccountInvitations',
                    ])
                    ->orderBy('start_date')
                    ->orderBy('id'),
            ])
            ->orderBy('room_number')
            ->get();

        $tenantDetails = $rooms
            ->flatMap(fn (Room $room) => $room->roomAssignments->map(function (RoomAssignment $assignment) use ($room): ?array {
                $tenant = $assignment->user;

                if ($tenant === null) {
                    return null;
                }

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'phone' => $assignment->application?->phone ?? 'Not provided',
                    'accountStatus' => $tenant->tenantAccountInvitations
                        ->contains(fn (TenantAccountInvitation $invitation) => $invitation->used_at !== null)
                            ? 'Active'
                            : 'Setup pending',
                    'room' => $room->room_number,
                    'assignmentStatus' => ucfirst($assignment->status),
                    'startDate' => $assignment->start_date?->format('M j, Y') ?? 'Not recorded',
                    'endDate' => $assignment->end_date?->format('M j, Y') ?? 'Current stay',
                    'monthlyRent' => number_format((float) $room->monthly_rent, 2),
                    'submeter' => $room->currentMeterAssignment?->submeter?->meter_number ?? 'Not assigned',
                    'roomHistory' => $tenant->roomAssignments
                        ->map(fn (RoomAssignment $history) => [
                            'room' => $history->room?->room_number ?? 'Room unavailable',
                            'status' => ucfirst($history->status),
                            'startDate' => $history->start_date?->format('M j, Y') ?? 'Not recorded',
                            'endDate' => $history->end_date?->format('M j, Y') ?? 'Ongoing',
                        ])
                        ->values()
                        ->all(),
                ];
            }))
            ->filter()
            ->keyBy('id')
            ->all();

        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $availableRooms = $rooms->where('status', 'available')->count();
        $reservedRooms = $rooms->where('status', 'reserved')->count();

        return view('admin.room.index', compact(
            'rooms',
            'tenantDetails',
            'occupiedRooms',
            'availableRooms',
            'reservedRooms',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:20', 'unique:rooms,room_number'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'submeter_number' => ['nullable', 'string', 'max:50', 'unique:rooms,submeter_number'],
        ]);

        // New rooms start as "available"
        Room::create($data + ['status' => 'available']);

        return redirect()->route('admin.room.index')->with('success', 'Room added.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        // Named error bag so the Add room modal doesn't reopen on edit errors
        $data = $request->validateWithBag('editRoom', [
            'room_number' => ['required', 'string', 'max:20', Rule::unique('rooms', 'room_number')->ignore($room->id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'submeter_number' => ['nullable', 'string', 'max:50', Rule::unique('rooms', 'submeter_number')->ignore($room->id)],
            'status' => ['required', Rule::in(['available', 'occupied', 'reserved'])],
        ]);

        $room->update($data);

        return redirect()->route('admin.room.index')->with('success', 'Room updated.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->status === 'occupied') {
            return redirect()->route('admin.room.index')
                ->with('error', "Room {$room->room_number} is occupied and can't be deleted.");
        }

        try {
            $room->delete();
        } catch (QueryException $e) {
            // Other records (applications, meters, etc.) still point to this room
            return redirect()->route('admin.room.index')
                ->with('error', "Room {$room->room_number} is still linked to other records and can't be deleted.");
        }

        return redirect()->route('admin.room.index')->with('success', 'Room deleted.');
    }
}
