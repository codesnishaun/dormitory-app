<?php

use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('application form lists rooms from the database using their ids', function () {
    $room = Room::query()->create([
        'room_number' => 'R305',
        'capacity' => 2,
        'monthly_rent' => 4250,
        'status' => 'available',
    ]);

    $this->get(route('application_form'))
        ->assertSee('value="'.$room->id.'"', false)
        ->assertSee('Room R305')
        ->assertDontSee('R101');
});

test('application stores the selected room id', function () {
    $room = Room::query()->create([
        'room_number' => 'R305',
        'capacity' => 2,
        'monthly_rent' => 4250,
        'status' => 'available',
    ]);

    $this->post(route('application_form.store'), [
        'applicant_name' => 'Taylor Example',
        'phone' => '555-0100',
        'email' => 'taylor@example.test',
        'preferred_room_id' => $room->id,
        'move_in_date' => today()->toDateString(),
    ])->assertRedirect(route('application_form'));

    $this->assertDatabaseHas('applications', [
        'applicant_name' => 'Taylor Example',
        'preferred_room_id' => $room->id,
    ]);
});

test('admin room index displays room details from the database', function () {
    Room::query()->create([
        'room_number' => 'R305',
        'capacity' => 2,
        'monthly_rent' => 4250,
        'submeter_number' => 'MTR-305',
        'status' => 'reserved',
    ]);

    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.room.index'))
        ->assertSee('R305')
        ->assertSee('Reserved')
        ->assertSee('2 people')
        ->assertSee('₱4,250.00')
        ->assertSee('MTR-305')
        ->assertDontSee('Marianne Santos')
        ->assertDontSee('Preview data');
});

test('admin room index shows an empty state when no rooms exist', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.room.index'))
        ->assertSee('0 rooms')
        ->assertSee('No rooms have been added yet.');
});

test('room seeder creates its rooms without duplicating them when rerun', function () {
    $this->seed(RoomSeeder::class);
    $this->seed(RoomSeeder::class);

    $this->assertDatabaseCount('rooms', 8);
    $this->assertDatabaseHas('rooms', [
        'room_number' => 'R103',
        'capacity' => 2,
        'monthly_rent' => 2800,
        'status' => 'available',
    ]);
});
