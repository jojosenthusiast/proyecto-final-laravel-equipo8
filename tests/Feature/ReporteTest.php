<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Reporte del caso (página 8 de 4.1): órdenes pendientes e ingresos por tipo de servicio.
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private TipoServicio $frenos;

    private OrdenServicio $pendiente;

    protected function setUp(): void
    {
        parent::setUp();

        // Datos conocidos: dos finalizadas de 25.00 y 40.00 en Frenos y una pendiente de 100.00.
        $this->frenos = TipoServicio::factory()->create(['nombre' => 'Frenos']);
        $hoy = now()->toDateString();

        OrdenServicio::factory()->finalizada()->for($this->frenos, 'tipoServicio')
            ->create(['fecha_ingreso' => $hoy, 'costo_total' => 25.00]);
        OrdenServicio::factory()->finalizada()->for($this->frenos, 'tipoServicio')
            ->create(['fecha_ingreso' => $hoy, 'costo_total' => 40.00]);
        $this->pendiente = OrdenServicio::factory()->pendiente()->for($this->frenos, 'tipoServicio')
            ->create(['fecha_ingreso' => now()->subDays(3)->toDateString(), 'costo_total' => 100.00]);
    }

    public function test_el_reporte_responde_200_sin_consultas_n_mas_1(): void
    {
        // preventLazyLoading (C1) convierte cualquier N+1 de la vista en un error 500.
        $this->get('/reportes')->assertOk()->assertViewIs('reportes.index');
    }

    public function test_los_ingresos_por_servicio_solo_suman_ordenes_finalizadas(): void
    {
        $this->get('/reportes')
            ->assertOk()
            ->assertViewHas('ingresos', function ($ingresos) {
                $frenos = $ingresos->firstWhere('nombre', 'Frenos');

                return (float) $frenos->ingresos === 65.0 && (int) $frenos->ordenes_finalizadas === 2;
            })
            ->assertViewHas('totalGeneral', fn ($total) => (float) $total === 65.0)
            ->assertSee('65.00');
    }

    public function test_la_tabla_de_pendientes_muestra_solo_ordenes_sin_finalizar(): void
    {
        $enProceso = OrdenServicio::factory()->enProceso()->create(['fecha_ingreso' => now()->toDateString()]);

        $this->get('/reportes')
            ->assertOk()
            ->assertViewHas('pendientes', fn ($pendientes) => $pendientes->pluck('id')->sort()->values()->all()
                === collect([$this->pendiente->id, $enProceso->id])->sort()->values()->all())
            ->assertSee($this->pendiente->vehiculo()->value('placa'));
    }

    public function test_los_pendientes_van_del_mas_antiguo_al_mas_reciente_con_dias_en_el_taller(): void
    {
        OrdenServicio::factory()->pendiente()->create(['fecha_ingreso' => now()->toDateString()]);

        $this->get('/reportes')
            ->assertOk()
            ->assertViewHas('pendientes', fn ($pendientes) => $pendientes->first()->is($this->pendiente)
                && $pendientes->first()->dias_en_taller === 3);
    }

    public function test_sin_ordenes_muestra_los_estados_vacios(): void
    {
        OrdenServicio::query()->delete();

        $this->get('/reportes')
            ->assertOk()
            ->assertViewHas('pendientes', fn ($pendientes) => $pendientes->isEmpty())
            ->assertSee('No hay órdenes pendientes.');
    }
}
