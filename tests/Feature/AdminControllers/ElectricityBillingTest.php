<?php

use App\Models\AbsenceRequest;
use App\Models\ElectricityBill;
use App\Models\MeterAssignment;
use App\Models\MeterReading;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Submeter;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('admin can assign an active meter and save its initial reading', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R701',
        'capacity' => 2,
        'monthly_rent' => 3500,
        'status' => 'available',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-701',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.meter-assignments.store'), [
            'room_id' => $room->id,
            'submeter_id' => $submeter->id,
            'installed_at' => today()->toDateString(),
            'initial_reading' => '100.000',
        ])
        ->assertRedirectToRoute('admin.meter-assignments.index');

    $assignment = MeterAssignment::query()->sole();

    $this->assertDatabaseHas('meter_readings', [
        'meter_assignment_id' => $assignment->id,
        'reading_value' => '100.000',
        'reading_date' => today()->toDateString(),
        'recorder_by' => $admin->id,
    ]);

    $replacement = Submeter::query()->create([
        'meter_number' => 'MTR-701-NEW',
        'status' => 'active',
    ]);

    $this->from(route('admin.meter-assignments.index'))
        ->post(route('admin.meter-assignments.store'), [
            'room_id' => $room->id,
            'submeter_id' => $replacement->id,
            'installed_at' => today()->toDateString(),
            'initial_reading' => '0.000',
        ])
        ->assertSessionHasErrors([
            'room_id' => 'This room already has an active meter assignment.',
        ]);

    $this->assertDatabaseCount('meter_assignments', 1);
    $this->assertDatabaseCount('meter_readings', 1);
});

test('only admins can open meter assignment management', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->get(route('admin.meter-assignments.index'))
        ->assertRedirectToRoute('login');

    $this->actingAs($tenant)
        ->get(route('admin.meter-assignments.index'))
        ->assertForbidden();
});

test('admin can open meter and electricity billing workflows', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.meter-assignments.index'))
        ->assertOk()
        ->assertSee('Assign a meter')
        ->assertSee('Assignment and reading history');

    $this->get(route('admin.electricity-bills.index'))
        ->assertOk()
        ->assertSee('Prepare a billing cycle')
        ->assertSee('No tariff is preselected.');
});

test('tenant room and bill page contains no other tenant billing snapshots', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);
    $otherTenant = User::factory()->create(['role' => 'tenant']);
    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R707',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-707',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => today()->subDay()->toDateString(),
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '1.000',
        'reading_date' => today()->subDay()->toDateString(),
        'recorder_by' => $admin->id,
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '2.000',
        'reading_date' => today()->toDateString(),
        'recorder_by' => $admin->id,
    ]);
    $roomAssignment = RoomAssignment::query()->create([
        'user_id' => $otherTenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->subDay()->toDateString(),
    ]);
    $bill = ElectricityBill::query()->create([
        'room_id' => $room->id,
        'period_start' => today()->subDay()->toDateString(),
        'period_end' => today()->toDateString(),
        'rate_per_kwh' => '15.0615',
        'total_kwh' => '1.000',
        'total_amount' => '15.06',
        'finalized_by' => $admin->id,
        'finalized_at' => now(),
    ]);
    $bill->allocations()->create([
        'room_assignment_id' => $roomAssignment->id,
        'user_id' => $otherTenant->id,
        'tenant_name' => $otherTenant->name,
        'chargeable_days' => 2,
        'allocated_amount' => '15.06',
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.room-bill'))
        ->assertOk()
        ->assertDontSee('MTR-707')
        ->assertDontSee('R707')
        ->assertSee('No finalized electricity bills are available yet.');
});

test('meter readings must increase and use a later date', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R702',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-702',
        'status' => 'active',
    ]);
    $assignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => today()->subDays(2)->toDateString(),
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $assignment->id,
        'reading_value' => '100.000',
        'reading_date' => today()->subDays(2)->toDateString(),
        'recorder_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.meter-assignments.index'))
        ->post(route('admin.meter-readings.store', $assignment), [
            'reading_date' => today()->subDay()->toDateString(),
            'reading_value' => '99.000',
        ])
        ->assertSessionHasErrors([
            'reading_value' => 'A new meter reading cannot be lower than the previous reading.',
        ]);

    $this->assertDatabaseCount('meter_readings', 1);
});

test('closing a meter assignment records the final reading before ending it', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R703',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-703',
        'status' => 'active',
    ]);
    $assignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => today()->subDays(3)->toDateString(),
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $assignment->id,
        'reading_value' => '10.000',
        'reading_date' => today()->subDays(3)->toDateString(),
        'recorder_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.meter-assignments.close', $assignment), [
            'uninstalled_at' => today()->toDateString(),
            'final_reading' => '25.000',
        ])
        ->assertRedirectToRoute('admin.meter-assignments.index');

    $this->assertDatabaseHas('meter_assignments', [
        'id' => $assignment->id,
        'uninstalled_at' => today()->toDateString(),
    ]);
    $this->assertDatabaseHas('meter_readings', [
        'meter_assignment_id' => $assignment->id,
        'reading_value' => '25.000',
        'reading_date' => today()->toDateString(),
    ]);
});

test('bill calculation keeps replacement meter readings in separate segments', function () {
    $this->travelTo('2026-01-31 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R706',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $oldMeter = Submeter::query()->create([
        'meter_number' => 'MTR-706-OLD',
        'status' => 'active',
    ]);
    $oldAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $oldMeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2025-12-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $oldAssignment->id,
        'reading_value' => '100.000',
        'reading_date' => '2025-12-01',
        'recorder_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.meter-assignments.close', $oldAssignment), [
            'uninstalled_at' => '2025-12-31',
            'final_reading' => '140.000',
        ])
        ->assertRedirectToRoute('admin.meter-assignments.index');

    $newMeter = Submeter::query()->create([
        'meter_number' => 'MTR-706-NEW',
        'status' => 'active',
    ]);

    $this->post(route('admin.meter-assignments.store'), [
        'room_id' => $room->id,
        'submeter_id' => $newMeter->id,
        'installed_at' => '2026-01-01',
        'initial_reading' => '0.000',
    ])->assertRedirectToRoute('admin.meter-assignments.index');

    $newAssignment = MeterAssignment::query()->where('submeter_id', $newMeter->id)->sole();
    MeterReading::query()->create([
        'meter_assignment_id' => $newAssignment->id,
        'reading_value' => '30.000',
        'reading_date' => '2026-01-31',
        'recorder_by' => $admin->id,
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => '2025-12-01',
    ]);

    $response = $this->post(route('admin.electricity-bills.preview'), [
        'room_id' => $room->id,
        'period_start' => '2025-12-01',
        'period_end' => '2026-01-31',
        'rate_per_kwh' => '15.0615',
    ])->assertOk();

    expect($response->viewData('preview')['total_kwh'])->toBe('70.000')
        ->and($response->viewData('preview')['segments'])->toHaveCount(2);

    $this->assertDatabaseCount('electricity_bills', 0);
});

test('bill preview and finalization allocate only approved absence-adjusted person-days', function () {
    $this->travelTo('2026-01-31 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $firstTenant = User::factory()->create(['role' => 'tenant', 'name' => 'First Tenant']);
    $secondTenant = User::factory()->create(['role' => 'tenant', 'name' => 'Second Tenant']);
    $room = Room::query()->create([
        'room_number' => 'R704',
        'capacity' => 2,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-704',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-01-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '100.000',
        'reading_date' => '2026-01-01',
        'recorder_by' => $admin->id,
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '150.000',
        'reading_date' => '2026-01-31',
        'recorder_by' => $admin->id,
    ]);

    $firstAssignment = RoomAssignment::query()->create([
        'user_id' => $firstTenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => '2026-01-01',
    ]);
    $secondAssignment = RoomAssignment::query()->create([
        'user_id' => $secondTenant->id,
        'room_id' => $room->id,
        'status' => 'reserved',
        'start_date' => '2026-01-01',
    ]);
    AbsenceRequest::query()->create([
        'room_assignment_id' => $firstAssignment->id,
        'start_date' => '2026-01-10',
        'end_date' => '2026-01-19',
        'reason' => 'Approved absence',
        'status' => 'approved',
    ]);
    AbsenceRequest::query()->create([
        'room_assignment_id' => $secondAssignment->id,
        'start_date' => '2026-01-05',
        'end_date' => '2026-01-15',
        'reason' => 'Pending absence',
        'status' => 'pending',
    ]);

    $cycle = [
        'room_id' => $room->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'rate_per_kwh' => '15.0615',
    ];
    $previewResponse = $this->actingAs($admin)
        ->post(route('admin.electricity-bills.preview'), $cycle)
        ->assertOk()
        ->assertSee('753.08')
        ->assertSee('Eligible days');
    expect($previewResponse->viewData('preview')['allocations'][0]['chargeable_days'])->toBe(21)
        ->and($previewResponse->viewData('preview')['allocations'][1]['chargeable_days'])->toBe(31);
    $previewToken = $previewResponse->viewData('token');

    $this->post(route('admin.electricity-bills.finalize'), $cycle + [
        'preview_token' => $previewToken,
    ])->assertRedirect();

    $bill = ElectricityBill::query()->sole();

    $this->withSession([
        'electricity_bill_previews' => [$previewToken => $cycle + ['actual_cost' => null]],
    ])->post(route('admin.electricity-bills.finalize'), $cycle + [
        'preview_token' => $previewToken,
    ])->assertRedirectToRoute('admin.electricity-bills.show', $bill);

    $this->assertDatabaseHas('electricity_bills', [
        'id' => $bill->id,
        'total_kwh' => '50.000',
        'rate_per_kwh' => '15.0615',
        'total_amount' => '753.08',
        'preview_token_hash' => hash('sha256', $admin->id.':'.$previewToken),
    ]);
    $this->assertDatabaseHas('electricity_bill_allocations', [
        'electricity_bill_id' => $bill->id,
        'room_assignment_id' => $firstAssignment->id,
        'eligible_days' => 31,
        'approved_absence_days' => 10,
        'chargeable_days' => 21,
        'allocated_amount' => '304.13',
        'rounding_adjustment_cents' => 1,
    ]);
    $this->assertDatabaseHas('electricity_bill_allocations', [
        'electricity_bill_id' => $bill->id,
        'room_assignment_id' => $secondAssignment->id,
        'eligible_days' => 31,
        'approved_absence_days' => 0,
        'chargeable_days' => 31,
        'allocated_amount' => '448.95',
    ]);
    $this->assertDatabaseCount('transactions', 2);

    $this->actingAs($firstTenant)
        ->get(route('tenant.room-bill'))
        ->assertOk()
        ->assertSee('R704')
        ->assertSee('₱304.13')
        ->assertSee('MTR-704');

    expect(fn () => $bill->update(['total_amount' => '1.00']))
        ->toThrow(LogicException::class, 'Finalized electricity bills cannot be changed.');

    $electricityCharge = Transaction::query()->firstOrFail();

    expect(fn () => $electricityCharge->update(['amount' => '1.00']))
        ->toThrow(LogicException::class, 'Electricity bill charges cannot be changed.');

    $allocation = $bill->allocations()->where('room_assignment_id', $firstAssignment->id)->sole();
    expect($allocation->absence_snapshots)->toHaveCount(1)
        ->and($allocation->absence_snapshots[0]['requested_start_date'])->toBe('2026-01-10')
        ->and($allocation->absence_snapshots[0]['eligible_start_date'])->toBe('2026-01-10');

    $firstAssignment->update(['start_date' => '2026-01-10']);
    $firstAssignment->absenceRequests()->sole()->update(['status' => 'rejected']);

    expect(fn () => $meterAssignment->meterReadings()->whereDate('reading_date', '2026-01-01')->sole()->update([
        'reading_value' => '99.000',
    ]))->toThrow(ValidationException::class);

    $this->actingAs($admin)
        ->get(route('admin.electricity-bills.show', $bill))
        ->assertOk()
        ->assertSee('Approved absence #')
        ->assertSee('2026-01-01 – Ongoing')
        ->assertSee('Approved absence days')
        ->assertSee('2026-01-10');

    $this->actingAs($admin)
        ->from(route('admin.electricity-bills.index'))
        ->post(route('admin.electricity-bills.preview'), $cycle)
        ->assertSessionHasErrors('period_start');

    $this->assertDatabaseCount('electricity_bills', 1);
    $this->assertDatabaseCount('transactions', 2);
});

test('bill preview refuses missing meter boundary readings', function () {
    $this->travelTo('2026-02-02 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R705',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-705',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-02-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '10.000',
        'reading_date' => '2026-02-01',
        'recorder_by' => $admin->id,
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => '2026-02-01',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.electricity-bills.index'))
        ->post(route('admin.electricity-bills.preview'), [
            'room_id' => $room->id,
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-02',
            'rate_per_kwh' => '15.0615',
        ])
        ->assertSessionHasErrors([
            'period_end' => 'Meter MTR-705 needs readings on 2026-02-01 and 2026-02-02 before this bill can be previewed.',
        ]);

    $this->assertDatabaseCount('electricity_bills', 0);
    $this->assertDatabaseCount('transactions', 0);
});

test('equal chargeable days receive equal shares of an actual provider bill', function () {
    $this->travelTo('2026-01-31 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R708',
        'capacity' => 3,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-708',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-01-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '100.000',
        'reading_date' => '2026-01-01',
        'recorder_by' => $admin->id,
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '200.000',
        'reading_date' => '2026-01-31',
        'recorder_by' => $admin->id,
    ]);

    foreach (['Equal One', 'Equal Two', 'Equal Three'] as $name) {
        $tenant = User::factory()->create(['role' => 'tenant', 'name' => $name]);
        RoomAssignment::query()->create([
            'user_id' => $tenant->id,
            'room_id' => $room->id,
            'status' => 'occupied',
            'start_date' => '2026-01-01',
        ]);
    }

    $cycle = [
        'room_id' => $room->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'actual_cost' => '1500.00',
    ];
    $preview = $this->actingAs($admin)
        ->post(route('admin.electricity-bills.preview'), $cycle)
        ->assertOk()
        ->assertSee('Actual room utility-bill amount');
    $token = $preview->viewData('token');
    $previewData = $preview->viewData('preview');

    expect($previewData['total_amount'])->toBe('1500.00')
        ->and($previewData['cost_source'])->toBe('provider_bill')
        ->and($previewData['reconciles'])->toBeTrue()
        ->and(array_column($previewData['allocations'], 'eligible_days'))->toBe([31, 31, 31])
        ->and(array_column($previewData['allocations'], 'allocated_amount'))->toBe(['500.00', '500.00', '500.00']);

    $this->post(route('admin.electricity-bills.finalize'), $cycle + [
        'preview_token' => $token,
    ])->assertRedirect();

    $bill = ElectricityBill::query()->sole();
    $this->assertDatabaseHas('electricity_bills', [
        'id' => $bill->id,
        'total_amount' => '1500.00',
        'rate_per_kwh' => null,
        'cost_source' => 'provider_bill',
        'allocation_method' => ElectricityBill::ALLOCATION_METHOD_OCCUPANCY_DAYS,
    ]);
    $this->get(route('admin.electricity-bills.show', $bill))
        ->assertOk()
        ->assertSee('Actual room utility-bill amount');
    $this->get(route('admin.electricity-bills.index'))
        ->assertOk()
        ->assertSee('Actual room utility-bill cost');
    expect($bill->allocations->sum(fn ($allocation): int => (int) str_replace('.', '', $allocation->allocated_amount)))
        ->toBe(150000);
});

test('rounding cents go to the largest remainders and reconcile exactly', function () {
    $this->travelTo('2026-01-02 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R709',
        'capacity' => 3,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-709',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-01-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '0.000',
        'reading_date' => '2026-01-01',
        'recorder_by' => $admin->id,
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '1.000',
        'reading_date' => '2026-01-02',
        'recorder_by' => $admin->id,
    ]);

    foreach (['Rounding One', 'Rounding Two', 'Rounding Three'] as $name) {
        $tenant = User::factory()->create(['role' => 'tenant', 'name' => $name]);
        RoomAssignment::query()->create([
            'user_id' => $tenant->id,
            'room_id' => $room->id,
            'status' => 'occupied',
            'start_date' => '2026-01-01',
        ]);
    }

    $preview = $this->actingAs($admin)
        ->post(route('admin.electricity-bills.preview'), [
            'room_id' => $room->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-02',
            'actual_cost' => '0.05',
        ])
        ->assertOk()
        ->viewData('preview');

    expect(array_column($preview['allocations'], 'allocated_amount'))->toBe(['0.02', '0.02', '0.01'])
        ->and(array_column($preview['allocations'], 'rounding_adjustment_cents'))->toBe([1, 1, 0])
        ->and($preview['allocated_total'])->toBe('0.05')
        ->and($preview['reconciles'])->toBeTrue();
});

test('partial stays and room transfers only allocate days to each assigned room', function () {
    $this->travelTo('2026-01-31 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $firstRoom = Room::query()->create([
        'room_number' => 'R710',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $secondRoom = Room::query()->create([
        'room_number' => 'R711',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);

    foreach ([$firstRoom, $secondRoom] as $index => $room) {
        $submeter = Submeter::query()->create([
            'meter_number' => 'MTR-71'.($index + 1),
            'status' => 'active',
        ]);
        $meterAssignment = MeterAssignment::query()->create([
            'room_id' => $room->id,
            'submeter_id' => $submeter->id,
            'installed_by' => $admin->id,
            'installed_at' => '2026-01-01',
        ]);
        MeterReading::query()->create([
            'meter_assignment_id' => $meterAssignment->id,
            'reading_value' => '0.000',
            'reading_date' => '2026-01-01',
            'recorder_by' => $admin->id,
        ]);
        MeterReading::query()->create([
            'meter_assignment_id' => $meterAssignment->id,
            'reading_value' => '10.000',
            'reading_date' => '2026-01-31',
            'recorder_by' => $admin->id,
        ]);
    }

    $firstAssignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $firstRoom->id,
        'status' => 'ended',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-15',
    ]);
    $secondAssignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $secondRoom->id,
        'status' => 'occupied',
        'start_date' => '2026-01-16',
    ]);

    $firstPreview = $this->actingAs($admin)
        ->post(route('admin.electricity-bills.preview'), [
            'room_id' => $firstRoom->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'actual_cost' => '100.00',
        ])
        ->assertOk()
        ->viewData('preview');
    $secondPreview = $this->post(route('admin.electricity-bills.preview'), [
        'room_id' => $secondRoom->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'actual_cost' => '200.00',
    ])->assertOk()->viewData('preview');

    expect($firstPreview['allocations'][0]['room_assignment_id'])->toBe($firstAssignment->id)
        ->and($firstPreview['allocations'][0]['eligible_days'])->toBe(15)
        ->and($secondPreview['allocations'][0]['room_assignment_id'])->toBe($secondAssignment->id)
        ->and($secondPreview['allocations'][0]['eligible_days'])->toBe(16);

    $firstAssignment->update(['end_date' => null]);
    $this->from(route('admin.electricity-bills.index'))
        ->post(route('admin.electricity-bills.preview'), [
            'room_id' => $firstRoom->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'actual_cost' => '100.00',
        ])
        ->assertSessionHasErrors([
            'room_id' => 'An ended tenant room assignment is missing its move-out date.',
        ]);
});

test('overlapping approved absences are counted once and pending or rejected absences are ignored', function () {
    $this->travelTo('2026-01-10 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R712',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-712',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-01-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '0.000',
        'reading_date' => '2026-01-01',
        'recorder_by' => $admin->id,
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '10.000',
        'reading_date' => '2026-01-10',
        'recorder_by' => $admin->id,
    ]);
    $assignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-10',
    ]);

    foreach ([
        ['2025-12-30', '2026-01-04', 'approved'],
        ['2026-01-03', '2026-01-06', 'approved'],
        ['2026-01-07', '2026-01-10', 'pending'],
        ['2026-01-06', '2026-01-10', 'rejected'],
    ] as [$startDate, $endDate, $status]) {
        AbsenceRequest::query()->create([
            'room_assignment_id' => $assignment->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Test absence',
            'status' => $status,
        ]);
    }

    $preview = $this->actingAs($admin)
        ->post(route('admin.electricity-bills.preview'), [
            'room_id' => $room->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-10',
            'actual_cost' => '100.00',
        ])
        ->assertOk()
        ->viewData('preview');

    expect($preview['allocations'][0]['eligible_days'])->toBe(10)
        ->and($preview['allocations'][0]['approved_absence_days'])->toBe(6)
        ->and($preview['allocations'][0]['chargeable_days'])->toBe(4)
        ->and($preview['allocations'][0]['absence_snapshots'])->toHaveCount(2);
});

test('zero chargeable person-days prevent a bill from being previewed or finalized', function () {
    $this->travelTo('2026-01-05 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R713',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-713',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-01-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '0.000',
        'reading_date' => '2026-01-01',
        'recorder_by' => $admin->id,
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '5.000',
        'reading_date' => '2026-01-05',
        'recorder_by' => $admin->id,
    ]);
    $assignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-05',
    ]);
    AbsenceRequest::query()->create([
        'room_assignment_id' => $assignment->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-05',
        'reason' => 'Full-period absence',
        'status' => 'approved',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.electricity-bills.index'))
        ->post(route('admin.electricity-bills.preview'), [
            'room_id' => $room->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-05',
            'actual_cost' => '25.00',
        ])
        ->assertSessionHasErrors([
            'room_id' => 'No tenant person-days are eligible for allocation during this billing period.',
        ]);

    $this->assertDatabaseCount('electricity_bills', 0);
    $this->assertDatabaseCount('transactions', 0);
});

test('decreasing meter readings prevent a bill from being previewed', function () {
    $this->travelTo('2026-01-02 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R714',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'occupied',
    ]);
    $submeter = Submeter::query()->create([
        'meter_number' => 'MTR-714',
        'status' => 'active',
    ]);
    $meterAssignment = MeterAssignment::query()->create([
        'room_id' => $room->id,
        'submeter_id' => $submeter->id,
        'installed_by' => $admin->id,
        'installed_at' => '2026-01-01',
    ]);
    MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '10.000',
        'reading_date' => '2026-01-01',
        'recorder_by' => $admin->id,
    ]);
    $closingReading = MeterReading::query()->create([
        'meter_assignment_id' => $meterAssignment->id,
        'reading_value' => '11.000',
        'reading_date' => '2026-01-02',
        'recorder_by' => $admin->id,
    ]);
    DB::table('meter_readings')->where('id', $closingReading->id)->update(['reading_value' => '9.000']);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => '2026-01-01',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.electricity-bills.index'))
        ->post(route('admin.electricity-bills.preview'), [
            'room_id' => $room->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-02',
            'actual_cost' => '15.00',
        ])
        ->assertSessionHasErrors([
            'period_end' => 'Meter readings must be non-negative and a closing reading cannot be lower than its opening reading.',
        ]);

    $this->assertDatabaseCount('electricity_bills', 0);
    $this->assertDatabaseCount('transactions', 0);
});

test('tenants cannot view or submit administrative electricity billing actions', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($tenant)
        ->get(route('admin.electricity-bills.index'))
        ->assertForbidden();
    $this->post(route('admin.electricity-bills.preview'), [])
        ->assertForbidden();
    $this->post(route('admin.electricity-bills.finalize'), [])
        ->assertForbidden();

    $this->assertDatabaseCount('electricity_bills', 0);
    $this->assertDatabaseCount('transactions', 0);
});

test('billing requires exactly one explicit provider cost or tariff estimate', function () {
    $this->travelTo('2026-01-31 12:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R715',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'available',
    ]);
    $cycle = [
        'room_id' => $room->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ];

    $this->actingAs($admin)
        ->from(route('admin.electricity-bills.index'))
        ->post(route('admin.electricity-bills.preview'), $cycle)
        ->assertSessionHasErrors(['actual_cost', 'rate_per_kwh']);

    $this->post(route('admin.electricity-bills.preview'), $cycle + [
        'actual_cost' => '100.00',
        'rate_per_kwh' => '15.0000',
    ])->assertSessionHasErrors('actual_cost');

    $this->assertDatabaseCount('electricity_bills', 0);
});
