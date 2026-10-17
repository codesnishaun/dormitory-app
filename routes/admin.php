<?php

use App\Http\Controllers\AdminControllers\AnnouncementController;
use App\Http\Controllers\AdminControllers\ApplicationReviewController;
use App\Http\Controllers\AdminControllers\BillingController;
use App\Http\Controllers\AdminControllers\DashboardController as AdminDashboardController;
use App\Http\Controllers\AdminControllers\MaintenanceController;
use App\Http\Controllers\AdminControllers\RoomAssignmentController;
use App\Http\Controllers\AdminControllers\RoomController;
use App\Http\Controllers\AdminControllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function (): void {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/tenants', [TenantController::class, 'index'])->name('admin.tenant.index');
    Route::put('/admin/tenants/{tenant}', [TenantController::class, 'update'])->name('admin.tenant.update');
    Route::delete('/admin/tenants/{tenant}', [TenantController::class, 'destroy'])->name('admin.tenant.destroy');
    Route::get('/admin/rooms', [RoomController::class, 'index'])->name('admin.room.index');
    Route::post('/admin/rooms', [RoomController::class, 'store'])->name('admin.room.store');
    Route::put('/admin/rooms/{room}', [RoomController::class, 'update'])->name('admin.room.update');
    Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy'])->name('admin.room.destroy');
    Route::get('/admin/billing', [BillingController::class, 'index'])->name('admin.billing.index');
    Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcement.index');
    Route::get('/admin/maintenance', [MaintenanceController::class, 'index'])->name('admin.maintenance.index');
    Route::get('/admin/ask-dora', function () {
        return view('admin.ask-dora');
    })->name('admin.ask-dora');
    Route::post('/admin/applications/{application}/approve', [ApplicationReviewController::class, 'approve'])->name('applications.approve');
    Route::post('/admin/applications/{application}/reject', [ApplicationReviewController::class, 'reject'])->name('applications.reject');
    Route::post('/applications/{application}/assign-room', [RoomAssignmentController::class, 'store'])->name('applications.assign-room');
});
