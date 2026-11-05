<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::middleware(['auth', 'role:ADMINISTRADOR,OPERADOR,CLIENTE'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('home');
    Route::patch('/reservas/{reserva}/cancelar', [ReservationController::class, 'cancel'])
        ->name('reservations.cancel');
    Route::resource('/reservas', ReservationController::class)
        ->parameters(['reservas' => 'reserva'])
        ->names('reservations');
    Route::post('/logout', LogoutController::class)->name('logout');
});
