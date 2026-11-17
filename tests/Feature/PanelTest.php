<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_calcula_los_indicadores_en_hasta_seis_consultas(): void
    {
        $this->travelTo('2026-11-17 12:00:00');
        $vehiculo = Vehiculo::factory()->create();
        $antigua = OrdenServicio::factory()->for($vehiculo)->enProceso()->create([
            'fecha_ingreso' => '2026-10-29', 'costo_total' => 100,
        ]);
        OrdenServicio::factory()->for($vehiculo)->finalizada()->create([
            'fecha_ingreso' => '2026-11-01', 'fecha_entrega' => '2026-11-03', 'costo_total' => 65,
        ]);
        OrdenServicio::factory()->for($vehiculo)->finalizada()->create([
            'fecha_ingreso' => '2026-10-01', 'fecha_entrega' => '2026-10-03', 'costo_total' => 987,
        ]);

        DB::enableQueryLog();

        try {
            $this->get(route('inicio'))
                ->assertOk()
                ->assertViewHas('totalVehiculos', 1)
                ->assertViewHas('totalOrdenes', 3)
                ->assertViewHas('ingresosMes', 65.0)
                ->assertViewHas('ordenesPorEstado', ['pendiente' => 0, 'en_proceso' => 1, 'finalizada' => 2])
                ->assertViewHas('porcentajes', ['pendiente' => 0, 'en_proceso' => 33, 'finalizada' => 67])
                ->assertViewHas('ordenAntigua', fn (OrdenServicio $orden) => $orden->is($antigua));
            $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
