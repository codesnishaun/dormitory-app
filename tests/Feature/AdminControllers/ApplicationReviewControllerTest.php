<?php

use App\Models\Application;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\TenantAccountInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin sees remove only after an approved applicant completes account setup', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Room::query()->create([
        'room_number' => 'R502',
        'capacity' => 2,
        'monthly_rent' => 4200,
    ]);
    $incompleteTenant = User::factory()->create(['role' => 'tenant']);
    $completedTenant = User::factory()->create(['role' => 'tenant']);
    $incompleteApplication = Application::query()->create([
        'applicant_name' => 'Incomplete Tenant',
        'email' => $incompleteTenant->email,
        'phone' => '555-0101',
        'move_in_date' => '2026-11-01',
    ]);
    $completedApplication = Application::query()->create([
        'applicant_name' => 'Completed Tenant',
        'email' => $completedTenant->email,
        'phone' => '555-0102',
        'move_in_date' => '2026-11-02',
    ]);

    $incompleteApplication->forceFill([
        'status' => 'approved',
        'user_id' => $incompleteTenant->id,
    ])->save();
    $completedApplication->forceFill([
        'status' => 'approved',
        'user_id' => $completedTenant->id,
    ])->save();

    TenantAccountInvitation::query()->create([
        'user_id' => $incompleteTenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', 'incomplete-setup-token'),
        'expires_at' => now()->addHour(),
    ]);
    TenantAccountInvitation::query()->create([
        'user_id' => $completedTenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', 'completed-setup-token'),
        'expires_at' => now()->addHour(),
        'used_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.tenant.index'))
        ->assertOk()
        ->assertSee('Room R502')
        ->assertSee('action="'.route('applications.destroy', $completedApplication).'"', false)
        ->assertDontSee('action="'.route('applications.destroy', $incompleteApplication).'"', false);
});

test('admin can remove a reviewed application after setup while preserving its room assignment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R501',
        'capacity' => 1,
        'monthly_rent' => 4200,
    ]);
    $application = Application::query()->create([
        'applicant_name' => 'Setup Complete',
        'email' => $tenant->email,
        'phone' => '555-0103',
        'move_in_date' => '2026-11-03',
    ]);
    $application->forceFill([
        'status' => 'approved',
        'user_id' => $tenant->id,
    ])->save();
    $assignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'application_id' => $application->id,
        'status' => 'reserved',
        'start_date' => '2026-11-03',
    ]);
    $invitation = TenantAccountInvitation::query()->create([
        'user_id' => $tenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', 'setup-complete-token'),
        'expires_at' => now()->addHour(),
        'used_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('applications.destroy', $application))
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('success', 'Reviewed application removed.');

    $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    $this->assertDatabaseHas('room_assignments', [
        'id' => $assignment->id,
        'application_id' => null,
    ]);
    $this->assertDatabaseHas('tenant_account_invitations', ['id' => $invitation->id]);
});

test('admin cannot remove a reviewed application before account setup is complete', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $application = Application::query()->create([
        'applicant_name' => 'Setup Incomplete',
        'email' => $tenant->email,
        'phone' => '555-0104',
        'move_in_date' => '2026-11-04',
    ]);
    $application->forceFill([
        'status' => 'approved',
        'user_id' => $tenant->id,
    ])->save();

    TenantAccountInvitation::query()->create([
        'user_id' => $tenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', 'setup-incomplete-token'),
        'expires_at' => now()->addHour(),
    ]);

    $this->actingAs($admin)
        ->delete(route('applications.destroy', $application))
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('error', 'Only approved applications whose tenant has completed account setup can be removed.');

    $this->assertDatabaseHas('applications', ['id' => $application->id]);
});

test('admin cannot remove a rejected application even if its linked account completed setup', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $application = Application::query()->create([
        'applicant_name' => 'Rejected Application',
        'email' => $tenant->email,
        'phone' => '555-0106',
        'move_in_date' => '2026-11-06',
    ]);
    $application->forceFill([
        'status' => 'rejected',
        'user_id' => $tenant->id,
    ])->save();

    TenantAccountInvitation::query()->create([
        'user_id' => $tenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', 'rejected-setup-token'),
        'expires_at' => now()->addHour(),
        'used_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('applications.destroy', $application))
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('error', 'Only approved applications whose tenant has completed account setup can be removed.');

    $this->assertDatabaseHas('applications', ['id' => $application->id]);
});

test('guest is redirected and tenant is forbidden from removing a reviewed application', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);
    $application = Application::query()->create([
        'applicant_name' => 'Protected Application',
        'email' => $tenant->email,
        'phone' => '555-0105',
        'move_in_date' => '2026-11-05',
    ]);

    $this->delete(route('applications.destroy', $application))
        ->assertRedirectToRoute('login');

    $this->actingAs($tenant)
        ->delete(route('applications.destroy', $application))
        ->assertForbidden();

    $this->assertDatabaseHas('applications', ['id' => $application->id]);
});
