<?php

use App\Models\MeterAssignment;
use App\Models\Room;
use App\Models\Submeter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('admin can view the submeter listing', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Submeter::query()->create([
        'meter_number' => 'MTR-1001',
        'status' => 'active',
        'notes' => 'Installed in building A',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.submeter.index'))
        ->assertOk()
        ->assertSee('Submeters')
        ->assertSee('MTR-1001');
});

test('add submeter validation errors reopen the modal and retain submitted values', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->from(route('admin.submeter.index'))
        ->followingRedirects()
        ->post(route('admin.submeter.store'), [
            'meter_number' => 'MTR-INVALID',
            'status' => 'unknown',
            'notes' => 'Needs review',
        ])
        ->assertOk()
        ->assertSee('id="add-submeter-modal"', false)
        ->assertSee('document.getElementById(\'add-submeter-modal\').showModal();', false)
        ->assertSee('The selected status is invalid.')
        ->assertSee('value="MTR-INVALID"', false)
        ->assertSee('Needs review');
});

test('admin can create a submeter with a unique meter number', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.submeter.store'), [
            'meter_number' => 'MTR-2044',
            'status' => 'active',
            'notes' => 'New meter in the east wing',
        ])
        ->assertRedirectToRoute('admin.submeter.index');

    $this->assertDatabaseHas('submeters', [
        'meter_number' => 'MTR-2044',
        'status' => 'active',
    ]);
});

test('duplicate meter numbers are rejected', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Submeter::query()->create([
        'meter_number' => 'MTR-7890',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.submeter.store'), [
            'meter_number' => 'MTR-7890',
            'status' => 'inactive',
        ])
        ->assertInvalid([
            'meter_number' => 'The meter number has already been taken.',
        ]);
});

test('invalid values are rejected', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.submeter.store'), [
            'meter_number' => str_repeat('M', 51),
            'status' => 'archived',
            'notes' => str_repeat('x', 1001),
        ])
        ->assertInvalid([
            'meter_number' => 'The meter number field must not be greater than 50 characters.',
            'status' => 'The selected status is invalid.',
            'notes' => 'The notes field must not be greater than 1000 characters.',
        ]);
});

test('tenant and unauthenticated users cannot manage submeters', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->get(route('admin.submeter.index'))->assertRedirectToRoute('login');

    $this->actingAs($tenant)
        ->get(route('admin.submeter.index'))
        ->assertForbidden();

    $this->actingAs($tenant)
        ->post(route('admin.submeter.store'), [
            'meter_number' => 'MTR-9999',
        ])
        ->assertForbidden();
});

test('retirement refuses active assignments and retired meters stay protected', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $meter = Submeter::query()->create([
        'meter_number' => 'MTR-3333',
        'status' => 'active',
    ]);
    $room = Room::query()->create([
        'room_number' => 'R301',
        'capacity' => 2,
        'monthly_rent' => 3400,
        'status' => 'occupied',
    ]);
    MeterAssignment::query()->create([
        'submeter_id' => $meter->id,
        'room_id' => $room->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-01-01',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.submeter.retire', $meter))
        ->assertRedirectToRoute('admin.submeter.index');

    $this->assertDatabaseHas('submeters', [
        'id' => $meter->id,
        'status' => 'active',
    ]);

    expect(fn () => $meter->update(['status' => 'retired']))
        ->toThrow(ValidationException::class, 'Close the active room assignment before deactivating or retiring this submeter.');

    $meter->meterAssignments()->update(['uninstalled_at' => '2026-01-31']);
    $meter->update(['status' => 'retired']);

    expect(fn () => MeterAssignment::query()->create([
        'submeter_id' => $meter->id,
        'room_id' => $room->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-02-03',
    ]))->toThrow(ValidationException::class, 'Only active submeters can be assigned to a room.');
});
