<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // [MIGRACION] Entidad principal; la imagen guarda solo la ruta del archivo, nunca el binario.
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10)->unique();
            $table->string('marca', 50);
            $table->string('modelo', 50);
            $table->unsignedSmallInteger('anio');
            $table->string('color', 30);
            $table->unsignedInteger('kilometraje');
            $table->string('propietario_nombre', 100);
            $table->string('propietario_telefono', 20);
            $table->string('imagen', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
