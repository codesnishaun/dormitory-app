<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins can open the admin DORA workspace', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.ask-dora'));

    $response->assertSee('DORA for admins')
        ->assertSee('Summarize current room occupancy')
        ->assertSee('Which rooms are currently available?')
        ->assertSee('Show currently published announcements')
        ->assertSee('aria-label="DORA conversation history"', false)
        ->assertSee('data-chat-conversation-list', false)
        ->assertDontSee('Summarize open maintenance requests')
        ->assertSee('Billing overview')
        ->assertSee(route('admin.ask-dora'))
        ->assertSee('AI unavailable');
});

test('tenants can open the resident DORA workspace', function () {
    $tenant = User::factory()->create(['role' => 'tenant']);

    $response = $this->actingAs($tenant)->get(route('tenant.ask-dora'));

    $response->assertSee('Ask DORA')
        ->assertSee('Understand your bill and common charges.')
        ->assertSee('What is my current room assignment?')
        ->assertSee('Explain my latest electricity bill')
        ->assertSee('Check my maintenance or absence request status')
        ->assertSee('Check or submit an absence request')
        ->assertSee('aria-label="DORA conversation history"', false)
        ->assertSee('data-chat-conversation-list', false)
        ->assertDontSee('Summarize current room occupancy')
        ->assertSee('AI unavailable');
});

test('guests can open the floating DORA assistant without access to tenant records', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Ask DORA')
        ->assertSee('AI unavailable');

    $this->getJson(route('chat.conversations.index'))
        ->assertOk()
        ->assertJsonPath('data', []);
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
