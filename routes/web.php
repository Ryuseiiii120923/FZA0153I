<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {

    Route::get('/home', [HomeController::class, 'index'])->name('home.index');

    Route::get('/prencode', [HomeController::class, 'prencode'])->name('prencode');

    Route::get('/selector', [HomeController::class, 'selector'])->name('selector');

    Route::get('/main', [HomeController::class, 'Main'])->name('main');

    Route::get('/dashboard', [HomeController::class, 'operatordash'])->name('operator.dashboard');

    Route::get('/gldash', [HomeController::class, 'gldash'])->name('gl.dashboard');
    Route::get('/ppfdash', [HomeController::class, 'ppfdash'])->name('gl.ppfdashboard');
    Route::get('/hfreworkdash',[HomeController::class, 'hfreworkdash'])->name('hf.dashboard');

    // Example Livewire update route if you need dynamic params
    // Route::get('/update/{ppf}', [HomeController::class, 'update'])->name('post.update');
});

Route::middleware('auth:worker')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home.index');

    Route::get('/prencode', [HomeController::class, 'prencode'])->name('prencode');

    Route::get('/dashboard', [HomeController::class, 'operatordash'])->name('operator.dashboard');
});
