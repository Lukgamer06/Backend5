<?php

use App\Enums\EstadoReservaEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->unsignedTinyInteger('numero_personas');
            $table->enum('estado', array_column(EstadoReservaEnum::cases(), 'value'))
                ->default(EstadoReservaEnum::CONFIRMADA->value);
            $table->text('observaciones')->nullable();
            $table->index('mesa_id');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
