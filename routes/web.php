<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Livewire\Update;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
})->name('login');


Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/home', function () {
    return view('home');
})->middleware('auth')->name('home.index');

Route::get('/add', function () {
    return view('crud.add');
})->middleware('auth')->name('add.post');

Route::middleware('auth')->controller(HomeController::class)->group(function () {
    Route::get('/home', 'index')->name('home.index');
    Route::get('/add', 'add')->name('post.add');
    // Route::get('/update/{ppf}', 'update')->name('post.update');
});
