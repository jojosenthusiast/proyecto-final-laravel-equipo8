<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Guion de la defensa automatizado: registrar una orden, avanzar sus estados, intentar editarla
// ya finalizada y ver su ingreso en el reporte. Si esta prueba pasa, el recorrido en vivo funciona.
class CicloOrdenTest extends TestCase
{
    use RefreshDatabase;

    public function test_ciclo_completo_de_una_orden(): void
    {
        $vehiculo = Vehiculo::factory()->create(['placa' => 'P777777', 'kilometraje' => 40000]);
        $tipo = TipoServicio::factory()->create(['nombre' => 'Alineación y balanceo', 'precio_base' => 35]);

        $this->iniciarSesion();

        // 1. Desde la ficha del vehículo se abre el formulario con el vehículo preseleccionado.
        $this->get(route('ordenes.create', ['vehiculo' => $vehiculo->id]))
            ->assertOk()
            ->assertSee('<option value="'.$vehiculo->id.'" selected', false);

        // 2. Registrar por HTTP: el total lo calcula el backend y el vehículo sube su kilometraje.
        $this->post(route('ordenes.store'), [
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
            'fecha_ingreso' => today()->toDateString(),
            'kilometraje_ingreso' => 42500,
            'descripcion' => 'Alineación de las cuatro ruedas',
            'costo_adicional' => 15,
            'costo_total' => 1,
        ])->assertSessionHasNoErrors();

        $orden = OrdenServicio::sole();
        $this->assertSame('pendiente', $orden->estado);
        $this->assertSame('50.00', $orden->costo_total);
        $this->assertSame(42500, $vehiculo->fresh()->kilometraje);

        // 3. Regla 3: otra orden con menos kilometraje se rechaza.
        $this->post(route('ordenes.store'), [
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
            'fecha_ingreso' => today()->toDateString(),
            'kilometraje_ingreso' => 41000,
            'descripcion' => 'Kilometraje menor',
        ])->assertSessionHasErrors('kilometraje_ingreso');
        $this->assertSame(1, OrdenServicio::count());

        // 4. Avanzar estados sin saltos: pendiente -> en proceso -> finalizada.
        $this->patch(route('ordenes.estado', $orden), ['estado' => 'finalizada'])
            ->assertSessionHasErrors('estado');
        $this->patch(route('ordenes.estado', $orden), ['estado' => 'en_proceso'])
            ->assertSessionHasNoErrors();
        $this->patch(route('ordenes.estado', $orden), ['estado' => 'finalizada'])
            ->assertSessionHasNoErrors();

        $orden->refresh();
        $this->assertSame('finalizada', $orden->estado);
        $this->assertSame(today()->toDateString(), $orden->fecha_entrega->toDateString());

        // 5. Regla 5: la orden finalizada no se edita ni se elimina, ni a mano.
        $this->get(route('ordenes.edit', $orden))
            ->assertForbidden()
            ->assertSee('Esta orden está finalizada y no puede editarse.');
        $this->put(route('ordenes.update', $orden), ['descripcion' => 'Cambio a mano'])
            ->assertForbidden();
        $this->delete(route('ordenes.destroy', $orden))->assertForbidden();
        $this->assertSame('Alineación de las cuatro ruedas', $orden->fresh()->descripcion);

        // 6. El ingreso aparece en el reporte de Carlos (C8).
        $this->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Alineación y balanceo')
            ->assertViewHas('ingresos', function ($ingresos) {
                $alineacion = $ingresos->firstWhere('nombre', 'Alineación y balanceo');

                return (float) $alineacion->ingresos === 50.0;
            })
            ->assertViewHas('totalGeneral', fn ($total) => (float) $total === 50.0);

        // 7. La ficha y el listado reflejan el estado final.
        $this->get(route('ordenes.show', $orden))
            ->assertOk()
            ->assertSee('etiqueta etiqueta-finalizada', false)
            ->assertDontSee(route('ordenes.edit', $orden), false);
        $this->get(route('ordenes.index', ['placa' => 'P777777', 'estado' => 'finalizada']))
            ->assertOk()
            ->assertViewHas('ordenes', fn ($ordenes) => $ordenes->total() === 1);
    }
}
