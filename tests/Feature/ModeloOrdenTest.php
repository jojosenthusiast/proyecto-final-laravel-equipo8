<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Estados, transiciones y scopes de OrdenServicio: el contrato que usan el reporte, el panel y órdenes.
class ModeloOrdenTest extends TestCase
{
    use RefreshDatabase;

    private function crearOrden(string $estado, string $fechaIngreso = '2026-11-01'): OrdenServicio
    {
        $tipo = TipoServicio::firstOrCreate(
            ['nombre' => 'Frenos'],
            ['precio_base' => 80]
        );

        return OrdenServicio::factory()->create([
            'vehiculo_id' => Vehiculo::factory()->create()->id,
            'tipo_servicio_id' => $tipo->id,
            'fecha_ingreso' => $fechaIngreso,
            'estado' => $estado,
        ]);
    }

    public function test_las_constantes_definen_estados_y_la_unica_transicion_de_cada_uno(): void
    {
        $this->assertSame(['pendiente', 'en_proceso', 'finalizada'], OrdenServicio::ESTADOS);
        $this->assertSame(
            ['pendiente' => 'en_proceso', 'en_proceso' => 'finalizada'],
            OrdenServicio::TRANSICIONES
        );
    }

    public function test_scope_estado_filtra_por_el_estado_indicado(): void
    {
        $this->crearOrden('pendiente');
        $this->crearOrden('finalizada');
        $this->crearOrden('finalizada');

        $this->assertSame(2, OrdenServicio::estado('finalizada')->count());
        $this->assertSame(1, OrdenServicio::estado('pendiente')->count());
    }

    public function test_scope_pendientes_incluye_pendientes_y_en_proceso(): void
    {
        $this->crearOrden('pendiente');
        $this->crearOrden('en_proceso');
        $this->crearOrden('finalizada');

        $this->assertSame(2, OrdenServicio::pendientes()->count());
    }

    public function test_scope_del_periodo_filtra_por_fecha_de_ingreso_con_limites_opcionales(): void
    {
        $this->crearOrden('pendiente', '2026-09-15');
        $this->crearOrden('pendiente', '2026-10-01');
        $this->crearOrden('pendiente', '2026-10-31');
        $this->crearOrden('pendiente', '2026-11-05');

        $this->assertSame(2, OrdenServicio::delPeriodo('2026-10-01', '2026-10-31')->count());
        $this->assertSame(3, OrdenServicio::delPeriodo('2026-10-01', null)->count());
        $this->assertSame(1, OrdenServicio::delPeriodo(null, '2026-09-30')->count());
        $this->assertSame(4, OrdenServicio::delPeriodo(null, null)->count());
    }

    public function test_esta_finalizada_y_puede_editarse_aplican_la_regla_5(): void
    {
        $finalizada = $this->crearOrden('finalizada');
        $pendiente = $this->crearOrden('pendiente');

        $this->assertTrue($finalizada->estaFinalizada());
        $this->assertFalse($finalizada->puedeEditarse());
        $this->assertFalse($pendiente->estaFinalizada());
        $this->assertTrue($pendiente->puedeEditarse());
    }

    public function test_siguiente_estado_sigue_las_transiciones_y_es_null_al_finalizar(): void
    {
        $this->assertSame('en_proceso', $this->crearOrden('pendiente')->siguienteEstado());
        $this->assertSame('finalizada', $this->crearOrden('en_proceso')->siguienteEstado());
        $this->assertNull($this->crearOrden('finalizada')->siguienteEstado());
    }
}
