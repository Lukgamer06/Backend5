<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use App\Models\Incidencia;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Repositories\IncidentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IncidentService
{
    public function __construct(
        private readonly IncidentRepository $incidentRepository,
    ) {}

    /**
     * @param  array{search?: string, prioridad?: string, estado?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Incidencia>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->incidentRepository->paginate($filters);
    }

    public function find(int $incidenciaId): ?Incidencia
    {
        return $this->incidentRepository->find($incidenciaId);
    }

    /**
     * @return array{mesas: Collection<int, Mesa>, pedidos: Collection<int, Pedido>}
     */
    public function formOptions(): array
    {
        return [
            'mesas' => $this->incidentRepository->mesas(),
            'pedidos' => $this->incidentRepository->pedidos(),
        ];
    }

    public function report(
        Usuario $usuario,
        string $titulo,
        string $descripcion,
        PrioridadIncidenciaEnum $prioridad,
        ?int $mesaId,
        ?int $pedidoId,
    ): Incidencia {
        $incidencia = new Incidencia([
            'usuario_reporta_id' => $usuario->getKey(),
            'mesa_id' => $mesaId,
            'pedido_id' => $pedidoId,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'prioridad' => $prioridad,
            'estado' => EstadoIncidenciaEnum::ABIERTA,
        ]);

        return $this->incidentRepository->save($incidencia);
    }

    public function update(
        Incidencia $incidencia,
        EstadoIncidenciaEnum $estado,
        PrioridadIncidenciaEnum $prioridad,
    ): Incidencia {
        $incidencia->fill([
            'estado' => $estado,
            'prioridad' => $prioridad,
        ]);

        return $this->incidentRepository->save($incidencia);
    }
}
