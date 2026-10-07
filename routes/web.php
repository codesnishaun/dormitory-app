<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminControllers\DashboardController as AdminDashboardController;
use App\Http\Controllers\AdminControllers\TenantController;
use App\Http\Controllers\AdminControllers\RoomController;
use App\Http\Controllers\AdminControllers\BillingController;
use App\Http\Controllers\AdminControllers\AnnouncementController;
use App\Http\Controllers\AdminControllers\MaintenanceController;
use App\Http\Controllers\TenantControllers\DashboardController as TenantDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/application_form', [ApplicationController::class, 'index'])->name('application_form');
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');

Route::get('/tenant', [TenantDashboardController::class, 'index'])->name('tenant.dashboard');
Route::get('/tenant/room-bill',function () {return view('tenant.room-bill');})->name('tenant.room-bill');
Route::get('/tenant/announcements',function () {return view('tenant.announcements');})->name('tenant.announcements');
Route::get('/tenant/maintenance', function() {return view('tenant.maintenance');})->name('tenant.maintenance');
Route::get('/tenant/ask-dora', function() {return view('tenant.ask-dora');})->name('tenant.ask-dora');

Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/tenants', [TenantController::class, 'index'])->name('admin.tenant.index');
Route::get('/admin/rooms', [RoomController::class, 'index'])->name('admin.room.index');
Route::get('/admin/billing', [BillingController::class, 'index'])->name('admin.billing.index');
Route::get('/admin/announcements', [AnnouncementController::class, 'index'])->name('admin.announcement.index');
Route::get('/admin/maintenance', [MaintenanceController::class, 'index'])->name('admin.maintenance.index');


