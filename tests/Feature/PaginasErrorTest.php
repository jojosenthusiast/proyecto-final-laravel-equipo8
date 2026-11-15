<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaginasErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Route::middleware('web')->get(
            '/prueba-error-403',
            function () {
                abort(
                    403,
                    'Esta orden está finalizada y no puede editarse.'
                );
            }
        );

        Route::middleware('web')->get(
            '/prueba-error-419',
            function () {
                abort(419);
            }
        );

        Route::middleware('web')->get(
            '/prueba-error-429',
            function () {
                abort(429);
            }
        );
    }

    public function test_muestra_pagina_personalizada_403(): void
    {
        $this->get('/prueba-error-403')
            ->assertStatus(403)
            ->assertSee('Acceso denegado')
            ->assertSee(
                'Esta orden está finalizada y no puede editarse.'
            )
            ->assertSee('Volver al inicio');
    }

    public function test_muestra_pagina_personalizada_419(): void
    {
        $this->get('/prueba-error-419')
            ->assertStatus(419)
            ->assertSee('Sesión expirada')
            ->assertSee('recarga la página')
            ->assertSee('Volver al inicio');
    }

    public function test_muestra_pagina_personalizada_429(): void
    {
        $this->get('/prueba-error-429')
            ->assertStatus(429)
            ->assertSee('Demasiados intentos')
            ->assertSee('Espera unos segundos')
            ->assertSee('Volver al inicio');
    }
}
