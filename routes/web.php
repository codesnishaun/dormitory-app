<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminControllers\DashboardController as AdminDashboardController;
use App\Http\Controllers\AdminControllers\TenantController;
use App\Http\Controllers\TenantControllers\DashboardController as TenantDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/application_form', [ApplicationController::class, 'index'])->name('application_form');
Route::get('/login', [AuthController::class, 'index'])->name('login');

Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/tenants', [TenantController::class, 'index'])->name('admin.tenant.index');

Route::get('/tenant', [TenantDashboardController::class, 'index'])->name('tenant.dashboard');

