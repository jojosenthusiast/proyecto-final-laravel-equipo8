<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AlertasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_mensaje_flash_se_anuncia_y_conserva_el_texto_escapado(): void
    {
        Route::middleware('web')->get('/prueba-interfaz-alerta', fn () => redirect()->route('login')->with('error', 'Falló <el envío>'));

        $this->followingRedirects()->get('/prueba-interfaz-alerta')
            ->assertOk()
            ->assertSee('role="alert">Falló &lt;el envío&gt;', false)
            ->assertSee('data-cerrar-alerta', false);
    }

    public function test_el_resumen_enlaza_el_error_con_su_campo(): void
    {
        $this->from(route('login'))->followingRedirects()
            ->post(route('login'), ['password' => 'cualquiera'])
            ->assertOk()
            ->assertSee('href="#email"', false);
    }
}
