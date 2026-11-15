<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

// El catálogo muestra cuántas órdenes usan cada servicio y no permite borrar uno que ya tiene
// historial: la llave foránea es restrictOnDelete y la aplicación lo explica antes de intentarlo.
class TipoServicioEliminarTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_servicio_con_ordenes_no_se_borra_y_muestra_el_motivo(): void
    {
        $servicio = TipoServicio::factory()->create(['nombre' => 'Frenos']);
        OrdenServicio::factory()->create(['tipo_servicio_id' => $servicio->id]);

        $this->iniciarSesion()
            ->delete(route('servicios.destroy', $servicio))
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tipos_servicio', ['id' => $servicio->id]);
    }

    public function test_un_servicio_sin_ordenes_se_borra(): void
    {
        $servicio = TipoServicio::factory()->create(['nombre' => 'Balanceo']);

        $this->iniciarSesion()
            ->delete(route('servicios.destroy', $servicio))
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseMissing('tipos_servicio', ['id' => $servicio->id]);
    }

    public function test_el_catalogo_muestra_cuantas_ordenes_usa_cada_servicio(): void
    {
        $servicio = TipoServicio::factory()->create(['nombre' => 'Alineación']);
        OrdenServicio::factory()->count(2)->create(['tipo_servicio_id' => $servicio->id]);

        $this->get(route('servicios.index'))
            ->assertOk()
            ->assertSee('data-label="Órdenes"', false)
            ->assertViewHas('tiposServicio', function ($tipos) use ($servicio) {
                $coleccion = $tipos instanceof LengthAwarePaginator
                    ? $tipos->getCollection()
                    : $tipos;

                return $coleccion->firstWhere('id', $servicio->id)->ordenes_count === 2;
            });
    }
}
