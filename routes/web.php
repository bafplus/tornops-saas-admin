<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FactionController;
use App\Http\Controllers\AuthController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/order', [HomeController::class, 'order'])->name('order');

// Auth
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Master key login (no auth needed)
Route::match(['GET', 'POST'], '/master-login', [AuthController::class, 'masterLogin'])->name('master-login');

// Admin routes (require auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin', function () {
    return redirect()->route('admin.factions');
})->name('admin');
    
    // Factions
    Route::get('/admin/factions', [FactionController::class, 'index'])->name('admin.factions');
    Route::get('/admin/factions/create', [FactionController::class, 'create'])->name('admin.factions.create');
    Route::post('/admin/factions/create', [FactionController::class, 'store'])->name('admin.factions.store');
    Route::get('/admin/factions/{faction}', [FactionController::class, 'show'])->name('admin.factions.show');
    Route::delete('/admin/factions/{faction}', [FactionController::class, 'destroy'])->name('admin.factions.destroy');
    
    // Faction actions
    Route::post('/admin/factions/{faction}/start', [FactionController::class, 'start'])->name('admin.factions.start');
    Route::post('/admin/factions/{faction}/stop', [FactionController::class, 'stop'])->name('admin.factions.stop');
    Route::post('/admin/factions/{faction}/regenerate-key', [FactionController::class, 'regenerateKey'])->name('admin.factions.regenerate-key');
    Route::post('/admin/factions/{faction}/login', [FactionController::class, 'loginAs'])->name('admin.factions.login');
    Route::post('/admin/factions/{faction}/toggle-trial', [FactionController::class, 'toggleTrial'])->name('admin.factions.toggle-trial');
});