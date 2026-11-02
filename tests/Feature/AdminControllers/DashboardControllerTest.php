<?php

use App\Models\AbsenceRequest;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\ElectricityBill;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin dashboard summarizes current dormitory records and links to their management pages', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create([
        'name' => 'Avery Resident',
        'role' => 'tenant',
    ]);
    $room = Room::query()->create([
        'room_number' => 'R201',
        'capacity' => 2,
        'monthly_rent' => 4200,
        'status' => 'occupied',
    ]);
    Room::query()->create([
        'room_number' => 'R202',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'available',
    ]);
    Room::query()->create([
        'room_number' => 'R203',
        'capacity' => 1,
        'monthly_rent' => 3500,
        'status' => 'reserved',
    ]);
    $assignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->subMonth()->toDateString(),
    ]);
    Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'charge',
        'description' => 'Monthly rent',
        'amount' => 1500,
        'transaction_date' => today()->toDateString(),
    ]);
    Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'payment',
        'description' => 'Cash payment',
        'amount' => 500,
        'transaction_date' => today()->toDateString(),
    ]);
    $creditTenant = User::factory()->create(['role' => 'tenant']);
    Transaction::query()->create([
        'user_id' => $creditTenant->id,
        'type' => 'charge',
        'description' => 'Utility charge',
        'amount' => 80,
        'transaction_date' => today()->toDateString(),
    ]);
    Transaction::query()->create([
        'user_id' => $creditTenant->id,
        'type' => 'payment',
        'description' => 'Advance payment',
        'amount' => 100,
        'transaction_date' => today()->toDateString(),
    ]);
    Application::query()->create([
        'applicant_name' => 'Pending Applicant',
        'email' => 'applicant@example.test',
        'phone' => '555-0123',
        'move_in_date' => today()->addDays(5)->toDateString(),
    ]);
    AbsenceRequest::query()->create([
        'room_assignment_id' => $assignment->id,
        'start_date' => today()->addDays(4)->toDateString(),
        'end_date' => today()->addDays(6)->toDateString(),
        'reason' => 'Family visit',
        'status' => AbsenceRequest::STATUS_PENDING,
    ]);
    MaintenanceRequest::query()->create([
        'user_id' => $tenant->id,
        'room_assignment_id' => $assignment->id,
        'title' => 'Broken desk lamp',
        'description' => 'The lamp does not turn on.',
        'status' => 'Pending',
    ]);
    ElectricityBill::query()->create([
        'room_id' => $room->id,
        'period_start' => today()->subMonth()->toDateString(),
        'period_end' => today()->subDays(2)->toDateString(),
        'rate_per_kwh' => '3.2000',
        'cost_source' => 'meter_rate',
        'allocation_method' => 'occupancy_days_largest_remainder_v1',
        'total_kwh' => '82.500',
        'total_amount' => '264.00',
        'finalized_by' => $admin->id,
        'finalized_at' => now(),
    ]);
    Announcement::query()->create([
        'title' => 'Water maintenance notice',
        'category' => 'Utilities',
        'body' => 'Please store water in advance.',
        'author_name' => $admin->name,
        'publish_at' => now()->subHour(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Active tenants')
        ->assertSee('>2<', false)
        ->assertSee('Occupied rooms')
        ->assertSee('aria-label="Occupied rooms: 1 of 3"', false)
        ->assertSee('Available rooms')
        ->assertSee('Pending applications')
        ->assertSee('Pending Applicant')
        ->assertSee('Absence requests')
        ->assertSee('Avery Resident')
        ->assertSee('Broken desk lamp')
        ->assertSee('₱1,000.00')
        ->assertSee('Recent electricity billing')
        ->assertSee('R201')
        ->assertSee('Water maintenance notice')
        ->assertSee('href="'.route('admin.room.index').'"', false)
        ->assertSee('href="'.route('admin.billing.index').'"', false)
        ->assertSee('href="'.route('admin.absence-request.index', ['status' => 'pending']).'"', false)
        ->assertDontSee('Aircon not cooling')
        ->assertDontSee('₱1,650.00');
});

test('admin dashboard shows helpful empty states when records are absent', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('No applications are waiting for review.')
        ->assertSee('No absence requests are waiting for approval.')
        ->assertSee('No maintenance requests are awaiting attention.')
        ->assertSee('No finalized electricity bills yet.')
        ->assertSee('No account transactions have been recorded.')
        ->assertSee('No announcements are currently published.');
});

test('only administrators can open the admin dashboard', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->get(route('admin.dashboard'))
        ->assertRedirectToRoute('login');

    $this->actingAs($tenant)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
