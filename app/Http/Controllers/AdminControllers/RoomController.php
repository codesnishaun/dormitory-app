<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
}