<?php

use App\Enums\EstadoIncidenciaEnum;
use App\Enums\PrioridadIncidenciaEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_reporta_id')
                ->constrained('usuarios')
                ->restrictOnDelete();
            $table->foreignId('mesa_id')
                ->nullable()
                ->constrained('mesas')
                ->nullOnDelete();
            $table->foreignId('pedido_id')
                ->nullable()
                ->constrained('pedidos')
                ->nullOnDelete();
            $table->string('titulo', 150);
            $table->text('descripcion');
            $table->enum(
                'prioridad',
                array_column(PrioridadIncidenciaEnum::cases(), 'value')
            )->default(PrioridadIncidenciaEnum::MEDIA->value);
            $table->enum('estado', array_column(EstadoIncidenciaEnum::cases(), 'value'))
                ->default(EstadoIncidenciaEnum::ABIERTA->value);
            $table->index('prioridad');
            $table->index('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};
