<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('auth.login');
})->middleware('nocache')->name('login');
Route::get('/debug-key', function () {
    return config('app.key');
});

Route::post('/login', [AuthController::class, 'login'])->middleware('nocache')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('nocache')->name('logout');


// Authenticated routes
Route::middleware(['auth', 'nocache'])->group(function () {

    Route::get('/home', [HomeController::class, 'index'])->name('home.index');
    Route::get('/prencode', [HomeController::class, 'prencode'])->name('prencode');
    Route::get('/dashboard', [HomeController::class, 'operatordash'])->name('operator.dashboard');
    Route::get('/gldash', [HomeController::class, 'gldash'])->name('gl.dashboard');
    Route::get('/glhfdash', [HomeController::class, 'glhfdash'])->name('gl.hf.dashboard');
    Route::get('/ppfdash', [HomeController::class, 'ppfdash'])->name('gl.ppfdashboard');
    Route::get('/hfreworkdash',[HomeController::class, 'hfreworkdash'])->name('hf.dashboard');
    Route::get('/generate-pdf/{ppf}', [PdfController::class, 'generate'])->name('generate-pdf');
});

Route::middleware(['auth:worker', 'nocache'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home.index');
    Route::get('/prencode', [HomeController::class, 'prencode'])->name('prencode');
    Route::get('/dashboard', [HomeController::class, 'operatordash'])->name('operator.dashboard');
    Route::get('/sf', [HomeController::class, 'sf'])->name('operator.sf');
});
