<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Regla 5 en el servidor (una orden finalizada no se edita, ni enviando el formulario a mano) y
// reglas 3 y 4 también al editar.
class OrdenEdicionTest extends TestCase
{
    use RefreshDatabase;

    private function datosDe(OrdenServicio $orden, array $cambios = []): array
    {
        return array_merge([
            'vehiculo_id' => $orden->vehiculo_id,
            'tipo_servicio_id' => $orden->tipo_servicio_id,
            'fecha_ingreso' => $orden->fecha_ingreso->toDateString(),
            'fecha_entrega' => $orden->fecha_entrega?->toDateString(),
            'kilometraje_ingreso' => $orden->kilometraje_ingreso,
            'descripcion' => $orden->descripcion,
            'costo_adicional' => $orden->costo_adicional,
        ], $cambios);
    }

    public function test_editar_requiere_sesion(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->get(route('ordenes.edit', $orden))->assertRedirect(route('login'));
        $this->put(route('ordenes.update', $orden), $this->datosDe($orden))->assertRedirect(route('login'));
    }

    public function test_una_orden_pendiente_se_edita_y_recalcula_el_total(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 10000]);
        $orden = OrdenServicio::factory()->pendiente()->create([
            'vehiculo_id' => $vehiculo->id,
            'kilometraje_ingreso' => 20000,
            'costo_adicional' => 0,
        ]);
        $nuevoTipo = TipoServicio::factory()->create(['precio_base' => 70]);

        $this->iniciarSesion()
            ->get(route('ordenes.edit', $orden))
            ->assertOk()
            ->assertViewIs('ordenes.edit')
            ->assertSee($orden->descripcion);

        $this->iniciarSesion()
            ->put(route('ordenes.update', $orden), $this->datosDe($orden, [
                'tipo_servicio_id' => $nuevoTipo->id,
                'costo_adicional' => 5,
                'descripcion' => 'Descripción corregida',
                'costo_total' => 1,
                'estado' => 'finalizada',
            ]))
            ->assertRedirect(route('ordenes.show', $orden))
            ->assertSessionHas('exito', 'Orden actualizada correctamente.');

        $orden->refresh();
        $this->assertSame('Descripción corregida', $orden->descripcion);
        $this->assertSame('75.00', $orden->costo_total);
        $this->assertSame('pendiente', $orden->estado);
    }

    public function test_una_orden_finalizada_responde_403_al_editar_y_no_cambia(): void
    {
        $orden = OrdenServicio::factory()->finalizada()->create(['descripcion' => 'Original']);

        $this->iniciarSesion()
            ->get(route('ordenes.edit', $orden))
            ->assertForbidden()
            ->assertSee('Esta orden está finalizada y no puede editarse.');

        $this->iniciarSesion()
            ->put(route('ordenes.update', $orden), $this->datosDe($orden, ['descripcion' => 'Cambiada a mano']))
            ->assertForbidden();

        $this->assertSame('Original', $orden->fresh()->descripcion);
    }

    public function test_la_ficha_solo_muestra_editar_si_la_orden_puede_editarse(): void
    {
        $pendiente = OrdenServicio::factory()->pendiente()->create();
        $finalizada = OrdenServicio::factory()->finalizada()->create();

        $this->iniciarSesion()
            ->get(route('ordenes.show', $pendiente))
            ->assertSee(route('ordenes.edit', $pendiente), false);

        $this->iniciarSesion()
            ->get(route('ordenes.show', $finalizada))
            ->assertDontSee(route('ordenes.edit', $finalizada), false);
    }

    public function test_editar_sin_cambiar_el_kilometraje_no_se_compara_consigo_misma(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);
        $orden = OrdenServicio::factory()->pendiente()->create([
            'vehiculo_id' => $vehiculo->id,
            'kilometraje_ingreso' => 60000,
        ]);

        // Si la orden se comparara consigo misma, bajar su propio kilometraje a 55 000 fallaría
        // contra sus 60 000; contra el resto del historial (50 000) es válido.
        $this->iniciarSesion()
            ->put(route('ordenes.update', $orden), $this->datosDe($orden, ['kilometraje_ingreso' => 55000]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.show', $orden));

        $this->assertSame(55000, $orden->fresh()->kilometraje_ingreso);
    }

    public function test_aplica_la_regla_3_contra_las_demas_ordenes(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);
        OrdenServicio::factory()->finalizada()->create(['vehiculo_id' => $vehiculo->id, 'kilometraje_ingreso' => 70000]);
        $orden = OrdenServicio::factory()->pendiente()->create(['vehiculo_id' => $vehiculo->id, 'kilometraje_ingreso' => 75000]);

        $this->iniciarSesion()
            ->put(route('ordenes.update', $orden), $this->datosDe($orden, ['kilometraje_ingreso' => 65000]))
            ->assertSessionHasErrors([
                'kilometraje_ingreso' => 'El kilometraje no puede ser menor que el último registrado (70000 km).',
            ]);
    }

    public function test_aplica_la_regla_4_al_editar(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->iniciarSesion()
            ->put(route('ordenes.update', $orden), $this->datosDe($orden, [
                'fecha_entrega' => $orden->fecha_ingreso->copy()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors([
                'fecha_entrega' => 'La fecha de entrega no puede ser anterior a la fecha de ingreso.',
            ]);
    }
}
