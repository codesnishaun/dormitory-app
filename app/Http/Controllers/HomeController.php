<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $rooms = Room::query()
            ->select(['id', 'room_number', 'capacity', 'monthly_rent', 'status'])
            ->withCount([
                'roomAssignments as active_assignments_count' => fn (Builder $query) => $query
                    ->whereIn('status', ['reserved', 'occupied']),
            ])
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room): array => [
                'room_number' => $room->room_number,
                'capacity' => $room->capacity,
                'monthly_rent' => $room->monthly_rent,
                'is_available' => $room->status === 'available' && $room->active_assignments_count === 0,
            ]);
        $totalRooms = $rooms->count();
        $openRooms = $rooms->where('is_available', true)->count();
        $dormitoryLocation = config('dormitory');
        $dormitoryCoordinatesConfigured = $dormitoryLocation['latitude'] !== null
            && $dormitoryLocation['longitude'] !== null;

        return view('index', compact(
            'rooms',
            'totalRooms',
            'openRooms',
            'dormitoryLocation',
            'dormitoryCoordinatesConfigured',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
