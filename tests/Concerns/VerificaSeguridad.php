<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

trait VerificaSeguridad
{
    protected function assertFormularioConCsrf(
        TestResponse $response
    ): void {
        // [SEGURIDAD] Verifica la protección CSRF.
        $response->assertSee('name="_token"', false);
    }

    protected function assertHtmlEscapado(
        TestResponse $response,
        string $texto
    ): void {
        // [SEGURIDAD] Verifica la protección contra XSS.
        $response->assertSee(e($texto), false);

        $response->assertDontSee($texto, false);
    }
}
