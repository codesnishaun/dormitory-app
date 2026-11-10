<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:dora-chat')->group(function (): void {
    Route::get('/chat/conversations', [ChatController::class, 'index'])->name('chat.conversations.index');
    Route::post('/chat/conversations', [ChatController::class, 'store'])->name('chat.conversations.store');
    Route::get('/chat/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.conversations.show');
    Route::delete('/chat/conversations/{conversation}', [ChatController::class, 'destroy'])->name('chat.conversations.destroy');
    Route::post('/chat/conversations/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::post('/chat/conversations/{conversation}/messages/{message}/retry', [ChatController::class, 'retryMessage'])->name('chat.messages.retry');
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/application_form', [ApplicationController::class, 'index'])->name('application_form');
Route::post('/application_form', [ApplicationController::class, 'store'])->name('application_form.store');
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
