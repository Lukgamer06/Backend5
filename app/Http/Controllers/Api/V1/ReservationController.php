<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Reservations\IndexReservationApiRequest;
use App\Http\Requests\Api\V1\Reservations\StoreReservationApiRequest;
use App\Http\Requests\Api\V1\Reservations\UpdateReservationApiRequest;
use App\Http\Resources\Api\V1\ReservaResource;
use App\Http\Resources\Api\V1\ReservationCollection;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\ReservationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ReservationController
{
    public function __construct(
        private readonly ReservationService $reservationService,
    ) {}

    public function index(IndexReservationApiRequest $request): ReservationCollection
    {
        $usuario = $this->authenticatedUser($request);
        Gate::authorize('viewAny', Reserva::class);

        $reservas = $this->reservationService->paginate(
            $usuario,
            $request->validated(),
        );

        return ReservationCollection::make($reservas);
    }

    public function show(Reserva $reserva): ReservaResource
    {
        Gate::authorize('view', $reserva);

        return ReservaResource::make($this->reservationService->findOrFail($reserva->getKey()));
    }

    public function store(StoreReservationApiRequest $request): JsonResponse
    {
        $usuario = $this->authenticatedUser($request);
        Gate::authorize('create', Reserva::class);
        $data = $request->validated();

        $reserva = $this->reservationService->create(
            $usuario,
            $data['cliente_id'] ?? null,
            $data['mesa_id'],
            Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_inicio']),
            Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_fin']),
            $data['numero_personas'],
            $data['observaciones'] ?? null,
        );

        return ReservaResource::make($reserva)
            ->withMessage('Reserva creada correctamente.')
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateReservationApiRequest $request,
        Reserva $reserva,
    ): ReservaResource {
        Gate::authorize('update', $reserva);
        $data = $request->validated();

        $reservaActualizada = $this->reservationService->update(
            $reserva,
            $this->authenticatedUser($request),
            $data['cliente_id'] ?? $reserva->cliente_id,
            $data['mesa_id'] ?? $reserva->mesa_id,
            array_key_exists('fecha_inicio', $data)
                ? Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_inicio'])
                : $reserva->fecha_inicio,
            array_key_exists('fecha_fin', $data)
                ? Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_fin'])
                : $reserva->fecha_fin,
            $data['numero_personas'] ?? $reserva->numero_personas,
            $reserva->estado,
            array_key_exists('observaciones', $data)
                ? $data['observaciones']
                : $reserva->observaciones,
        );

        return ReservaResource::make($reservaActualizada)
            ->withMessage('Reserva actualizada correctamente.');
    }

    public function cancel(Request $request, Reserva $reserva): ReservaResource
    {
        Gate::authorize('cancel', $reserva);

        return ReservaResource::make($this->reservationService->cancel($reserva))
            ->withMessage('Reserva cancelada correctamente.');
    }

    private function authenticatedUser(Request $request): Usuario
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new AuthorizationException('Se requiere una sesión autenticada.');
        }

        return $usuario;
    }
}
