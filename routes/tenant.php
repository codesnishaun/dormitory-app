<?php

use App\Http\Controllers\TenantAccountSetupController;
use App\Http\Controllers\TenantControllers\DashboardController as TenantDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function (): void {
    Route::get('/tenant/setup/{token}', [TenantAccountSetupController::class, 'show'])->name('tenant.setup.show');
    Route::post('/tenant/setup/{token}', [TenantAccountSetupController::class, 'store'])->name('tenant.setup.store');
});

Route::middleware(['auth', 'role:tenant'])->group(function (): void {
    Route::get('/tenant', [TenantDashboardController::class, 'index'])->name('tenant.dashboard');
    Route::get('/tenant/room-bill', function () {
        return view('tenant.room-bill');
    })->name('tenant.room-bill');
    Route::get('/tenant/announcements', function () {
        return view('tenant.announcements');
    })->name('tenant.announcements');
    Route::get('/tenant/maintenance', function () {
        return view('tenant.maintenance');
    })->name('tenant.maintenance');
    Route::get('/tenant/ask-dora', function () {
        return view('tenant.ask-dora');
    })->name('tenant.ask-dora');
});
