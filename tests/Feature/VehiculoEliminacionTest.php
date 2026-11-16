<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Eliminación del vehículo: solo por DELETE, con confirmación, sin error 500 cuando tiene órdenes
// (restrictOnDelete) y sin dejar su foto en el disco (la borra el observer de Fernando, F4).
class VehiculoEliminacionTest extends TestCase
{
    use RefreshDatabase;

    private const FOTO = 'vehiculos/foto.png';

    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put(self::FOTO, 'foto');

        $this->vehiculo = Vehiculo::factory()->create(['placa' => 'P820001', 'imagen' => self::FOTO]);
    }

    private function eliminarDesde(string $origen)
    {
        return $this->iniciarSesion()->from($origen)->delete(route('vehiculos.destroy', $this->vehiculo));
    }

    public function test_sin_ordenes_se_borran_la_fila_y_la_foto(): void
    {
        $this->eliminarDesde(route('vehiculos.index'))
            ->assertRedirect(route('vehiculos.index'))
            ->assertSessionHas('exito', 'Vehículo eliminado correctamente.');

        $this->assertModelMissing($this->vehiculo);
        Storage::disk('public')->assertMissing(self::FOTO);
    }

    public function test_al_borrar_desde_la_ficha_vuelve_al_listado_con_el_mensaje(): void
    {
        // La ficha del vehículo borrado ya no existe: regresar a ella daría 404.
        $this->iniciarSesion()->from(route('vehiculos.show', $this->vehiculo))->followingRedirects()
            ->delete(route('vehiculos.destroy', $this->vehiculo))
            ->assertOk()
            ->assertSee('Vehículos registrados')
            ->assertSee('Vehículo eliminado correctamente.');
    }

    public function test_con_ordenes_no_se_borra_y_explica_cuantas_tiene(): void
    {
        OrdenServicio::factory()->count(2)->create(['vehiculo_id' => $this->vehiculo->id]);

        // El controlador revisa las órdenes antes de borrar: con órdenes ni siquiera intenta el
        // DELETE, no depende de que la base lo rechace.
        $seIntentoBorrar = false;
        Vehiculo::deleting(function () use (&$seIntentoBorrar) {
            $seIntentoBorrar = true;
        });

        $this->eliminarDesde(route('vehiculos.show', $this->vehiculo))
            ->assertRedirect(route('vehiculos.show', $this->vehiculo))
            ->assertSessionHas('error', 'No se puede eliminar: el vehículo tiene 2 órdenes registradas.');

        $this->assertFalse($seIntentoBorrar, 'Con órdenes no se debe intentar el borrado.');
        $this->assertModelExists($this->vehiculo);
        $this->assertSame(2, $this->vehiculo->ordenes()->count());
        Storage::disk('public')->assertExists(self::FOTO);
    }

    public function test_con_una_sola_orden_el_mensaje_va_en_singular(): void
    {
        OrdenServicio::factory()->create(['vehiculo_id' => $this->vehiculo->id]);

        $this->eliminarDesde(route('vehiculos.index'))
            ->assertRedirect(route('vehiculos.index'))
            ->assertSessionHas('error', 'No se puede eliminar: el vehículo tiene 1 orden registrada.');
    }

    public function test_el_mensaje_de_error_se_ve_en_la_pagina_de_origen(): void
    {
        OrdenServicio::factory()->count(3)->create(['vehiculo_id' => $this->vehiculo->id]);

        $this->iniciarSesion()->from(route('vehiculos.index'))->followingRedirects()
            ->delete(route('vehiculos.destroy', $this->vehiculo))
            ->assertOk()
            ->assertSee('class="alerta alerta-error"', false)
            ->assertSee('No se puede eliminar: el vehículo tiene 3 órdenes registradas.');
    }

    public function test_si_la_base_rechaza_el_borrado_responde_con_el_mensaje_y_no_con_error_500(): void
    {
        // Simula una orden registrada justo después de la revisión: la orden aparece al empezar
        // el borrado y la llave foránea (restrictOnDelete) rechaza el DELETE.
        Vehiculo::deleting(fn (Vehiculo $vehiculo) => OrdenServicio::factory()->create(['vehiculo_id' => $vehiculo->id]));

        $this->eliminarDesde(route('vehiculos.index'))
            ->assertRedirect(route('vehiculos.index'))
            ->assertSessionHas('error', 'No se puede eliminar: el vehículo tiene 1 orden registrada.');

        $this->assertModelExists($this->vehiculo);
        Storage::disk('public')->assertExists(self::FOTO);
    }

    public function test_una_peticion_get_a_la_url_no_borra_nada(): void
    {
        // La URL de borrado es la misma de la ficha: por GET solo se ve el vehículo.
        $this->iniciarSesion()->get(route('vehiculos.destroy', $this->vehiculo))->assertOk()
            ->assertSee('P820001');

        // Un POST sin _method=DELETE tampoco llega a destroy().
        $this->iniciarSesion()->post(route('vehiculos.destroy', $this->vehiculo))->assertMethodNotAllowed();

        $this->assertModelExists($this->vehiculo);
        Storage::disk('public')->assertExists(self::FOTO);
    }

    public function test_sin_sesion_no_se_puede_eliminar(): void
    {
        $this->delete(route('vehiculos.destroy', $this->vehiculo))->assertRedirect(route('login'));

        $this->assertModelExists($this->vehiculo);
        Storage::disk('public')->assertExists(self::FOTO);
    }

    public function test_eliminar_un_vehiculo_que_no_existe_responde_404(): void
    {
        $this->iniciarSesion()->delete(route('vehiculos.destroy', 999999))->assertNotFound();
    }

    public function test_el_listado_muestra_el_formulario_de_eliminacion_solo_con_sesion(): void
    {
        $this->get(route('vehiculos.index'))->assertOk()
            ->assertDontSee('name="_method" value="DELETE"', false);

        $this->iniciarSesion()->get(route('vehiculos.index'))->assertOk()
            ->assertSee('action="'.route('vehiculos.destroy', $this->vehiculo).'"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('data-confirmar="¿Eliminar el vehículo P820001?"', false)
            ->assertSee('onsubmit="return confirm(this.dataset.confirmar)"', false)
            ->assertSee('<button class="boton boton-peligro">Eliminar</button>', false);
    }
}
