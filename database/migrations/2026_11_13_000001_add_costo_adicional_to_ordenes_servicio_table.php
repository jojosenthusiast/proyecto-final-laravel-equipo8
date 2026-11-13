<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // [MIGRACION] Migración nueva de Fase 2: la de Fase 1 no se edita (regla de continuidad).
        // costo_total = precio_base del servicio + costo_adicional; lo calcula el backend de Laravel.
        // El default 0 mantiene válidas las órdenes y los seeders que ya existían.
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->decimal('costo_adicional', 10, 2)->default(0)->after('kilometraje_ingreso');
            // El reporte y el panel filtran por estado y período; el índice evita recorrer la tabla.
            $table->index(['estado', 'fecha_ingreso']);
        });
    }

    public function down(): void
    {
        // Orden inverso al de up().
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropIndex(['estado', 'fecha_ingreso']);
            $table->dropColumn('costo_adicional');
        });
    }
};
