<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_menu_conecta_el_control_movil_con_la_navegacion_y_mantiene_los_enlaces(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('aria-controls="menu-principal"', false)
            ->assertSee('id="menu-principal"', false)
            ->assertSee(route('vehiculos.index'))
            ->assertSee(route('reportes.index'))
            ->assertSee('Entrar');
    }
}
