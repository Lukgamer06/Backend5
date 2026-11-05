<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Http\Requests\Incidents\IndexIncidentRequest;
use App\Http\Requests\Incidents\StoreIncidentRequest;
use App\Http\Requests\Incidents\UpdateIncidentRequest;
use App\Models\Incidencia;
use App\Models\Usuario;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class IncidentController
{
    public function index(
        IndexIncidentRequest $request,
        IncidentService $incidentService,
    ): View {
        return view('incidents.index', [
            'incidents' => $incidentService->paginate($request->validated()),
            'filters' => $request->validated(),
            'breadcrumbs' => [
                ['label' => 'Incidencias'],
            ],
        ]);
    }

    public function create(IncidentService $incidentService): View
    {
        Gate::authorize('create', Incidencia::class);

        return view('incidents.create', [
            ...$incidentService->formOptions(),
            'priorities' => PrioridadIncidenciaEnum::cases(),
            'breadcrumbs' => [
                ['label' => 'Incidencias', 'url' => route('incidents.index')],
                ['label' => 'Reportar incidencia'],
            ],
        ]);
    }

    public function store(
        StoreIncidentRequest $request,
        IncidentService $incidentService,
    ): RedirectResponse {
        $data = $request->validated();
        $incidencia = $incidentService->report(
            $this->authenticatedUser($request),
            $data['titulo'],
            $data['descripcion'],
            PrioridadIncidenciaEnum::from($data['prioridad']),
            isset($data['mesa_id']) ? (int) $data['mesa_id'] : null,
            isset($data['pedido_id']) ? (int) $data['pedido_id'] : null,
        );

        return redirect()
            ->route('incidents.show', $incidencia)
            ->with('status', 'La incidencia fue reportada correctamente.');
    }

    public function show(Incidencia $incidencia): View
    {
        Gate::authorize('view', $incidencia);

        return view('incidents.show', [
            'incident' => $incidencia->loadMissing(['usuario', 'mesa', 'pedido']),
            'breadcrumbs' => [
                ['label' => 'Incidencias', 'url' => route('incidents.index')],
                ['label' => 'Detalle'],
            ],
        ]);
    }

    public function edit(Incidencia $incidencia): View
    {
        Gate::authorize('update', $incidencia);

        return view('incidents.edit', [
            'incident' => $incidencia->loadMissing(['usuario', 'mesa', 'pedido']),
            'statuses' => EstadoIncidenciaEnum::cases(),
            'priorities' => PrioridadIncidenciaEnum::cases(),
            'breadcrumbs' => [
                ['label' => 'Incidencias', 'url' => route('incidents.index')],
                ['label' => 'Actualizar incidencia'],
            ],
        ]);
    }

    public function update(
        UpdateIncidentRequest $request,
        Incidencia $incidencia,
        IncidentService $incidentService,
    ): RedirectResponse {
        $data = $request->validated();
        $incidencia = $incidentService->update(
            $incidencia,
            EstadoIncidenciaEnum::from($data['estado']),
            PrioridadIncidenciaEnum::from($data['prioridad']),
        );

        return redirect()
            ->route('incidents.show', $incidencia)
            ->with('status', 'La incidencia fue actualizada correctamente.');
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
