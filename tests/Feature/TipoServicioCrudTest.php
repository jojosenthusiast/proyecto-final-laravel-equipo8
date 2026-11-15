<?php

namespace Tests\Feature;

use App\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// CRUD del catálogo de servicios: registrar y editar con validación completa.
class TipoServicioCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_no_puede_entrar_al_formulario_de_registro(): void
    {
        $this->get(route('servicios.create'))->assertRedirect(route('login'));
    }

    public function test_un_usuario_autenticado_ve_el_formulario_de_registro(): void
    {
        $this->iniciarSesion()
            ->get(route('servicios.create'))
            ->assertOk()
            ->assertSee('Registrar servicio');
    }

    public function test_se_registra_un_servicio_valido(): void
    {
        $this->iniciarSesion()
            ->post(route('servicios.store'), [
                'nombre' => 'Cambio de aceite sintético',
                'precio_base' => 45.50,
                'descripcion' => 'Incluye filtro y revisión de niveles.',
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('tipos_servicio', [
            'nombre' => 'Cambio de aceite sintético',
            'precio_base' => 45.50,
        ]);
    }

    public function test_no_se_registra_un_nombre_duplicado(): void
    {
        TipoServicio::factory()->create(['nombre' => 'Frenos']);

        $this->iniciarSesion()
            ->post(route('servicios.store'), [
                'nombre' => 'Frenos',
                'precio_base' => 30,
            ])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('tipos_servicio', 1);
    }

    public function test_no_se_registra_un_precio_cero(): void
    {
        $this->iniciarSesion()
            ->post(route('servicios.store'), [
                'nombre' => 'Alineación',
                'precio_base' => 0,
            ])
            ->assertSessionHasErrors('precio_base');

        $this->assertDatabaseCount('tipos_servicio', 0);
    }

    public function test_el_formulario_de_edicion_carga_el_servicio(): void
    {
        $servicio = TipoServicio::factory()->create(['nombre' => 'Balanceo']);

        $this->iniciarSesion()
            ->get(route('servicios.edit', $servicio))
            ->assertOk()
            ->assertSee('Balanceo');
    }

    public function test_se_actualiza_un_servicio(): void
    {
        $servicio = TipoServicio::factory()->create([
            'nombre' => 'Diagnóstico',
            'precio_base' => 20,
        ]);

        $this->iniciarSesion()
            ->put(route('servicios.update', $servicio), [
                'nombre' => 'Diagnóstico computarizado',
                'precio_base' => 35.75,
                'descripcion' => 'Escaneo completo.',
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('tipos_servicio', [
            'id' => $servicio->id,
            'nombre' => 'Diagnóstico computarizado',
            'precio_base' => 35.75,
        ]);
    }

    public function test_se_puede_guardar_la_edicion_sin_cambiar_el_nombre(): void
    {
        $servicio = TipoServicio::factory()->create([
            'nombre' => 'Suspensión',
            'precio_base' => 80,
        ]);

        $this->iniciarSesion()
            ->put(route('servicios.update', $servicio), [
                'nombre' => 'Suspensión',
                'precio_base' => 95,
            ])
            ->assertRedirect(route('servicios.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tipos_servicio', [
            'id' => $servicio->id,
            'precio_base' => 95,
        ]);
    }
}
