<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transaction history retains its soft-deleted recorder', function () {
    $recorder = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'tenant']);
    $transaction = Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'payment',
        'description' => 'Monthly rent',
        'amount' => 4200,
        'transaction_date' => '2026-10-01',
        'recorded_by' => $recorder->id,
    ]);
    $recorder->delete();

    expect($transaction->fresh()->recorder->is($recorder))->toBeTrue();
});
