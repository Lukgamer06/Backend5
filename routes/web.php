<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MenuDiaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
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
    Route::patch('/pedidos/{pedido}/cancelar', [OrderController::class, 'cancel'])
        ->name('orders.cancel');
    Route::patch('/pedidos/{pedido}/estado', [OrderController::class, 'updateStatus'])
        ->name('orders.status');
    Route::resource('/pedidos', OrderController::class)
        ->parameters(['pedidos' => 'pedido'])
        ->names('orders');
    Route::patch('/productos/{producto}/activar', [ProductController::class, 'activate'])
        ->name('products.activate');
    Route::patch('/productos/{producto}/desactivar', [ProductController::class, 'deactivate'])
        ->name('products.deactivate');
    Route::resource('/productos', ProductController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->parameters(['productos' => 'producto'])
        ->names('products');
    Route::patch('/menus/{menuDia}/publicar', [MenuDiaController::class, 'publish'])
        ->name('menus.publish');
    Route::resource('/menus', MenuDiaController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->parameters(['menus' => 'menuDia'])
        ->names('menus');
    Route::resource('/incidencias', IncidentController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->parameters(['incidencias' => 'incidencia'])
        ->names('incidents');
    Route::resource('/pagos', PaymentController::class)
        ->only(['index', 'show'])
        ->parameters(['pagos' => 'pago'])
        ->names('payments');
    Route::post('/logout', LogoutController::class)->name('logout');
});
