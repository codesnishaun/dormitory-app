<?php

use App\Mail\TenantAccountSetupMail;
use App\Models\TenantAccountInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant account setup page renders a styled password form for a valid invitation', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $token = 'tenant-setup-token';

    TenantAccountInvitation::query()->create([
        'user_id' => $tenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addHour(),
    ]);

    $this->get(route('tenant.setup.show', ['token' => $token]))
        ->assertOk()
        ->assertSee('Set up your account')
        ->assertSee('Create password')
        ->assertSee('Confirm password')
        ->assertSee('name="password"', false)
        ->assertSee('minlength="12"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertSee('action="'.route('tenant.setup.store', ['token' => $token]).'"', false);
});

test('tenant can set a password through the account setup form', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $token = 'tenant-password-token';

    $invitation = TenantAccountInvitation::query()->create([
        'user_id' => $tenant->id,
        'issued_by' => $admin->id,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addHour(),
    ]);

    $this->post(route('tenant.setup.store', ['token' => $token]), [
        'password' => 'a-secure-tenant-password',
        'password_confirmation' => 'a-secure-tenant-password',
    ])->assertRedirectToRoute('login')
        ->assertSessionHas('status', 'Your password has been set. You can now log in.');

    $this->assertDatabaseHas('tenant_account_invitations', [
        'id' => $invitation->id,
        'used_at' => $invitation->fresh()->used_at,
    ]);
    expect(password_verify('a-secure-tenant-password', $tenant->fresh()->password))->toBeTrue();
});

test('tenant setup email includes a branded call to action and expiry details', function () {
    $html = (new TenantAccountSetupMail('https://example.test/tenant/setup/token'))->render();

    expect($html)
        ->toContain('Welcome to DORA')
        ->toContain('Set up my account')
        ->toContain('https://example.test/tenant/setup/token')
        ->toContain('expires in 60 minutes');
});
