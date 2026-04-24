<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FactionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/order', [HomeController::class, 'order'])->name('order');

// Payment webhook (called by cron or Torn API if supported)
Route::post('/webhook/payment', [PaymentController::class, 'checkPayments'])->name('payment.webhook');

// Admin routes (require authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    
    Route::get('/admin/factions', [FactionController::class, 'index'])->name('admin.factions');
    Route::post('/admin/factions', [FactionController::class, 'store'])->name('admin.factions.store');
    Route::get('/admin/factions/{faction}', [FactionController::class, 'show'])->name('admin.factions.show');
    Route::delete('/admin/factions/{faction}', [FactionController::class, 'destroy'])->name('admin.factions.destroy');
    
    // Faction actions
    Route::post('/admin/factions/{faction}/start', [FactionController::class, 'start'])->name('admin.factions.start');
    Route::post('/admin/factions/{faction}/stop', [FactionController::class, 'stop'])->name('admin.factions.stop');
    Route::post('/admin/factions/{faction}/regenerate-key', [FactionController::class, 'regenerateKey'])->name('admin.factions.regenerate-key');
    Route::post('/admin/factions/{faction}/login', [FactionController::class, 'loginAs'])->name('admin.factions.login');
    Route::post('/admin/factions/{faction}/toggle-trial', [FactionController::class, 'toggleTrial'])->name('admin.factions.toggle-trial');
});