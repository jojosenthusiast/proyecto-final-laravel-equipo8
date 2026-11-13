<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // [MIGRACION] Índices para el filtro por marca y la búsqueda por propietario del listado.
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->index('marca');
            $table->index('propietario_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropIndex(['propietario_nombre']);
            $table->dropIndex(['marca']);
        });
    }
};
