<?php

declare(strict_types=1);

use App\Http\Middleware\RoleMiddleware;
use App\Services\OrderStateConflictException;
use App\Services\PaymentIdempotencyConflictException;
use App\Services\ReservationConflictException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PaymentIdempotencyConflictException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'La clave de idempotencia ya fue utilizada para otro pedido o canal.',
                'meta' => (object) [],
            ], 409);
        });

        $exceptions->render(function (OrderStateConflictException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'El estado del pedido no permite la transición solicitada.',
                'meta' => [
                    'errors' => $exception->errors(),
                ],
            ], 409);
        });

        $exceptions->render(function (ReservationConflictException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'La reserva entra en conflicto con la disponibilidad de la mesa.',
                'meta' => [
                    'errors' => $exception->errors(),
                ],
            ], 409);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'No autenticado.',
                'meta' => (object) [],
            ], 401);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'Los datos enviados no son válidos.',
                'meta' => [
                    'errors' => $exception->errors(),
                ],
            ], 422);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'data' => null,
                'message' => 'Demasiadas solicitudes. Inténtalo nuevamente más tarde.',
                'meta' => [
                    'retry_after' => $exception->getHeaders()['Retry-After'] ?? null,
                ],
            ], 429, $exception->getHeaders());
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;
            $message = match ($status) {
                403 => 'Acceso denegado.',
                404 => 'Recurso no encontrado.',
                405 => 'Método HTTP no permitido.',
                409 => 'Conflicto al procesar la solicitud.',
                default => $status >= 500
                    ? 'Error interno del servidor.'
                    : 'No fue posible procesar la solicitud.',
            };
            $headers = $exception instanceof HttpExceptionInterface
                ? $exception->getHeaders()
                : [];

            return response()->json([
                'data' => null,
                'message' => $message,
                'meta' => (object) [],
            ], $status, $headers);
        });
    })->create();
