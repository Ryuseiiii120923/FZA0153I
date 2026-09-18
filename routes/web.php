<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('/', 'auth::login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::view('/glPage', 'index')->name('gl.dashboard');
});

Route::post('/logout/worker', [AuthController::class, 'logout'])->name('logout');
