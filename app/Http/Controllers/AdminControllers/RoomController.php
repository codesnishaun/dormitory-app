<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;

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
}
