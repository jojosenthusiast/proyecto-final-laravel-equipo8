<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_reporte_incluye_impresion_y_las_etiquetas_de_sus_tablas(): void
    {
        $this->seed();

        $this->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('css/reportes.css', false)
            ->assertSee('data-imprimir', false)
            ->assertSee('data-label="Ingresos"', false)
            ->assertSee('class="barra-ingresos"', false);
    }
}
