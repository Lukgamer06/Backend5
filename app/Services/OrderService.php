<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoOperativoMesaEnum;
use App\Enums\EstadoPedidoEnum;
use App\Enums\EstadoReservaEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\RolUsuarioEnum;
use App\Enums\TipoAtencionEnum;
use App\Models\Cliente;
use App\Models\DetallePedido;
use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * @param  array{
     *     search?: string,
     *     estado?: string,
     *     tipo_atencion?: string,
     *     fecha_desde?: string,
     *     fecha_hasta?: string,
     *     per_page?: int
     * }  $filters
     * @return LengthAwarePaginator<int, Pedido>
     */
    public function paginate(Usuario $usuario, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->orderRepository->paginateFor($usuario, $filters, $perPage);
    }

    public function find(int $pedidoId): ?Pedido
    {
        return $this->orderRepository->find($pedidoId);
    }

    public function findOrFail(int $pedidoId): Pedido
    {
        return $this->find($pedidoId)
            ?? throw (new ModelNotFoundException)->setModel(Pedido::class, [$pedidoId]);
    }

    /**
     * @return array{
     *     clientes: Collection<int, Cliente>,
     *     mesas: Collection<int, Mesa>,
     *     reservas: Collection<int, Reserva>,
     *     productos: Collection<int, Producto>
     * }
     */
    public function formOptions(Usuario $usuario, ?int $clienteId = null): array
    {
        $cliente = $usuario->rol === RolUsuarioEnum::CLIENTE
            ? $this->resolveCustomer($usuario, null)
            : ($clienteId === null
                ? null
                : $this->orderRepository->findCustomer($clienteId));

        return [
            'clientes' => $usuario->rol === RolUsuarioEnum::CLIENTE
                ? new Collection
                : $this->orderRepository->customers(),
            'mesas' => $this->orderRepository->tables(),
            'reservas' => $cliente instanceof Cliente
                ? $this->orderRepository->reservationsForCustomer($cliente->getKey())
                : $this->orderRepository->reservations(),
            'productos' => $this->orderRepository->availableProducts(),
        ];
    }

    /**
     * @param  array{
     *     cliente_id?: int,
     *     reserva_id?: int|null,
     *     mesa_id?: int|null,
     *     tipo_atencion: string,
     *     modalidad_servicio: string,
     *     fecha_programada?: string|null
     * }  $data
     * @param  list<array{producto_id: int, cantidad: int, observaciones?: string|null}>  $lines
     */
    public function register(Usuario $usuario, array $data, array $lines): Pedido
    {
        return DB::transaction(function () use ($usuario, $data, $lines): Pedido {
            $cliente = $this->resolveCustomer(
                $usuario,
                isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
            );
            $normalized = $this->normalizeOrderData($cliente, $data);
            $products = $this->availableProductsForLines($lines);
            $totalCents = $this->totalInCents($lines, $products);

            $pedido = new Pedido([
                'usuario_id' => $usuario->getKey(),
                'cliente_id' => $cliente->getKey(),
                ...$normalized,
                'estado' => EstadoPedidoEnum::REGISTRADO,
                'total' => $this->formatMoney($totalCents),
            ]);
            $this->orderRepository->save($pedido);
            $this->replaceDetails($pedido, $lines, $products);

            return $this->orderRepository->find($pedido->getKey())
                ?? throw new ModelNotFoundException('No se encontró el pedido creado.');
        });
    }

    /**
     * @param  array{
     *     cliente_id: int,
     *     reserva_id?: int|null,
     *     mesa_id?: int|null,
     *     tipo_atencion: string,
     *     modalidad_servicio: string,
     *     fecha_programada?: string|null,
     *     estado: string
     * }  $data
     * @param  list<array{producto_id: int, cantidad: int, observaciones?: string|null}>  $lines
     */
    public function update(Pedido $pedido, array $data, array $lines): Pedido
    {
        return DB::transaction(function () use ($pedido, $data, $lines): Pedido {
            $lockedPedido = $this->orderRepository->findForUpdate($pedido->getKey());

            if (! $lockedPedido instanceof Pedido) {
                throw new ModelNotFoundException('No se encontró el pedido que se desea actualizar.');
            }

            return $this->updateLocked($lockedPedido, $data, $lines);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePartial(Pedido $pedido, array $data): Pedido
    {
        return DB::transaction(function () use ($pedido, $data): Pedido {
            $lockedPedido = $this->orderRepository->findForUpdate($pedido->getKey());

            if (! $lockedPedido instanceof Pedido) {
                throw new ModelNotFoundException('No se encontró el pedido que se desea actualizar.');
            }

            $lockedPedido->loadMissing('detalles');
            $currentData = [
                'cliente_id' => $lockedPedido->cliente_id,
                'reserva_id' => $lockedPedido->reserva_id,
                'mesa_id' => $lockedPedido->mesa_id,
                'tipo_atencion' => $lockedPedido->tipo_atencion->value,
                'modalidad_servicio' => $lockedPedido->modalidad_servicio->value,
                'fecha_programada' => $lockedPedido->fecha_programada?->format('Y-m-d\TH:i'),
                'estado' => $lockedPedido->estado->value,
            ];
            $lines = $data['lineas'] ?? $lockedPedido->detalles
                ->map(fn (DetallePedido $detalle): array => [
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'observaciones' => $detalle->observaciones,
                ])
                ->all();
            $mergedData = array_replace($currentData, $data);

            if (
                isset($data['modalidad_servicio'])
                && $data['modalidad_servicio'] === ModalidadServicioEnum::SIN_MESA->value
            ) {
                $mergedData['mesa_id'] = null;
                $mergedData['reserva_id'] = null;
            }

            return $this->updateLocked(
                $lockedPedido,
                $mergedData,
                $lines,
                preserveDetails: ! array_key_exists('lineas', $data),
            );
        });
    }

    public function changeStatus(Pedido $pedido, EstadoPedidoEnum $estado): Pedido
    {
        return DB::transaction(function () use ($pedido, $estado): Pedido {
            $lockedPedido = $this->orderRepository->findForUpdate($pedido->getKey());

            if (! $lockedPedido instanceof Pedido) {
                throw new ModelNotFoundException('No se encontró el pedido que se desea actualizar.');
            }

            $this->assertValidTransition($lockedPedido->estado, $estado);

            if (
                $estado === EstadoPedidoEnum::CANCELADO
                && $lockedPedido->pagos()->exists()
            ) {
                throw ValidationException::withMessages([
                    'estado' => 'No se puede cancelar un pedido que tiene pagos asociados.',
                ]);
            }

            $lockedPedido->estado = $estado;
            $this->orderRepository->save($lockedPedido);

            return $this->orderRepository->find($lockedPedido->getKey())
                ?? throw new ModelNotFoundException('No se encontró el pedido actualizado.');
        });
    }

    public function cancel(Pedido $pedido): Pedido
    {
        return $this->changeStatus($pedido, EstadoPedidoEnum::CANCELADO);
    }

    /**
     * @return list<EstadoPedidoEnum>
     */
    public function availableTransitions(Pedido $pedido): array
    {
        $transitions = match ($pedido->estado) {
            EstadoPedidoEnum::REGISTRADO => [
                EstadoPedidoEnum::EN_PREPARACION,
                EstadoPedidoEnum::CANCELADO,
            ],
            EstadoPedidoEnum::EN_PREPARACION => [
                EstadoPedidoEnum::LISTO,
                EstadoPedidoEnum::CANCELADO,
            ],
            EstadoPedidoEnum::LISTO => [EstadoPedidoEnum::ENTREGADO],
            EstadoPedidoEnum::ENTREGADO, EstadoPedidoEnum::CANCELADO => [],
        };

        if ($pedido->pagos()->exists()) {
            return array_values(array_filter(
                $transitions,
                static fn (EstadoPedidoEnum $status): bool => $status !== EstadoPedidoEnum::CANCELADO,
            ));
        }

        return $transitions;
    }

    public function delete(Pedido $pedido): void
    {
        DB::transaction(function () use ($pedido): void {
            $lockedPedido = $this->orderRepository->findForUpdate($pedido->getKey());

            if (! $lockedPedido instanceof Pedido) {
                throw new ModelNotFoundException('No se encontró el pedido que se desea eliminar.');
            }

            if ($lockedPedido->pagos()->exists()) {
                throw ValidationException::withMessages([
                    'pedido' => 'No se puede eliminar un pedido que tiene pagos asociados.',
                ]);
            }

            $this->orderRepository->deleteDetails($lockedPedido);
            $this->orderRepository->delete($lockedPedido);
        });
    }

    private function resolveCustomer(Usuario $usuario, ?int $clienteId): Cliente
    {
        $cliente = $usuario->rol === RolUsuarioEnum::CLIENTE
            ? $usuario->cliente()->first()
            : ($clienteId === null ? null : $this->orderRepository->findCustomer($clienteId));

        if (! $cliente instanceof Cliente) {
            throw new ModelNotFoundException('No se encontró el cliente asociado al pedido.');
        }

        return $cliente;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     reserva_id: int|null,
     *     mesa_id: int|null,
     *     tipo_atencion: TipoAtencionEnum,
     *     modalidad_servicio: ModalidadServicioEnum,
     *     fecha_programada: Carbon|null
     * }
     */
    private function normalizeOrderData(Cliente $cliente, array $data): array
    {
        $tipoAtencion = TipoAtencionEnum::from($data['tipo_atencion']);
        $modalidad = ModalidadServicioEnum::from($data['modalidad_servicio']);
        $reservaId = isset($data['reserva_id']) ? (int) $data['reserva_id'] : null;
        $mesaId = isset($data['mesa_id']) ? (int) $data['mesa_id'] : null;
        $fechaProgramada = isset($data['fecha_programada'])
            ? Carbon::parse($data['fecha_programada'])
            : null;

        if ($tipoAtencion === TipoAtencionEnum::PROGRAMADO) {
            if (! $fechaProgramada instanceof Carbon || $fechaProgramada->isPast()) {
                throw ValidationException::withMessages([
                    'fecha_programada' => 'La fecha programada debe ser posterior al momento actual.',
                ]);
            }
        } else {
            $fechaProgramada = null;
        }

        if ($modalidad === ModalidadServicioEnum::EN_MESA) {
            if ($mesaId === null) {
                throw ValidationException::withMessages([
                    'mesa_id' => 'Selecciona una mesa para el servicio en mesa.',
                ]);
            }

            $mesa = $this->orderRepository->findTable($mesaId);

            if (! $mesa instanceof Mesa) {
                throw new ModelNotFoundException('No se encontró la mesa seleccionada.');
            }

            if ($mesa->estado_operativo !== EstadoOperativoMesaEnum::HABILITADA) {
                throw ValidationException::withMessages([
                    'mesa_id' => 'La mesa seleccionada no está habilitada.',
                ]);
            }
        } elseif ($mesaId !== null || $reservaId !== null) {
            throw ValidationException::withMessages([
                'modalidad_servicio' => 'El servicio sin mesa no puede estar asociado a una mesa o reserva.',
            ]);
        }

        if ($reservaId !== null) {
            $reserva = $this->orderRepository->findReservation($reservaId);

            if (! $reserva instanceof Reserva || $reserva->cliente_id !== $cliente->getKey()) {
                throw ValidationException::withMessages([
                    'reserva_id' => 'La reserva seleccionada no pertenece al cliente del pedido.',
                ]);
            }

            if (! in_array($reserva->estado, [
                EstadoReservaEnum::CONFIRMADA,
                EstadoReservaEnum::ACTIVA,
            ], true)) {
                throw ValidationException::withMessages([
                    'reserva_id' => 'Solo se pueden asociar reservas confirmadas o activas.',
                ]);
            }

            if ($mesaId !== $reserva->mesa_id) {
                throw ValidationException::withMessages([
                    'mesa_id' => 'La mesa debe coincidir con la mesa de la reserva seleccionada.',
                ]);
            }
        }

        return [
            'reserva_id' => $reservaId,
            'mesa_id' => $mesaId,
            'tipo_atencion' => $tipoAtencion,
            'modalidad_servicio' => $modalidad,
            'fecha_programada' => $fechaProgramada,
        ];
    }

    /**
     * @param  list<array{producto_id: int, cantidad: int, observaciones?: string|null}>  $lines
     * @return Collection<int, Producto>
     */
    private function availableProductsForLines(array $lines): Collection
    {
        $productIds = array_values(array_unique(array_map(
            static fn (array $line): int => (int) $line['producto_id'],
            $lines,
        )));
        $products = $this->orderRepository->findAvailableProducts($productIds);

        if ($products->count() !== count($productIds)) {
            throw ValidationException::withMessages([
                'lineas' => 'Uno o más productos ya no están disponibles.',
            ]);
        }

        return $products;
    }

    /**
     * @param  list<array{producto_id: int, cantidad: int, observaciones?: string|null}>  $lines
     * @param  Collection<int, Producto>  $products
     */
    private function totalInCents(array $lines, Collection $products): int
    {
        $productsById = $products->keyBy('id');
        $total = 0;

        foreach ($lines as $line) {
            $product = $productsById->get((int) $line['producto_id']);

            if (! $product instanceof Producto) {
                throw ValidationException::withMessages([
                    'lineas' => 'Uno o más productos ya no están disponibles.',
                ]);
            }

            $priceCents = $this->moneyInCents($product->precio);
            $total += $priceCents * (int) $line['cantidad'];
        }

        if ($total > 999_999_999_999) {
            throw ValidationException::withMessages([
                'lineas' => 'El total del pedido supera el máximo permitido.',
            ]);
        }

        return $total;
    }

    /**
     * @param  list<array{producto_id: int, cantidad: int, observaciones?: string|null}>  $lines
     * @param  Collection<int, Producto>  $products
     */
    private function replaceDetails(Pedido $pedido, array $lines, Collection $products): void
    {
        $pedido->detalles()->delete();
        $productsById = $products->keyBy('id');

        foreach ($lines as $line) {
            $product = $productsById->get((int) $line['producto_id']);

            if (! $product instanceof Producto) {
                throw ValidationException::withMessages([
                    'lineas' => 'Uno o más productos ya no están disponibles.',
                ]);
            }

            $priceCents = $this->moneyInCents($product->precio);
            $quantity = (int) $line['cantidad'];

            $pedido->detalles()->create([
                'producto_id' => $product->getKey(),
                'cantidad' => $quantity,
                'precio_unitario' => $this->formatMoney($priceCents),
                'subtotal' => $this->formatMoney($priceCents * $quantity),
                'observaciones' => $line['observaciones'] ?? null,
            ]);
        }
    }

    private function formatMoney(int $amountInCents): string
    {
        return intdiv($amountInCents, 100).'.'.str_pad(
            (string) ($amountInCents % 100),
            2,
            '0',
            STR_PAD_LEFT,
        );
    }

    private function moneyInCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(
            substr($fraction, 0, 2),
            2,
            '0',
        );
    }

    private function assertValidTransition(
        EstadoPedidoEnum $current,
        EstadoPedidoEnum $next,
    ): void {
        if ($current === $next) {
            return;
        }

        $transitions = [
            EstadoPedidoEnum::REGISTRADO->value => [
                EstadoPedidoEnum::EN_PREPARACION,
                EstadoPedidoEnum::CANCELADO,
            ],
            EstadoPedidoEnum::EN_PREPARACION->value => [
                EstadoPedidoEnum::LISTO,
                EstadoPedidoEnum::CANCELADO,
            ],
            EstadoPedidoEnum::LISTO->value => [
                EstadoPedidoEnum::ENTREGADO,
            ],
            EstadoPedidoEnum::ENTREGADO->value => [],
            EstadoPedidoEnum::CANCELADO->value => [],
        ];

        if (! in_array($next, $transitions[$current->value], true)) {
            throw new OrderStateConflictException;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{producto_id: int, cantidad: int, observaciones?: string|null}>  $lines
     */
    private function updateLocked(
        Pedido $lockedPedido,
        array $data,
        array $lines,
        bool $preserveDetails = false,
    ): Pedido {
        if ($lockedPedido->pagos()->exists()) {
            throw ValidationException::withMessages([
                'pedido' => 'No se puede modificar un pedido que ya tiene pagos asociados.',
            ]);
        }

        $cliente = $this->orderRepository->findCustomer((int) $data['cliente_id']);

        if (! $cliente instanceof Cliente) {
            throw new ModelNotFoundException('No se encontró el cliente seleccionado.');
        }

        $normalized = $this->normalizeOrderData($cliente, $data);
        $nextState = EstadoPedidoEnum::from($data['estado']);
        $this->assertValidTransition($lockedPedido->estado, $nextState);

        $attributes = [
            'cliente_id' => $cliente->getKey(),
            ...$normalized,
            'estado' => $nextState,
        ];

        if (! $preserveDetails) {
            $products = $this->availableProductsForLines($lines);
            $totalCents = $this->totalInCents($lines, $products);
            $attributes['total'] = $this->formatMoney($totalCents);
        }

        $lockedPedido->fill($attributes);
        $this->orderRepository->save($lockedPedido);

        if (! $preserveDetails) {
            $this->replaceDetails($lockedPedido, $lines, $products);
        }

        return $this->orderRepository->find($lockedPedido->getKey())
            ?? throw new ModelNotFoundException('No se encontró el pedido actualizado.');
    }
}
