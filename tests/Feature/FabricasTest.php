<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Las fábricas que usa todo el equipo producen datos coherentes con las reglas del caso.
class FabricasTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_fabrica_de_tipos_de_servicio_crea_nombres_unicos_y_precio_positivo(): void
    {
        $tipos = TipoServicio::factory()->count(5)->create();

        $this->assertCount(5, $tipos->pluck('nombre')->unique());
        $this->assertTrue($tipos->every(fn ($tipo) => (float) $tipo->precio_base > 0));
    }

    public function test_una_orden_crea_su_vehiculo_y_su_servicio_y_calcula_el_costo_total(): void
    {
        $orden = OrdenServicio::factory()->create();

        $this->assertNotNull($orden->vehiculo_id);
        $this->assertNotNull($orden->tipo_servicio_id);
        $this->assertSame(
            round((float) $orden->tipoServicio()->value('precio_base') + (float) $orden->costo_adicional, 2),
            (float) $orden->costo_total
        );
    }

    public function test_el_costo_total_usa_el_precio_del_servicio_indicado(): void
    {
        $tipo = TipoServicio::factory()->create(['precio_base' => 80]);

        $orden = OrdenServicio::factory()->for($tipo, 'tipoServicio')->create(['costo_adicional' => 15.50]);

        $this->assertSame('95.50', $orden->costo_total);
    }

    public function test_los_estados_de_la_orden_producen_el_estado_esperado(): void
    {
        $pendiente = OrdenServicio::factory()->pendiente()->create();
        $enProceso = OrdenServicio::factory()->enProceso()->create();
        $finalizada = OrdenServicio::factory()->finalizada()->create();

        $this->assertSame('pendiente', $pendiente->estado);
        $this->assertNull($pendiente->fecha_entrega);
        $this->assertSame('en_proceso', $enProceso->estado);
        $this->assertNull($enProceso->fecha_entrega);
        $this->assertSame('finalizada', $finalizada->estado);
        $this->assertNotNull($finalizada->fecha_entrega);
    }

    public function test_una_orden_finalizada_nunca_tiene_entrega_anterior_al_ingreso(): void
    {
        $ordenes = OrdenServicio::factory()->finalizada()->count(20)->create();

        $this->assertTrue($ordenes->every(
            fn ($orden) => $orden->fecha_entrega->greaterThanOrEqualTo($orden->fecha_ingreso)
        ));
    }

    public function test_vehiculo_con_kilometraje_fija_el_valor_y_la_placa_cumple_el_formato(): void
    {
        $vehiculo = Vehiculo::factory()->conKilometraje(80000)->create();

        $this->assertSame(80000, $vehiculo->kilometraje);
        $this->assertMatchesRegularExpression('/^P\d{6}$/', $vehiculo->placa);
    }
}
