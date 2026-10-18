<?php

use App\Http\Controllers\AdminControllers\AbsenceRequestController;
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
    Route::post('/admin/tenants', [TenantController::class, 'store'])->name('admin.tenant.store');
    Route::put('/admin/tenants/{tenant}', [TenantController::class, 'update'])->name('admin.tenant.update');
    Route::delete('/admin/tenants/{tenant}', [TenantController::class, 'destroy'])->name('admin.tenant.destroy');
    Route::get('/admin/rooms', [RoomController::class, 'index'])->name('admin.room.index');
    Route::post('/admin/rooms', [RoomController::class, 'store'])->name('admin.room.store');
    Route::put('/admin/rooms/{room}', [RoomController::class, 'update'])->name('admin.room.update');
    Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy'])->name('admin.room.destroy');
    Route::get('/admin/billing', [BillingController::class, 'index'])->name('admin.billing.index');
    Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcement.index');
    Route::post('/admin/announcements', [AnnouncementController::class, 'store'])->name('admin.announcement.store');
    Route::put('/admin/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('admin.announcement.update');
    Route::patch('/admin/announcements/{announcement}/pin', [AnnouncementController::class, 'togglePin'])->name('admin.announcement.pin');
    Route::delete('/admin/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcement.destroy');
    Route::get('/admin/maintenance', [MaintenanceController::class, 'index'])->name('admin.maintenance.index');
    Route::get('/admin/absence-requests', [AbsenceRequestController::class, 'index'])->name('admin.absence-request.index');
    Route::patch('/admin/absence-requests/{absenceRequest}/approve', [AbsenceRequestController::class, 'approve'])->name('admin.absence-request.approve');
    Route::patch('/admin/absence-requests/{absenceRequest}/reject', [AbsenceRequestController::class, 'reject'])->name('admin.absence-request.reject');
    Route::get('/admin/ask-dora', function () {
        return view('admin.ask-dora');
    })->name('admin.ask-dora');
    Route::post('/admin/applications/{application}/approve', [ApplicationReviewController::class, 'approve'])->name('applications.approve');
    Route::post('/admin/applications/{application}/reject', [ApplicationReviewController::class, 'reject'])->name('applications.reject');
    Route::delete('/admin/applications/{application}', [ApplicationReviewController::class, 'destroy'])->name('applications.destroy');
    Route::post('/applications/{application}/assign-room', [RoomAssignmentController::class, 'store'])->name('applications.assign-room');
});