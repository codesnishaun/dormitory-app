<?php

use App\Http\Controllers\TenantAccountSetupController;
use App\Http\Controllers\TenantControllers\AnnouncementController as TenantAnnouncementController;
use App\Http\Controllers\TenantControllers\DashboardController as TenantDashboardController;
use App\Http\Controllers\TenantControllers\MaintenanceController as TenantMaintenanceController;
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
    Route::get('/tenant/announcements', [TenantAnnouncementController::class, 'index'])->name('tenant.announcements');
    Route::get('/tenant/maintenance', [TenantMaintenanceController::class, 'index'])->name('tenant.maintenance');
    Route::post('/tenant/maintenance', [TenantMaintenanceController::class, 'store'])->name('tenant.maintenance.store');
    Route::get('/tenant/ask-dora', function () {
        return view('tenant.ask-dora');
    })->name('tenant.ask-dora');
});