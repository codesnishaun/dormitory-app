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
