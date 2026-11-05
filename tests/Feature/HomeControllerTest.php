<?php

use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

test('home page shows accurate room details without exposing tenant occupancy', function () {
    $availableRoom = Room::query()->create([
        'room_number' => 'R101',
        'capacity' => 2,
        'monthly_rent' => 4250,
        'status' => 'available',
    ]);
    $reservedRoom = Room::query()->create([
        'room_number' => 'R102',
        'capacity' => 2,
        'monthly_rent' => 5000,
        'status' => 'reserved',
    ]);
    $roomWithStaleAvailableStatus = Room::query()->create([
        'room_number' => 'R103',
        'capacity' => 2,
        'monthly_rent' => 5250,
        'status' => 'available',
    ]);
    foreach ([
        [$reservedRoom, 'reserved'],
        [$roomWithStaleAvailableStatus, 'occupied'],
    ] as [$room, $assignmentStatus]) {
        $tenant = User::factory()->create([
            'name' => 'Private Tenant Name',
            'role' => 'tenant',
        ]);

        RoomAssignment::query()->create([
            'user_id' => $tenant->id,
            'room_id' => $room->id,
            'status' => $assignmentStatus,
            'start_date' => today()->toDateString(),
        ]);
    }

    $response = $this->get(route('home'))->assertOk();

    $response
        ->assertViewHas('rooms', fn (Collection $rooms): bool => $rooms->pluck('is_available', 'room_number')->all() === [
            'R101' => true,
            'R102' => false,
            'R103' => false,
        ])
        ->assertViewHas('totalRooms', 3)
        ->assertViewHas('openRooms', 1)
        ->assertSee('R101')
        ->assertSee('R102')
        ->assertSee('R103')
        ->assertSee('₱4,250.00')
        ->assertSee('Available')
        ->assertSee('Not available')
        ->assertDontSee('Private Tenant Name');
});

test('home page shows an empty room state and keeps existing guest actions available', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Room information is not available yet.')
        ->assertSee(route('application_form'), false)
        ->assertSee(route('login'), false);
});
