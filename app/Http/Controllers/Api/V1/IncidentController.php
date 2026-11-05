<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Http\Requests\Api\V1\Incidents\IndexIncidentApiRequest;
use App\Http\Requests\Api\V1\Incidents\StoreIncidentApiRequest;
use App\Http\Requests\Api\V1\Incidents\UpdateIncidentApiRequest;
use App\Http\Resources\Api\V1\IncidenciaResource;
use App\Http\Resources\Api\V1\IncidentCollection;
use App\Models\Incidencia;
use App\Models\Usuario;
use App\Services\IncidentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentController
{
    public function __construct(
        private readonly IncidentService $incidentService,
    ) {}

    public function index(IndexIncidentApiRequest $request): IncidentCollection
    {
        Gate::authorize('viewAny', Incidencia::class);

        return IncidentCollection::make(
            $this->incidentService->paginate($request->validated()),
        );
    }

    public function store(StoreIncidentApiRequest $request): JsonResponse
    {
        Gate::authorize('create', Incidencia::class);
        $data = $request->validated();

        $incidencia = $this->incidentService->report(
            $this->authenticatedUser($request),
            $data['titulo'],
            $data['descripcion'],
            PrioridadIncidenciaEnum::from(
                $data['prioridad'] ?? PrioridadIncidenciaEnum::MEDIA->value,
            ),
            isset($data['mesa_id']) ? (int) $data['mesa_id'] : null,
            isset($data['pedido_id']) ? (int) $data['pedido_id'] : null,
        );

        return IncidenciaResource::make($incidencia)
            ->withMessage('Incidencia reportada correctamente.')
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateIncidentApiRequest $request,
        Incidencia $incidencia,
    ): IncidenciaResource {
        Gate::authorize('update', $incidencia);
        $data = $request->validated();

        $incidenciaActualizada = $this->incidentService->update(
            $incidencia,
            isset($data['estado'])
                ? EstadoIncidenciaEnum::from($data['estado'])
                : $incidencia->estado,
            isset($data['prioridad'])
                ? PrioridadIncidenciaEnum::from($data['prioridad'])
                : $incidencia->prioridad,
        );

        return IncidenciaResource::make($incidenciaActualizada)
            ->withMessage('Incidencia actualizada correctamente.');
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
