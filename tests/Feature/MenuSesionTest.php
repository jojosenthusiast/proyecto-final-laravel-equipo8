<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuSesionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_ve_opcion_entrar(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Entrar')
            ->assertDontSee('Salir');
    }

    public function test_usuario_autenticado_ve_opcion_salir(): void
    {
        $this->iniciarSesion();

        $this->get('/')
            ->assertOk()
            ->assertSee('Salir')
            ->assertSee('Cambiar contraseña')
            ->assertDontSee('Entrar');
    }

    public function test_formulario_logout_contiene_csrf(): void
    {
        $this->iniciarSesion();

        $this->get('/')
            ->assertOk()
            ->assertSee(
                'action="' . route('logout') . '"',
                false
            )
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false);
    }
}
