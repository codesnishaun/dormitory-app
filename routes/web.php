<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/application_form', [ApplicationController::class, 'index'])->name('application_form');
Route::get('/login', [AuthController::class, 'index'])->name('login');
