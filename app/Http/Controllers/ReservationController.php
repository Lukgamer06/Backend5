<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoReservaEnum;
use App\Http\Requests\Reservations\IndexReservationRequest;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Http\Requests\Reservations\UpdateReservationRequest;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ReservationController
{
    public function index(
        IndexReservationRequest $request,
        ReservationService $reservationService,
    ): View {
        $usuario = $this->authenticatedUser($request);

        return view('reservations.index', [
            'reservations' => $reservationService->paginate($usuario, $request->validated()),
            'filters' => $request->validated(),
            'breadcrumbs' => [
                ['label' => 'Reservas'],
            ],
        ]);
    }

    public function create(
        Request $request,
        ReservationService $reservationService,
    ): View {
        Gate::authorize('create', Reserva::class);
        $usuario = $this->authenticatedUser($request);

        return view('reservations.create', [
            ...$reservationService->formOptions($usuario),
            'breadcrumbs' => [
                ['label' => 'Reservas', 'url' => route('reservations.index')],
                ['label' => 'Nueva reserva'],
            ],
        ]);
    }

    public function store(
        StoreReservationRequest $request,
        ReservationService $reservationService,
    ): RedirectResponse {
        $usuario = $this->authenticatedUser($request);
        $data = $request->validated();

        $reserva = $reservationService->create(
            $usuario,
            isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
            (int) $data['mesa_id'],
            Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_inicio']),
            Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_fin']),
            (int) $data['numero_personas'],
            $data['observaciones'] ?? null,
        );

        return redirect()
            ->route('reservations.show', $reserva)
            ->with('status', 'La reserva fue creada correctamente.');
    }

    public function show(Reserva $reserva): View
    {
        Gate::authorize('view', $reserva);

        return view('reservations.show', [
            'reservation' => $reserva->loadMissing(['cliente', 'mesa', 'usuario']),
            'breadcrumbs' => [
                ['label' => 'Reservas', 'url' => route('reservations.index')],
                ['label' => 'Detalle de reserva'],
            ],
        ]);
    }

    public function edit(
        Reserva $reserva,
        Request $request,
        ReservationService $reservationService,
    ): View {
        Gate::authorize('update', $reserva);

        return view('reservations.edit', [
            'reservation' => $reserva->loadMissing(['cliente', 'mesa', 'usuario']),
            'statuses' => EstadoReservaEnum::cases(),
            ...$reservationService->formOptions($this->authenticatedUser($request)),
            'breadcrumbs' => [
                ['label' => 'Reservas', 'url' => route('reservations.index')],
                ['label' => 'Editar reserva'],
            ],
        ]);
    }

    public function update(
        UpdateReservationRequest $request,
        Reserva $reserva,
        ReservationService $reservationService,
    ): RedirectResponse {
        $data = $request->validated();

        $reservationService->update(
            $reserva,
            $this->authenticatedUser($request),
            (int) $data['cliente_id'],
            (int) $data['mesa_id'],
            Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_inicio']),
            Carbon::createFromFormat('Y-m-d\TH:i', $data['fecha_fin']),
            (int) $data['numero_personas'],
            EstadoReservaEnum::from($data['estado']),
            $data['observaciones'] ?? null,
        );

        return redirect()
            ->route('reservations.show', $reserva)
            ->with('status', 'La reserva fue actualizada correctamente.');
    }

    public function cancel(
        Reserva $reserva,
        ReservationService $reservationService,
    ): RedirectResponse {
        Gate::authorize('cancel', $reserva);
        $reservationService->cancel($reserva);

        return redirect()
            ->route('reservations.show', $reserva)
            ->with('status', 'La reserva fue cancelada.');
    }

    public function destroy(
        Reserva $reserva,
        ReservationService $reservationService,
    ): RedirectResponse {
        Gate::authorize('delete', $reserva);
        $reservationService->delete($reserva);

        return redirect()
            ->route('reservations.index')
            ->with('status', 'La reserva fue eliminada correctamente.');
    }

    private function authenticatedUser(Request $request): Usuario
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new UnauthorizedHttpException('web');
        }

        return $usuario;
    }
}
