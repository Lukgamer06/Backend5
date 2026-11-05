<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::get('/reservas', [ReservationController::class, 'index'])->name('reservas.index');
        Route::post('/reservas', [ReservationController::class, 'store'])->name('reservas.store');
        Route::get('/reservas/{reserva}', [ReservationController::class, 'show'])->name('reservas.show');
        Route::patch('/reservas/{reserva}', [ReservationController::class, 'update'])->name('reservas.update');
        Route::post('/reservas/{reserva}/cancelacion', [ReservationController::class, 'cancel'])->name('reservas.cancel');
    });
});
