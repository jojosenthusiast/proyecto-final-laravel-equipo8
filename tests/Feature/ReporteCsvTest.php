<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Filtro por fechas del reporte (por defecto el mes actual) y exportación de ingresos a CSV (+3).
class ReporteCsvTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha fija: el filtro por defecto depende del mes actual.
        $this->travelTo('2026-11-13 10:00:00');

        $frenos = TipoServicio::factory()->create(['nombre' => 'Frenos']);
        $aceite = TipoServicio::factory()->create(['nombre' => 'Cambio de aceite']);

        // Noviembre: dos finalizadas de Frenos (25.00 y 40.00) y una pendiente.
        OrdenServicio::factory()->finalizada()->for($frenos, 'tipoServicio')
            ->create(['fecha_ingreso' => '2026-11-02', 'costo_total' => 25.00]);
        OrdenServicio::factory()->finalizada()->for($frenos, 'tipoServicio')
            ->create(['fecha_ingreso' => '2026-11-10', 'costo_total' => 40.00]);
        OrdenServicio::factory()->pendiente()->for($frenos, 'tipoServicio')
            ->create(['fecha_ingreso' => '2026-11-12', 'costo_total' => 100.00]);

        // Octubre: una finalizada de aceite (30.00) y una pendiente.
        OrdenServicio::factory()->finalizada()->for($aceite, 'tipoServicio')
            ->create(['fecha_ingreso' => '2026-10-15', 'costo_total' => 30.00]);
        OrdenServicio::factory()->pendiente()->for($aceite, 'tipoServicio')
            ->create(['fecha_ingreso' => '2026-10-20', 'costo_total' => 50.00]);
    }

    public function test_sin_fechas_el_reporte_usa_el_mes_actual(): void
    {
        $this->get('/reportes')
            ->assertOk()
            ->assertViewHas('desde', '2026-11-01')
            ->assertViewHas('hasta', '2026-11-30')
            ->assertViewHas('totalGeneral', 65.0)
            ->assertViewHas('ingresos', fn ($ingresos) => (float) $ingresos->firstWhere('nombre', 'Cambio de aceite')->ingresos === 0.0)
            ->assertViewHas('pendientes', fn ($pendientes) => $pendientes->count() === 1);
    }

    public function test_el_filtro_de_fechas_cambia_los_totales(): void
    {
        $this->get('/reportes?desde=2026-10-01&hasta=2026-10-31')
            ->assertOk()
            ->assertViewHas('totalGeneral', 30.0)
            ->assertViewHas('ingresos', fn ($ingresos) => (float) $ingresos->firstWhere('nombre', 'Frenos')->ingresos === 0.0
                && (float) $ingresos->firstWhere('nombre', 'Cambio de aceite')->ingresos === 30.0)
            ->assertViewHas('pendientes', fn ($pendientes) => $pendientes->count() === 1);

        $this->get('/reportes?desde=2026-10-01&hasta=2026-11-30')
            ->assertOk()
            ->assertViewHas('totalGeneral', 95.0);
    }

    public function test_hasta_anterior_a_desde_da_error_en_hasta(): void
    {
        $this->get('/reportes?desde=2026-11-10&hasta=2026-11-01')
            ->assertRedirect(route('reportes.index'))
            ->assertSessionHasErrors(['hasta' => 'La fecha final no puede ser anterior a la fecha inicial.']);

        $this->followingRedirects()
            ->get('/reportes?desde=2026-11-10&hasta=2026-11-01')
            ->assertSee('id="hasta-error"', false);
    }

    public function test_una_fecha_invalida_da_error(): void
    {
        $this->get('/reportes?desde=no-es-fecha')
            ->assertSessionHasErrors('desde');
    }

    public function test_solo_hasta_sin_desde_es_valido(): void
    {
        $this->get('/reportes?hasta=2026-10-31')
            ->assertOk()
            ->assertViewHas('totalGeneral', 30.0);
    }

    public function test_exporta_los_ingresos_del_mes_a_csv_con_bom_y_encabezados(): void
    {
        $respuesta = $this->get('/reportes/exportar');

        $respuesta->assertOk()->assertDownload('ingresos-por-servicio.csv');
        $contenido = $respuesta->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $lineas = preg_split('/\R/', trim(substr($contenido, 3)));
        $this->assertSame('Servicio,Órdenes,Ingresos', $lineas[0]);
        $this->assertContains('Frenos,2,65.00', $lineas);
        $this->assertContains('"Cambio de aceite",0,0.00', $lineas);
    }

    public function test_el_csv_respeta_el_mismo_filtro_de_fechas(): void
    {
        $contenido = $this->get('/reportes/exportar?desde=2026-10-01&hasta=2026-10-31')->streamedContent();

        $this->assertStringContainsString('"Cambio de aceite",1,30.00', $contenido);
        $this->assertStringContainsString('Frenos,0,0.00', $contenido);
    }

    public function test_el_reporte_enlaza_la_exportacion_con_el_filtro_actual(): void
    {
        $this->get('/reportes?desde=2026-10-01&hasta=2026-10-31')
            ->assertSee(route('reportes.exportar', ['desde' => '2026-10-01', 'hasta' => '2026-10-31']));
    }
}
