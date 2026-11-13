<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Inicia sesión con un usuario nuevo. Todas las pruebas de escritura lo usan desde la Fase 2,
    // así proteger las rutas con auth (Ángel, A4) no rompe las pruebas de los demás módulos.
    protected function iniciarSesion(): static
    {
        return $this->actingAs(User::factory()->create());
    }
}
