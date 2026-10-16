<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can navigate to room management from the sidebar', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.room.index'))
        ->assertSee('href="'.route('admin.room.index').'"', false)
        ->assertSee('Rooms & Meters')
        ->assertSee('All rooms')
        ->assertSee('Add room')
        ->assertSee('action="'.route('admin.room.store').'"', false);
});

test('guest is redirected to login when opening room management', function () {
    $this->get(route('admin.room.index'))
        ->assertRedirectToRoute('login');
});

test('tenant cannot open room management', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($tenant)
        ->get(route('admin.room.index'))
        ->assertForbidden();
});

test('admin can create a room that appears in the room inventory', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.room.store'), [
            'room_number' => 'R305',
            'capacity' => 2,
            'monthly_rent' => 4250,
            'submeter_number' => 'MTR-305',
        ])
        ->assertRedirectToRoute('admin.room.index');

    $this->assertDatabaseHas('rooms', [
        'room_number' => 'R305',
        'capacity' => 2,
        'monthly_rent' => 4250,
        'submeter_number' => 'MTR-305',
        'status' => 'available',
    ]);

    $this->get(route('admin.room.index'))
        ->assertSee('R305')
        ->assertSee('Room added.');
});

test('guest cannot create a room', function () {
    $this->post(route('admin.room.store'), [
        'room_number' => 'R305',
        'capacity' => 2,
        'monthly_rent' => 4250,
    ])->assertRedirectToRoute('login');

    $this->assertDatabaseMissing('rooms', ['room_number' => 'R305']);
});

test('tenant cannot create a room', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($tenant)
        ->post(route('admin.room.store'), [
            'room_number' => 'R305',
            'capacity' => 2,
            'monthly_rent' => 4250,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('rooms', ['room_number' => 'R305']);
});

test('room creation requires its room number capacity and monthly rent', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.room.store'), [])
        ->assertInvalid([
            'room_number' => 'The room number field is required.',
            'capacity' => 'The capacity field is required.',
            'monthly_rent' => 'The monthly rent field is required.',
        ]);

    $this->assertDatabaseCount('rooms', 0);
});

test('room creation rejects invalid field values', function (string $field, mixed $value, string $message) {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.room.store'), [
            'room_number' => 'R305',
            'capacity' => 2,
            'monthly_rent' => 4250,
            $field => $value,
        ])
        ->assertInvalid([$field => $message]);

    $this->assertDatabaseCount('rooms', 0);
})->with([
    'capacity below minimum' => ['capacity', 0, 'The capacity field must be at least 1.'],
    'capacity above maximum' => ['capacity', 11, 'The capacity field must not be greater than 10.'],
    'non-integer capacity' => ['capacity', '1.5', 'The capacity field must be an integer.'],
    'non-numeric monthly rent' => ['monthly_rent', 'invalid', 'The monthly rent field must be a number.'],
    'negative monthly rent' => ['monthly_rent', -1, 'The monthly rent field must be at least 0.'],
    'room number exceeds maximum length' => ['room_number', str_repeat('R', 21), 'The room number field must not be greater than 20 characters.'],
    'submeter number exceeds maximum length' => ['submeter_number', str_repeat('M', 51), 'The submeter number field must not be greater than 50 characters.'],
]);

test('room creation rejects a room number that already exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Room::query()->create([
        'room_number' => 'R305',
        'capacity' => 2,
        'monthly_rent' => 4250,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.room.store'), [
            'room_number' => 'R305',
            'capacity' => 1,
            'monthly_rent' => 3000,
        ])
        ->assertInvalid([
            'room_number' => 'The room number has already been taken.',
        ]);

    $this->assertDatabaseCount('rooms', 1);
});

test('room creation rejects a submeter number that already exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Room::query()->create([
        'room_number' => 'R305',
        'capacity' => 2,
        'monthly_rent' => 4250,
        'submeter_number' => 'MTR-305',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.room.store'), [
            'room_number' => 'R306',
            'capacity' => 1,
            'monthly_rent' => 3000,
            'submeter_number' => 'MTR-305',
        ])
        ->assertInvalid([
            'submeter_number' => 'The submeter number has already been taken.',
        ]);

    $this->assertDatabaseCount('rooms', 1);
});
