<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests see the application and login navigation links', function () {
    $response = $this->get(route('home'));

    $response->assertSee('Apply for a room')
        ->assertSee(route('login'))
        ->assertDontSee(route('admin.dashboard'))
        ->assertDontSee(route('tenant.dashboard'))
        ->assertDontSee('Log out');
});

test('authenticated users see the dashboard for their role and a logout button', function (string $role, string $dashboardRoute) {
    $user = User::factory()->create(['role' => $role]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSee('Dashboard')
        ->assertSee(route($dashboardRoute))
        ->assertSee('Log out')
        ->assertDontSee('>Apply<span', false)
        ->assertSee('action="'.route('logout').'"', false);
})->with([
    'admin dashboard' => ['admin', 'admin.dashboard'],
    'tenant dashboard' => ['tenant', 'tenant.dashboard'],
]);

test('guest navigation links open their destination pages', function (string $destination) {
    $this->get(route('home'))
        ->assertSee('href="'.route($destination).'"', false);

    $this->get(route($destination))->assertSuccessful();
})->with([
    'application form' => 'application_form',
    'login page' => 'login',
]);

test('admin navigation links open their destination pages', function (string $destination) {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSee('href="'.route($destination).'"', false);

    $this->get(route($destination))->assertSuccessful();
})->with([
    'home logo' => 'home',
    'dashboard' => 'admin.dashboard',
    'tenants' => 'admin.tenant.index',
    'rooms' => 'admin.room.index',
    'billing' => 'admin.billing.index',
    'announcements' => 'admin.announcement.index',
    'maintenance' => 'admin.maintenance.index',
    'ask dora' => 'admin.ask-dora',
]);

test('tenant navigation links open their destination pages', function (string $destination) {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($tenant)
        ->get(route('tenant.dashboard'))
        ->assertSee('href="'.route($destination).'"', false);

    $this->get(route($destination))->assertSuccessful();
})->with([
    'home logo' => 'home',
    'dashboard' => 'tenant.dashboard',
    'room and bill' => 'tenant.room-bill',
    'announcements' => 'tenant.announcements',
    'maintenance' => 'tenant.maintenance',
    'ask dora' => 'tenant.ask-dora',
]);

test('authenticated users can use the logout navigation button', function (string $role, string $dashboardRoute) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route($dashboardRoute))
        ->assertSee('action="'.route('logout').'"', false);

    $this->post(route('logout'))
        ->assertRedirectToRoute('home');

    $this->assertGuest();
})->with([
    'admin logout' => ['admin', 'admin.dashboard'],
    'tenant logout' => ['tenant', 'tenant.dashboard'],
]);
