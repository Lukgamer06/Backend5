<?php

use App\Enums\CanalPagoEnum;
use App\Enums\EstadoPagoEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->enum('canal', array_column(CanalPagoEnum::cases(), 'value'));
            $table->enum('estado', array_column(EstadoPagoEnum::cases(), 'value'))
                ->default(EstadoPagoEnum::PENDIENTE->value);
            $table->string('referencia_operacion', 100)->unique();
            $table->string('clave_idempotencia', 100)->unique();
            $table->string('proveedor', 100)->nullable();
            $table->string('transaccion_externa', 150)->nullable();
            $table->unique(['proveedor', 'transaccion_externa']);
            $table->index('pedido_id');
            $table->index('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
