<?php

use App\Models\AbsenceRequest;
use App\Models\Announcement;
use App\Models\ElectricityBill;
use App\Models\ElectricityBillAllocation;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant dashboard shows only their room, account, requests, payments, and current announcements', function () {
    $tenant = User::factory()->create([
        'name' => 'Jordan Resident',
        'role' => 'tenant',
    ]);
    $otherTenant = User::factory()->create([
        'name' => 'Private Other Resident',
        'role' => 'tenant',
    ]);
    $admin = User::factory()->create(['role' => 'admin']);
    $room = Room::query()->create([
        'room_number' => 'R104',
        'capacity' => 2,
        'monthly_rent' => 4200,
        'status' => 'occupied',
    ]);
    $otherRoom = Room::query()->create([
        'room_number' => 'R999',
        'capacity' => 1,
        'monthly_rent' => 9000,
        'status' => 'occupied',
    ]);
    $assignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->subMonth()->toDateString(),
    ]);
    $otherAssignment = RoomAssignment::query()->create([
        'user_id' => $otherTenant->id,
        'room_id' => $otherRoom->id,
        'status' => 'occupied',
        'start_date' => today()->subMonth()->toDateString(),
    ]);
    Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'charge',
        'description' => 'Monthly rent',
        'amount' => 7000,
        'transaction_date' => today()->toDateString(),
    ]);
    Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'payment',
        'description' => 'My recorded payment',
        'amount' => 1000,
        'transaction_date' => today()->toDateString(),
        'payment_method' => 'bank_transfer',
    ]);
    Transaction::query()->create([
        'user_id' => $otherTenant->id,
        'type' => 'payment',
        'description' => 'Other tenant private payment',
        'amount' => 500,
        'transaction_date' => today()->toDateString(),
    ]);
    $bill = ElectricityBill::query()->create([
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
    ElectricityBillAllocation::query()->create([
        'electricity_bill_id' => $bill->id,
        'room_assignment_id' => $assignment->id,
        'user_id' => $tenant->id,
        'tenant_name' => $tenant->name,
        'chargeable_days' => 30,
        'allocated_amount' => '321.50',
    ]);
    AbsenceRequest::query()->create([
        'room_assignment_id' => $assignment->id,
        'start_date' => today()->addDays(4)->toDateString(),
        'end_date' => today()->addDays(6)->toDateString(),
        'reason' => 'Family visit',
        'status' => AbsenceRequest::STATUS_PENDING,
    ]);
    AbsenceRequest::query()->create([
        'room_assignment_id' => $otherAssignment->id,
        'start_date' => today()->addDays(8)->toDateString(),
        'end_date' => today()->addDays(9)->toDateString(),
        'reason' => 'Other tenant private absence',
        'status' => AbsenceRequest::STATUS_PENDING,
    ]);
    MaintenanceRequest::query()->create([
        'user_id' => $tenant->id,
        'room_assignment_id' => $assignment->id,
        'title' => 'My loose window latch',
        'description' => 'The latch will not close.',
        'status' => 'In Progress',
    ]);
    MaintenanceRequest::query()->create([
        'user_id' => $otherTenant->id,
        'room_assignment_id' => $otherAssignment->id,
        'title' => 'Other tenant private repair',
        'description' => 'Private maintenance details.',
        'status' => 'Pending',
    ]);
    Announcement::query()->create([
        'title' => 'Current resident update',
        'category' => 'Community event',
        'body' => 'The monthly meeting is on Friday.',
        'publish_at' => now()->subHour(),
    ]);
    Announcement::query()->create([
        'title' => 'Future announcement',
        'category' => 'Utilities',
        'body' => 'This is not published yet.',
        'publish_at' => now()->addDay(),
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.dashboard'))
        ->assertOk()
        ->assertSee('Welcome, Jordan Resident')
        ->assertSee('R104')
        ->assertSee('₱4,200.00')
        ->assertSee('₱321.50')
        ->assertSee('₱6,000.00')
        ->assertSee('My recorded payment')
        ->assertSee('My loose window latch')
        ->assertSee('In Progress')
        ->assertSee('Current resident update')
        ->assertSee('Submit an absence request')
        ->assertSee('Report a maintenance concern')
        ->assertSee('View billing details')
        ->assertSee('href="'.route('tenant.room-bill').'#account-history-heading"', false)
        ->assertDontSee('Private Other Resident')
        ->assertDontSee('R999')
        ->assertDontSee('Other tenant private payment')
        ->assertDontSee('Other tenant private absence')
        ->assertDontSee('Other tenant private repair')
        ->assertDontSee('Future announcement');
});

test('tenant dashboard shows empty states when the tenant has no related records', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($tenant)
        ->get(route('tenant.dashboard'))
        ->assertOk()
        ->assertSee('Unassigned')
        ->assertSee('No finalized bill for your account')
        ->assertSee('You have not submitted an absence request.')
        ->assertSee('You have not reported a maintenance concern.')
        ->assertSee('No payments have been recorded yet.')
        ->assertSee('There are no current announcements.');
});

test('only tenants can open the tenant dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->get(route('tenant.dashboard'))
        ->assertRedirectToRoute('login');

    $this->actingAs($admin)
        ->get(route('tenant.dashboard'))
        ->assertForbidden();
});
