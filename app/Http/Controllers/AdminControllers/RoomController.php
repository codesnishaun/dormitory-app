<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
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
            ->orderBy('room_number')
            ->get();

        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $availableRooms = $rooms->where('status', 'available')->count();
        $reservedRooms = $rooms->where('status', 'reserved')->count();

        return view('admin.room.index', compact(
            'rooms',
            'occupiedRooms',
            'availableRooms',
            'reservedRooms',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_number'     => ['required', 'string', 'max:20', 'unique:rooms,room_number'],
            'capacity'        => ['required', 'integer', 'min:1', 'max:10'],
            'monthly_rent'    => ['required', 'numeric', 'min:0'],
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
            'room_number'     => ['required', 'string', 'max:20', Rule::unique('rooms', 'room_number')->ignore($room->id)],
            'capacity'        => ['required', 'integer', 'min:1', 'max:10'],
            'monthly_rent'    => ['required', 'numeric', 'min:0'],
            'submeter_number' => ['nullable', 'string', 'max:50', Rule::unique('rooms', 'submeter_number')->ignore($room->id)],
            'status'          => ['required', Rule::in(['available', 'occupied', 'reserved'])],
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