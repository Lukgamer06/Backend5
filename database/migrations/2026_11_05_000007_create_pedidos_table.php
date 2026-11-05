<?php

use App\Enums\EstadoPedidoEnum;
use App\Enums\ModalidadServicioEnum;
use App\Enums\TipoAtencionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('reserva_id')
                ->nullable()
                ->constrained('reservas')
                ->nullOnDelete();
            $table->foreignId('mesa_id')
                ->nullable()
                ->constrained('mesas')
                ->nullOnDelete();
            $table->enum('tipo_atencion', array_column(TipoAtencionEnum::cases(), 'value'));
            $table->enum(
                'modalidad_servicio',
                array_column(ModalidadServicioEnum::cases(), 'value')
            );
            $table->enum('estado', array_column(EstadoPedidoEnum::cases(), 'value'))
                ->default(EstadoPedidoEnum::REGISTRADO->value);
            $table->dateTime('fecha_programada')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->index('cliente_id');
            $table->index('mesa_id');
            $table->index('reserva_id');
            $table->index('estado');
            $table->index('fecha_programada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
