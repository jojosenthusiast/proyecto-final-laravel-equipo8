<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Comprueba el único cambio de esquema de la Fase 2, hecho con migraciones nuevas.
class EsquemaFase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_ordenes_servicio_tiene_costo_adicional_e_indice_de_estado_y_fecha(): void
    {
        $this->assertTrue(Schema::hasColumn('ordenes_servicio', 'costo_adicional'));
        $this->assertTrue(Schema::hasIndex('ordenes_servicio', ['estado', 'fecha_ingreso']));
    }

    public function test_vehiculos_tiene_indices_de_marca_y_propietario(): void
    {
        $this->assertTrue(Schema::hasIndex('vehiculos', ['marca']));
        $this->assertTrue(Schema::hasIndex('vehiculos', ['propietario_nombre']));
    }
}
