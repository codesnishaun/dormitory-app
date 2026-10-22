<?php

use App\Mail\TenantAccountSetupMail;
use App\Models\Application;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\TenantAccountInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('admin tenant index shows tenant records and their room assignment from the database', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create([
        'name' => 'Jordan Tenant',
        'email' => 'jordan@example.test',
    ]);
    $room = Room::query()->create([
        'room_number' => 'R401',
        'capacity' => 2,
        'monthly_rent' => 4200,
    ]);
    $application = Application::query()->create([
        'applicant_name' => 'Jordan Tenant',
        'email' => 'jordan@example.test',
        'phone' => '555-0142',
        'move_in_date' => '2026-10-01',
        'user_id' => $tenant->id,
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'application_id' => $application->id,
        'status' => 'occupied',
        'start_date' => '2026-10-01',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.tenant.index'))
        ->assertOk()
        ->assertSee('Jordan Tenant')
        ->assertSee('jordan@example.test')
        ->assertSee('R401')
        ->assertSee('555-0142')
        ->assertSee('Occupied')
        ->assertDontSee($admin->name)
        ->assertDontSee('Marianne Santos')
        ->assertSee('data-tenant-edit', false)
        ->assertSee('data-tenant-archive', false)
        ->assertSee('Archive tenant account?')
        ->assertSee('name="_method" value="DELETE"', false)
        ->assertSee('Add a tenant')
        ->assertSee('action="'.route('admin.tenant.store').'"', false)
        ->assertSee('add-tenant-modal', false);
});

test('admin can add a tenant account and send its setup invitation', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.tenant.store'), [
            'name' => 'Taylor Guest',
            'email' => 'taylor@example.test',
        ])
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('success', 'Tenant account added and setup invitation sent.');

    $tenant = User::query()->where('email', 'taylor@example.test')->firstOrFail();

    $this->assertDatabaseHas('users', [
        'id' => $tenant->id,
        'name' => 'Taylor Guest',
        'email' => 'taylor@example.test',
        'role' => 'tenant',
    ]);
    $this->assertDatabaseHas('tenant_account_invitations', [
        'user_id' => $tenant->id,
        'issued_by' => $admin->id,
    ]);
    Mail::assertSent(TenantAccountSetupMail::class);
});

test('admin cannot add a tenant with an email that already belongs to an account', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $existingTenant = User::factory()->create([
        'role' => 'tenant',
        'email' => 'existing@example.test',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.tenant.index'))
        ->followingRedirects()
        ->post(route('admin.tenant.store'), [
            'name' => 'Guest Applicant',
            'email' => $existingTenant->email,
        ]);

    $response->assertOk();

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('tenant_account_invitations', 0);
    Mail::assertNotSent(TenantAccountSetupMail::class);

    $response
        ->assertSee('add-tenant-modal', false)
        ->assertSee('showModal()', false)
        ->assertSee('An account with this email already exists. Enter a different email.')
        ->assertSee('existing@example.test');
});

test('tenant account creation requires a name and email', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.tenant.store'), [])
        ->assertSessionHasErrorsIn('addTenant', [
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
        ]);

    $this->assertDatabaseCount('users', 1);
});

test('tenant account creation rejects an invalid email address', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.tenant.store'), [
            'name' => 'Taylor Guest',
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrorsIn('addTenant', ['email' => 'The email field must be a valid email address.']);

    $this->assertDatabaseCount('users', 1);
});

test('admin can update a tenant name and email', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($admin)
        ->put(route('admin.tenant.update', $tenant), [
            'editing_id' => $tenant->id,
            'name' => 'Updated Tenant',
            'email' => 'updated@example.test',
            'role' => 'admin',
        ])
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('success', 'Tenant updated.');

    $this->assertDatabaseHas('users', [
        'id' => $tenant->id,
        'name' => 'Updated Tenant',
        'email' => 'updated@example.test',
        'role' => 'tenant',
    ]);
});

test('tenant update validates required fields and unique email addresses', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $existingUser = User::factory()->create(['email' => 'taken@example.test']);

    $this->actingAs($admin)
        ->put(route('admin.tenant.update', $tenant), [
            'editing_id' => $tenant->id,
            'name' => '',
            'email' => $existingUser->email,
        ])
        ->assertSessionHasErrorsIn('editTenant', [
            'name' => 'The name field is required.',
            'email' => 'The email has already been taken.',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $tenant->id,
        'name' => $tenant->name,
        'email' => $tenant->email,
    ]);
});

test('tenant update rejects a mismatched record id', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $otherTenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($admin)
        ->put(route('admin.tenant.update', $tenant), [
            'editing_id' => $otherTenant->id,
            'name' => 'Changed Name',
            'email' => 'changed@example.test',
        ])
        ->assertSessionHasErrorsIn('editTenant', ['editing_id']);

    $this->assertDatabaseHas('users', [
        'id' => $tenant->id,
        'name' => $tenant->name,
        'email' => $tenant->email,
    ]);
});

test('admin can archive a tenant while preserving its application and invitation history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $application = Application::query()->create([
        'applicant_name' => $tenant->name,
        'email' => $tenant->email,
        'phone' => '555-0133',
        'move_in_date' => '2026-10-01',
    ]);
    $application->forceFill(['user_id' => $tenant->id])->save();
    $invitation = TenantAccountInvitation::query()->create([
        'user_id' => $tenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', 'tenant-token'),
        'expires_at' => now()->addHour(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.tenant.destroy', $tenant))
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('success', 'Tenant archived. Room assignment and account history have been preserved.');

    $this->assertSoftDeleted('users', ['id' => $tenant->id]);
    $this->assertDatabaseHas('tenant_account_invitations', ['id' => $invitation->id]);
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'user_id' => $tenant->id,
        'applicant_name' => $tenant->name,
    ]);

    $this->get(route('admin.tenant.index'))
        ->assertSee('Archived accounts')
        ->assertSee($tenant->email)
        ->assertSee('Restore account');
});

test('admin can archive a tenant with ended room assignment history', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R402',
        'capacity' => 1,
        'monthly_rent' => 3500,
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'ended',
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-01',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.tenant.destroy', $tenant))
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('success', 'Tenant archived. Room assignment and account history have been preserved.');

    $this->assertSoftDeleted('users', ['id' => $tenant->id]);
    $this->assertDatabaseHas('room_assignments', [
        'user_id' => $tenant->id,
        'status' => 'ended',
    ]);
});

test('admin cannot archive a tenant with an active room assignment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $room = Room::query()->create([
        'room_number' => 'R403',
        'capacity' => 1,
        'monthly_rent' => 3500,
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => '2026-06-01',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.tenant.destroy', $tenant))
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('error', 'This tenant still has an active or reserved room assignment. End or cancel it before archiving this account.');

    $this->assertModelExists($tenant);
    $this->assertDatabaseHas('room_assignments', [
        'user_id' => $tenant->id,
        'status' => 'occupied',
    ]);
});

test('admin can restore an archived tenant account', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $tenant->delete();

    $this->actingAs($admin)
        ->patch(route('admin.tenant.restore', $tenant->id))
        ->assertRedirectToRoute('admin.tenant.index')
        ->assertSessionHas('success', 'Tenant account restored.');

    $this->assertModelExists($tenant);
});

test('archived tenant accounts cannot sign in', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);
    $tenant->delete();

    $this->post(route('login'), [
        'email' => $tenant->email,
        'password' => 'password',
        'login_role' => 'tenant',
    ])->assertSessionHasErrors([
        'email' => 'The provided credentials do not match our records.',
    ]);
});

test('guest is redirected when accessing tenant administration', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->get(route('admin.tenant.index'))
        ->assertRedirectToRoute('login');

    $this->put(route('admin.tenant.update', $tenant), [
        'editing_id' => $tenant->id,
        'name' => 'Changed Name',
        'email' => 'changed@example.test',
    ])->assertRedirectToRoute('login');

    $this->post(route('admin.tenant.store'), [
        'name' => 'Guest Applicant',
        'email' => 'guest@example.test',
    ])->assertRedirectToRoute('login');

    $this->delete(route('admin.tenant.destroy', $tenant))
        ->assertRedirectToRoute('login');

    $this->assertDatabaseMissing('users', ['email' => 'guest@example.test']);
});

test('tenant cannot access tenant administration actions', function () {
    $currentTenant = User::factory()->create(['role' => 'tenant']);
    $otherTenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($currentTenant)
        ->get(route('admin.tenant.index'))
        ->assertForbidden();

    $this->put(route('admin.tenant.update', $otherTenant), [
        'editing_id' => $otherTenant->id,
        'name' => 'Changed Name',
        'email' => 'changed@example.test',
    ])->assertForbidden();

    $this->post(route('admin.tenant.store'), [
        'name' => 'Guest Applicant',
        'email' => 'guest@example.test',
    ])->assertForbidden();

    $this->delete(route('admin.tenant.destroy', $otherTenant))
        ->assertForbidden();

    $this->patch(route('admin.tenant.restore', $otherTenant->id))
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $otherTenant->id,
        'role' => 'tenant',
    ]);
    $this->assertDatabaseMissing('users', ['email' => 'guest@example.test']);
});

test('admin tenant actions cannot edit or delete an administrator account', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $anotherAdmin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('admin.tenant.update', $anotherAdmin), [
            'editing_id' => $anotherAdmin->id,
            'name' => 'Changed Admin',
            'email' => 'changed-admin@example.test',
        ])
        ->assertNotFound();

    $this->delete(route('admin.tenant.destroy', $anotherAdmin))
        ->assertNotFound();

    $this->assertDatabaseHas('users', [
        'id' => $anotherAdmin->id,
        'role' => 'admin',
        'name' => $anotherAdmin->name,
    ]);
});
