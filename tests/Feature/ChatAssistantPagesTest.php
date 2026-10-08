<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins can open the admin DORA workspace', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.ask-dora'));

    $response->assertSee('DORA for admins')
        ->assertSee('Maintenance requests')
        ->assertSee('Billing overview')
        ->assertSee(route('admin.ask-dora'))
        ->assertSee('Preview mode');
});

test('tenants can open the resident DORA workspace', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $response = $this->actingAs($tenant)->get(route('tenant.ask-dora'));

    $response->assertSee('Ask DORA')
        ->assertSee('Understand your bill and common charges.')
        ->assertSee('Preview mode');
});

test('users cannot open the DORA workspace for another role', function (string $role, string $routeName) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'tenant cannot open admin workspace' => ['tenant', 'admin.ask-dora'],
    'admin cannot open tenant workspace' => ['admin', 'tenant.ask-dora'],
]);

test('guests are redirected to login from either DORA workspace', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'admin workspace' => ['admin.ask-dora'],
    'tenant workspace' => ['tenant.ask-dora'],
]);
