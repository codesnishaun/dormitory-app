<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login page exposes the existing role and credential fields without demo credentials', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('action="'.route('login.store').'"', false)
        ->assertDontSee('Apply for a room')
        ->assertDontSee('© 2026 DORA')
        ->assertSee('name="_token"', false)
        ->assertSee('name="login_role"', false)
        ->assertSee('value="admin"', false)
        ->assertSee('value="tenant"', false)
        ->assertSee('id="email"', false)
        ->assertSee('id="password"', false)
        ->assertSee('aria-label="Show password"', false)
        ->assertDontSee('admin123')
        ->assertDontSee('cozyhaven');
});

test('users can sign in to the dashboard for their selected role', function (string $role, string $dashboardRoute) {
    $user = User::factory()->create([
        'email' => "{$role}@example.test",
        'password' => Hash::make('correct-password'),
        'role' => $role,
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'correct-password',
        'login_role' => $role,
    ]);

    $response->assertRedirectToRoute($dashboardRoute);
    $this->assertAuthenticatedAs($user);
})->with([
    'admin account' => ['admin', 'admin.dashboard'],
    'tenant account' => ['tenant', 'tenant.dashboard'],
]);

test('invalid credentials show a generic error and preserve only the email and role', function () {
    $user = User::factory()->create([
        'email' => 'resident@example.test',
        'password' => Hash::make('correct-password'),
        'role' => 'tenant',
    ]);

    $response = $this->followingRedirects()
        ->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
            'login_role' => 'tenant',
        ])
        ->assertOk()
        ->assertSee('The provided credentials do not match our records.')
        ->assertSee('value="resident@example.test"', false)
        ->assertDontSee('incorrect-password');

    $this->assertGuest();

    expect(substr_count($response->getContent(), 'The provided credentials do not match our records.'))->toBe(1);
    expect(preg_match('/value="tenant"\s+required\s+checked/', $response->getContent()))->toBe(1);
});

test('validation errors are shown beside their fields without echoing a password', function () {
    $response = $this->followingRedirects()
        ->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'not-an-email',
            'password' => '',
            'login_role' => 'invalid-role',
        ])
        ->assertOk()
        ->assertSee('id="email-error"', false)
        ->assertSee('id="password-error"', false)
        ->assertSee('id="role-error"', false)
        ->assertSee('value="not-an-email"', false)
        ->assertSee('aria-invalid="true"', false);
});

test('validation failures preserve the email but never echo the submitted password', function () {
    $response = $this->followingRedirects()
        ->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'not-an-email',
            'password' => 'never-echo-this-password',
            'login_role' => 'admin',
        ])
        ->assertOk()
        ->assertSee('value="not-an-email"', false)
        ->assertSee('id="email-error"', false)
        ->assertDontSee('never-echo-this-password');

    $response->assertStatus(200);
});
