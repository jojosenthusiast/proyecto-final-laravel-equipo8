<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Historial de servicios de un vehículo elegido, con total gastado y último kilometraje.
class ReporteHistorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_vehiculo_muestra_el_selector(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->get('/reportes/historial')
            ->assertOk()
            ->assertViewIs('reportes.historial')
            ->assertViewHas('vehiculo', null)
            ->assertSee('name="vehiculo_id"', false)
            ->assertSee($vehiculo->placa);
    }

    public function test_un_vehiculo_inexistente_muestra_el_error_junto_al_campo(): void
    {
        $this->get('/reportes/historial?vehiculo_id=999999')
            ->assertRedirect(route('reportes.historial'))
            ->assertSessionHasErrors(['vehiculo_id' => 'El vehículo seleccionado no existe.']);

        $this->followingRedirects()
            ->get('/reportes/historial?vehiculo_id=999999')
            ->assertOk()
            ->assertSee('id="vehiculo_id-error"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('El vehículo seleccionado no existe.');
    }

    public function test_un_valor_que_no_es_numero_tambien_se_rechaza(): void
    {
        $this->get('/reportes/historial?vehiculo_id=abc')
            ->assertSessionHasErrors('vehiculo_id');
    }

    public function test_un_vehiculo_valido_muestra_sus_ordenes_de_la_mas_reciente_a_la_mas_antigua(): void
    {
        $vehiculo = Vehiculo::factory()->conKilometraje(40000)->create();
        $antigua = OrdenServicio::factory()->finalizada()->for($vehiculo)
            ->create(['fecha_ingreso' => '2026-08-01', 'kilometraje_ingreso' => 45000, 'costo_total' => 25.00]);
        $reciente = OrdenServicio::factory()->finalizada()->for($vehiculo)
            ->create(['fecha_ingreso' => '2026-10-01', 'kilometraje_ingreso' => 52000, 'costo_total' => 40.00]);
        $pendiente = OrdenServicio::factory()->pendiente()->for($vehiculo)
            ->create(['fecha_ingreso' => '2026-09-01', 'kilometraje_ingreso' => 50000, 'costo_total' => 100.00]);
        OrdenServicio::factory()->finalizada()->create(['costo_total' => 999.00]);

        $this->get('/reportes/historial?vehiculo_id='.$vehiculo->id)
            ->assertOk()
            ->assertViewHas('vehiculo', fn ($v) => $v->is($vehiculo)
                && $v->ordenes->pluck('id')->all() === [$reciente->id, $pendiente->id, $antigua->id])
            ->assertViewHas('totalGastado', 65.0)
            ->assertViewHas('ultimoKilometraje', 52000)
            ->assertSee('65.00')
            ->assertSeeInOrder(['01/10/2026', '01/09/2026', '01/08/2026']);
    }

    public function test_un_vehiculo_sin_ordenes_muestra_el_estado_vacio(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->get('/reportes/historial?vehiculo_id='.$vehiculo->id)
            ->assertOk()
            ->assertViewHas('totalGastado', 0.0)
            ->assertSee('Este vehículo no tiene órdenes registradas.');
    }
}
