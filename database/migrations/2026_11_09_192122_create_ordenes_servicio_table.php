<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_servicio', function (Blueprint $table) { // [MIGRACION]
            $table->id();
            // restrictOnDelete: un taller no pierde el historial de un vehículo ni borra un servicio con órdenes.
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete(); // [RELACION]
            $table->foreignId('tipo_servicio_id')->constrained('tipos_servicio')->restrictOnDelete(); // [RELACION]
            $table->date('fecha_ingreso');
            $table->date('fecha_entrega')->nullable();
            $table->unsignedInteger('kilometraje_ingreso');
            $table->text('descripcion');
            $table->decimal('costo_total', 10, 2);
            $table->enum('estado', ['pendiente', 'en_proceso', 'finalizada'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_servicio');
    }
};
