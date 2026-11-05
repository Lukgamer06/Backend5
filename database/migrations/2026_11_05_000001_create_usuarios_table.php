<?php

use App\Enums\RolUsuarioEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('correo', 255)->unique();
            $table->string('telefono', 30)->nullable();
            $table->enum('rol', array_column(RolUsuarioEnum::cases(), 'value'))
                ->default(RolUsuarioEnum::CLIENTE->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
