<?php

use App\Enums\EstadoMenuDiaEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus_dia', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha')->unique();
            $table->enum('estado', array_column(EstadoMenuDiaEnum::cases(), 'value'))
                ->default(EstadoMenuDiaEnum::BORRADOR->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus_dia');
    }
};
