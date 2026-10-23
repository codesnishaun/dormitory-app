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
    'submeters' => 'admin.submeter.index',
    'meter assignments' => 'admin.meter-assignments.index',
    'billing' => 'admin.billing.index',
    'electricity bills' => 'admin.electricity-bills.index',
    'absence requests' => 'admin.absence-request.index',
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
    'absence requests' => 'tenant.absence-request',
    'announcements' => 'tenant.announcements',
    'maintenance' => 'tenant.maintenance',
    'ask dora' => 'tenant.ask-dora',
]);

test('admin and tenant sidebars show role-specific navigation groups and active styling', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);

    $this->actingAs($admin)
        ->get(route('admin.electricity-bills.index'))
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('Dormitory management')
        ->assertSee('Utilities & billing')
        ->assertSee('Operations')
        ->assertSee('Communication')
        ->assertSee('border-l-copper', false)
        ->assertSee('peer-checked:flex', false)
        ->assertSee('md:w-72', false)
        ->assertSee('max-h-dvh', false)
        ->assertSee('overflow-y-auto', false)
        ->assertSee('overscroll-contain', false)
        ->assertDontSee('My stay')
        ->assertSee('Electricity bills')
        ->assertSee('Absence requests');

    $this->actingAs($tenant)
        ->get(route('tenant.room-bill'))
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('My stay')
        ->assertSee('Services')
        ->assertSee('Communication')
        ->assertSee('border-l-copper', false)
        ->assertSee('peer-checked:flex', false)
        ->assertSee('md:w-72', false)
        ->assertSee('max-h-dvh', false)
        ->assertSee('overflow-y-auto', false)
        ->assertSee('overscroll-contain', false)
        ->assertSee('Absence requests')
        ->assertDontSee('Dormitory management')
        ->assertDontSee('Electricity bills')
        ->assertDontSee('Meter assignments');
});

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
