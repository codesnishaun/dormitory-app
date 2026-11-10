<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins can still publish announcements from the announcements module', function () {
    $admin = User::factory()->admin()->create(['name' => 'Announcement Admin']);

    $this->actingAs($admin)->post(route('admin.announcement.store'), [
        'title' => 'Water interruption',
        'category' => 'Utilities',
        'body' => 'Water service will be interrupted this afternoon.',
        'publish_mode' => 'now',
    ])->assertRedirectToRoute('admin.announcement.index')
        ->assertSessionHas('success', 'Announcement posted!');

    $this->assertDatabaseHas('announcements', [
        'title' => 'Water interruption',
        'category' => 'Utilities',
        'body' => 'Water service will be interrupted this afternoon.',
        'author_name' => 'Announcement Admin',
        'is_pinned' => false,
    ]);
});

test('tenants cannot publish announcements from the admin module', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)->post(route('admin.announcement.store'), [
        'title' => 'Unauthorized announcement',
        'category' => 'General update',
        'body' => 'This should not be published.',
        'publish_mode' => 'now',
    ])->assertForbidden();

    $this->assertDatabaseCount('announcements', 0);
});
