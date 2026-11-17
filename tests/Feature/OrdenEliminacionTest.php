<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Eliminación segura: solo órdenes pendientes, por DELETE y con confirmación. Una orden en
// proceso o finalizada es historial trabajado y no se borra.
class OrdenEliminacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_eliminar_requiere_sesion(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->delete(route('ordenes.destroy', $orden))->assertRedirect(route('login'));

        $this->assertModelExists($orden);
    }

    public function test_delete_a_una_pendiente_la_borra(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->iniciarSesion()
            ->delete(route('ordenes.destroy', $orden))
            ->assertRedirect(route('ordenes.index'))
            ->assertSessionHas('exito', "Orden #{$orden->id} eliminada correctamente.");

        $this->assertModelMissing($orden);
    }

    public function test_delete_a_una_en_proceso_responde_403(): void
    {
        $orden = OrdenServicio::factory()->enProceso()->create();

        $this->iniciarSesion()
            ->delete(route('ordenes.destroy', $orden))
            ->assertForbidden()
            ->assertSee('Solo se pueden eliminar órdenes pendientes.');

        $this->assertModelExists($orden);
    }

    public function test_delete_a_una_finalizada_responde_403(): void
    {
        $orden = OrdenServicio::factory()->finalizada()->create();

        $this->iniciarSesion()->delete(route('ordenes.destroy', $orden))->assertForbidden();

        $this->assertModelExists($orden);
    }

    public function test_get_a_la_url_no_borra_nada(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        // GET /ordenes/{orden} es la ficha: leer nunca elimina.
        $this->iniciarSesion()->get(route('ordenes.destroy', $orden))->assertOk();

        $this->assertModelExists($orden);
    }

    public function test_la_ficha_ofrece_eliminar_con_confirmacion_solo_si_esta_pendiente(): void
    {
        $pendiente = OrdenServicio::factory()->pendiente()->create();
        $enProceso = OrdenServicio::factory()->enProceso()->create();

        $this->iniciarSesion()
            ->get(route('ordenes.show', $pendiente))
            ->assertSee('data-confirmar="¿Eliminar la orden #'.$pendiente->id.'?"', false)
            ->assertSee('onsubmit="return confirm(this.dataset.confirmar)"', false)
            ->assertSee('name="_method" value="DELETE"', false);

        $this->iniciarSesion()
            ->get(route('ordenes.show', $enProceso))
            ->assertDontSee('value="DELETE"', false);
    }
}
