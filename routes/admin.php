
<?php

use App\Http\Controllers\AdminControllers\AbsenceRequestController;
use App\Http\Controllers\AdminControllers\AnnouncementController;
use App\Http\Controllers\AdminControllers\ApplicationReviewController;
use App\Http\Controllers\AdminControllers\BillingController;
use App\Http\Controllers\AdminControllers\DashboardController as AdminDashboardController;
use App\Http\Controllers\AdminControllers\ElectricityBillController;
use App\Http\Controllers\AdminControllers\MaintenanceController;
use App\Http\Controllers\AdminControllers\MeterAssignmentController;
use App\Http\Controllers\AdminControllers\MeterReadingController;
use App\Http\Controllers\AdminControllers\RoomAssignmentController;
use App\Http\Controllers\AdminControllers\RoomController;
use App\Http\Controllers\AdminControllers\SubmeterController;
use App\Http\Controllers\AdminControllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function (): void {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/tenants', [TenantController::class, 'index'])->name('admin.tenant.index');
    Route::post('/admin/tenants', [TenantController::class, 'store'])->name('admin.tenant.store');
    Route::put('/admin/tenants/{tenant}', [TenantController::class, 'update'])->name('admin.tenant.update');
    Route::delete('/admin/tenants/{tenant}', [TenantController::class, 'destroy'])->name('admin.tenant.destroy');
    Route::patch('/admin/tenants/{tenant}/restore', [TenantController::class, 'restore'])->name('admin.tenant.restore');

    Route::get('/admin/rooms', [RoomController::class, 'index'])->name('admin.room.index');
    Route::post('/admin/rooms', [RoomController::class, 'store'])->name('admin.room.store');
    Route::put('/admin/rooms/{room}', [RoomController::class, 'update'])->name('admin.room.update');
    Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy'])->name('admin.room.destroy');

    Route::get('/admin/submeters', [SubmeterController::class, 'index'])->name('admin.submeter.index');
    Route::post('/admin/submeters', [SubmeterController::class, 'store'])->name('admin.submeter.store');
    Route::put('/admin/submeters/{submeter}', [SubmeterController::class, 'update'])->name('admin.submeter.update');
    Route::patch('/admin/submeters/{submeter}/retire', [SubmeterController::class, 'retire'])->name('admin.submeter.retire');

    Route::get('/admin/meter-assignments', [MeterAssignmentController::class, 'index'])->name('admin.meter-assignments.index');
    Route::post('/admin/meter-assignments', [MeterAssignmentController::class, 'store'])->name('admin.meter-assignments.store');
    Route::patch('/admin/meter-assignments/{meterAssignment}/close', [MeterAssignmentController::class, 'close'])->name('admin.meter-assignments.close');
    Route::post('/admin/meter-assignments/{meterAssignment}/readings', [MeterReadingController::class, 'store'])->name('admin.meter-readings.store');

    Route::get('/admin/electricity-bills', [ElectricityBillController::class, 'index'])->name('admin.electricity-bills.index');
    Route::post('/admin/electricity-bills/preview', [ElectricityBillController::class, 'preview'])->name('admin.electricity-bills.preview');
    Route::post('/admin/electricity-bills/finalize', [ElectricityBillController::class, 'finalize'])->name('admin.electricity-bills.finalize');
    Route::get('/admin/electricity-bills/{electricityBill}', [ElectricityBillController::class, 'show'])->name('admin.electricity-bills.show');

    Route::get('/admin/billing', [BillingController::class, 'index'])->name('admin.billing.index');
    Route::post('/admin/billing/tenants/{tenant}/payments', [BillingController::class, 'storePayment'])->name('admin.billing.payments.store');
    Route::post('/admin/billing/tenants/{tenant}/charges', [BillingController::class, 'storeCharge'])->name('admin.billing.charges.store');

    Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcement.index');
    Route::post('/admin/announcements', [AnnouncementController::class, 'store'])->name('admin.announcement.store');
    Route::put('/admin/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('admin.announcement.update');
    Route::patch('/admin/announcements/{announcement}/pin', [AnnouncementController::class, 'togglePin'])->name('admin.announcement.pin');
    Route::delete('/admin/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcement.destroy');

    Route::get('/admin/maintenance', [MaintenanceController::class, 'index'])->name('admin.maintenance.index');
    Route::patch('/admin/maintenance/{maintenanceRequest}', [MaintenanceController::class, 'update'])->name('admin.maintenance.update');

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
