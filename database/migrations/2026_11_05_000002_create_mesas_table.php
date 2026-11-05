<?php

use App\Enums\EstadoMesaEnum;
use App\Enums\EstadoOperativoMesaEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('numero')->unique();
            $table->unsignedTinyInteger('capacidad');
            $table->enum('estado', array_column(EstadoMesaEnum::cases(), 'value'))
                ->default(EstadoMesaEnum::LIBRE->value);
            $table->enum(
                'estado_operativo',
                array_column(EstadoOperativoMesaEnum::cases(), 'value')
            )->default(EstadoOperativoMesaEnum::HABILITADA->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
