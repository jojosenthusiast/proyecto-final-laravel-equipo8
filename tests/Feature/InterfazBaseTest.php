<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfazBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_carga_los_estilos_y_el_script_compartido(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('css/estilos.css', false)
            ->assertSee('js/biela.js', false);
    }

    public function test_el_formulario_exhibe_el_error_junto_al_campo(): void
    {
        $this->iniciarSesion()->from(route('vehiculos.create'))->followingRedirects()
            ->post(route('vehiculos.store'), [])
            ->assertOk()
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('id="placa-error"', false);
    }

    public function test_el_layout_incluye_un_dialogo_con_titulo_y_cancelacion(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<dialog id="confirmar-eliminacion" aria-labelledby="titulo-confirmacion"', false)
            ->assertSee('value="cancelar" autofocus', false);
    }

    public function test_el_formulario_y_el_script_acuerdan_el_selector_de_la_vista_previa(): void
    {
        $this->iniciarSesion()->get(route('vehiculos.create'))
            ->assertOk()
            ->assertSee('data-vista-previa="vista-imagen"', false)
            ->assertSee('id="vista-imagen"', false);

        $this->assertStringContainsString('[data-vista-previa]', file_get_contents(public_path('js/biela.js')));
    }
}
