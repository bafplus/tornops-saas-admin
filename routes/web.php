<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FactionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/master-login', [AuthController::class, 'login']);

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/factions', [FactionController::class, 'index'])->name('admin.factions');
    Route::get('/admin/factions/create', [FactionController::class, 'create']);
    Route::post('/admin/factions', [FactionController::class, 'store']);
    Route::post('/admin/factions/{faction}/start', [FactionController::class, 'start']);
    Route::post('/admin/factions/{faction}/stop', [FactionController::class, 'stop']);
    Route::delete('/admin/factions/{faction}', [FactionController::class, 'destroy']);
    Route::post('/admin/factions/{faction}/login', [FactionController::class, 'loginAs']);
    Route::post('/admin/factions/{faction}/regenerate-key', [FactionController::class, 'regenerateKey']);
    Route::get('/admin/factions/{faction}/edit', [FactionController::class, 'edit'])->name('admin.factions.edit');
    Route::put('/admin/factions/{faction}', [FactionController::class, 'update'])->name('admin.factions.update');
    Route::post('/admin/logout', [AuthController::class, 'logout']);
});
