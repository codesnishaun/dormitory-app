<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $savedRooms = Room::orderBy('id')->get()
            ->map(fn (Room $room) => [
                'number'    => $room->room_number,
                'floor'     => 'Floor ' . $room->floor_number,
                'type'      => $room->capacity > 1 ? 'Shared' : 'Single',
                'rent'      => (float) $room->monthly_rent,
                'tenants'   => [],
                'occupancy' => $room->status === 'available' ? 'Vacant' : '0/' . $room->capacity . ' occupied',
                'status'    => match ($room->status) {
                    'available'   => 'vacant',
                    'reading_due' => 'reading_due',
                    default       => 'occupied',
                },
                'current' => 0, 'previous' => 0, 'usage' => 0,
                'percent' => 0, 'rate' => 12, 'updated' => '—',
            ])
            ->all();

        return view('admin.room.index', compact('savedRooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_number'     => ['required', 'string', 'max:20', 'unique:rooms,room_number'],
            'floor_number'    => ['required', 'integer', 'min:1', 'max:50'],
            'capacity'        => ['required', 'integer', 'min:1', 'max:10'],
            'monthly_rent'    => ['required', 'numeric', 'min:0'],
            'submeter_number' => ['nullable', 'string', 'max:50', 'unique:rooms,submeter_number'],
        ]);

        Room::create($data); 

        return redirect()->route('admin.room.index')->with('success', 'Room added.');
    }
}