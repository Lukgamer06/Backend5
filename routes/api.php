<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\MenuDiaController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::get('/incidencias', [IncidentController::class, 'index'])->name('incidencias.index');
        Route::post('/incidencias', [IncidentController::class, 'store'])->name('incidencias.store');
        Route::patch('/incidencias/{incidencia}', [IncidentController::class, 'update'])->name('incidencias.update');

        Route::get('/reservas', [ReservationController::class, 'index'])->name('reservas.index');
        Route::get('/reservas/disponibilidad', [ReservationController::class, 'availableTables'])
            ->name('reservas.disponibilidad');
        Route::post('/reservas', [ReservationController::class, 'store'])->name('reservas.store');
        Route::get('/reservas/{reserva}', [ReservationController::class, 'show'])->name('reservas.show');
        Route::patch('/reservas/{reserva}', [ReservationController::class, 'update'])->name('reservas.update');
        Route::post('/reservas/{reserva}/cancelacion', [ReservationController::class, 'cancel'])->name('reservas.cancel');

        Route::get('/pedidos', [OrderController::class, 'index'])->name('pedidos.index');
        Route::post('/pedidos', [OrderController::class, 'store'])->name('pedidos.store');
        Route::get('/pedidos/{pedido}', [OrderController::class, 'show'])->name('pedidos.show');
        Route::patch('/pedidos/{pedido}', [OrderController::class, 'update'])->name('pedidos.update');
        Route::post('/pedidos/{pedido}/cancelacion', [OrderController::class, 'cancel'])->name('pedidos.cancel');
        Route::patch('/pedidos/{pedido}/estado', [OrderController::class, 'changeStatus'])->name('pedidos.estado');
        Route::get('/pedidos/{pedido}/pagos', [PaymentController::class, 'indexForOrder'])->name('pedidos.pagos.index');
        Route::post('/pedidos/{pedido}/pagos/enlinea', [PaymentController::class, 'createOnline'])->name('pedidos.pagos.online');
        Route::post('/pedidos/{pedido}/pagos/presenciales', [PaymentController::class, 'createInPerson'])->name('pedidos.pagos.in-person');

        Route::get('/productos', [ProductController::class, 'index'])->name('productos.index');
        Route::get('/productos/{producto}', [ProductController::class, 'show'])->name('productos.show');

        Route::get('/menus-dia', [MenuDiaController::class, 'index'])->name('menus-dia.index');
        Route::get('/menus-dia/actual', [MenuDiaController::class, 'actual'])->name('menus-dia.actual');
        Route::post('/menus-dia', [MenuDiaController::class, 'store'])->name('menus-dia.store');
        Route::patch('/menus-dia/{menuDia}', [MenuDiaController::class, 'update'])->name('menus-dia.update');
        Route::post('/menus-dia/{menuDia}/publicacion', [MenuDiaController::class, 'publish'])->name('menus-dia.publish');

        Route::get('/pagos/{pago}', [PaymentController::class, 'show'])->name('pagos.show');
    });
});
