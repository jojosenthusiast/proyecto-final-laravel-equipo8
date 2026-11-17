<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// El estado avanza pendiente -> en proceso -> finalizada, sin saltos ni retrocesos. Al finalizar
// se registra la fecha de entrega respetando la regla 4.
class OrdenEstadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cambiar_estado_requiere_sesion(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->patch(route('ordenes.estado', $orden), ['estado' => 'en_proceso'])
            ->assertRedirect(route('login'));

        $this->assertSame('pendiente', $orden->fresh()->estado);
    }

    public function test_pendiente_pasa_a_en_proceso(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->iniciarSesion()
            ->patch(route('ordenes.estado', $orden), ['estado' => 'en_proceso'])
            ->assertRedirect(route('ordenes.show', $orden))
            ->assertSessionHas('exito', 'La orden pasó a En proceso.');

        $this->assertSame('en_proceso', $orden->fresh()->estado);
        $this->assertNull($orden->fresh()->fecha_entrega);
    }

    public function test_en_proceso_a_finalizada_pone_la_fecha_de_entrega(): void
    {
        $orden = OrdenServicio::factory()->enProceso()->create(['fecha_entrega' => null]);

        $this->iniciarSesion()
            ->patch(route('ordenes.estado', $orden), ['estado' => 'finalizada'])
            ->assertRedirect(route('ordenes.show', $orden))
            ->assertSessionHas('exito', 'La orden pasó a Finalizada.');

        $orden->refresh();
        $this->assertSame('finalizada', $orden->estado);
        $this->assertSame(today()->toDateString(), $orden->fecha_entrega->toDateString());
    }

    public function test_al_finalizar_conserva_una_fecha_de_entrega_valida(): void
    {
        $orden = OrdenServicio::factory()->enProceso()->create([
            'fecha_ingreso' => now()->subDays(10)->toDateString(),
            'fecha_entrega' => now()->subDays(3)->toDateString(),
        ]);

        $this->iniciarSesion()
            ->patch(route('ordenes.estado', $orden), ['estado' => 'finalizada'])
            ->assertSessionHasNoErrors();

        $this->assertSame(now()->subDays(3)->toDateString(), $orden->fresh()->fecha_entrega->toDateString());
    }

    public function test_no_finaliza_si_la_entrega_guardada_es_anterior_al_ingreso(): void
    {
        // Dato heredado incoherente: la regla 4 se vuelve a comprobar al cerrar la orden.
        $orden = OrdenServicio::factory()->enProceso()->create([
            'fecha_ingreso' => now()->subDays(2)->toDateString(),
            'fecha_entrega' => now()->subDays(5)->toDateString(),
        ]);

        $this->iniciarSesion()
            ->patch(route('ordenes.estado', $orden), ['estado' => 'finalizada'])
            ->assertSessionHasErrors([
                'estado' => 'La fecha de entrega no puede ser anterior a la fecha de ingreso.',
            ]);

        $this->assertSame('en_proceso', $orden->fresh()->estado);
    }

    public function test_pendiente_a_finalizada_directo_se_rechaza(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->iniciarSesion()
            ->patch(route('ordenes.estado', $orden), ['estado' => 'finalizada'])
            ->assertSessionHasErrors([
                'estado' => 'La orden solo puede pasar a En proceso.',
            ]);

        $this->assertSame('pendiente', $orden->fresh()->estado);
    }

    public function test_finalizada_a_pendiente_se_rechaza(): void
    {
        $orden = OrdenServicio::factory()->finalizada()->create();

        $this->iniciarSesion()
            ->patch(route('ordenes.estado', $orden), ['estado' => 'pendiente'])
            ->assertSessionHasErrors([
                'estado' => 'Esta orden está finalizada y ya no cambia de estado.',
            ]);

        $this->assertSame('finalizada', $orden->fresh()->estado);
    }

    public function test_un_estado_inventado_se_rechaza(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create();

        $this->iniciarSesion()
            ->patch(route('ordenes.estado', $orden), ['estado' => 'cancelada'])
            ->assertSessionHasErrors('estado');

        $this->assertSame('pendiente', $orden->fresh()->estado);
    }

    public function test_la_ficha_ofrece_solo_el_siguiente_estado(): void
    {
        $pendiente = OrdenServicio::factory()->pendiente()->create();
        $finalizada = OrdenServicio::factory()->finalizada()->create();

        $this->iniciarSesion()
            ->get(route('ordenes.show', $pendiente))
            ->assertSee(route('ordenes.estado', $pendiente), false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('value="en_proceso"', false)
            ->assertSee('Pasar a En proceso');

        $this->iniciarSesion()
            ->get(route('ordenes.show', $finalizada))
            ->assertDontSee(route('ordenes.estado', $finalizada), false);
    }
}
