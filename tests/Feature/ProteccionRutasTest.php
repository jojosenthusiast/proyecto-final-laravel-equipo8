<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProteccionRutasTest extends TestCase
{
    use RefreshDatabase;

    public static function rutasEscritura(): array
    {
        $rutas = [];

        foreach (['vehiculos', 'ordenes', 'servicios'] as $modulo) {
            $rutas[$modulo . ' crear'] = [
                'GET', "/$modulo/create"
            ];

            $rutas[$modulo . ' guardar'] = [
                'POST', "/$modulo"
            ];

            $rutas[$modulo . ' editar'] = [
                'GET', "/$modulo/1/edit"
            ];

            $rutas[$modulo . ' actualizar'] = [
                'PUT', "/$modulo/1"
            ];

            $rutas[$modulo . ' eliminar'] = [
                'DELETE', "/$modulo/1"
            ];
        }

        $rutas['ordenes cambiar estado'] = [
            'PATCH', '/ordenes/1/estado'
        ];

        return $rutas;
    }

    #[DataProvider('rutasEscritura')]
    public function test_invitado_no_puede_escribir(
        string $metodo,
        string $ruta
    ): void {
        $this->call($metodo, $ruta)
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    #[DataProvider('rutasEscritura')]
    public function test_usuario_autenticado_supera_middleware(
        string $metodo,
        string $ruta
    ): void {
        $this->iniciarSesion();

        $response = $this->call($metodo, $ruta);

        $this->assertNotEquals(
            route('login'),
            $response->headers->get('Location')
        );

        $this->assertNotContains(
            $response->getStatusCode(),
            [401, 500]
        );
    }

    public static function rutasLectura(): array
    {
        return [
            ['/', true],
            ['/vehiculos', true],
            ['/vehiculos/1', false],
            ['/ordenes', false],
            ['/ordenes/1', false],
            ['/servicios', false],
            ['/servicios/1', false],
            ['/reportes', true],
            ['/reportes/historial', false],
            ['/reportes/exportar', false],
        ];
    }

    #[DataProvider('rutasLectura')]
    public function test_lectura_es_publica(
        string $ruta,
        bool $implementada
    ): void {
        $response = $this->get($ruta);

        $this->assertNotEquals(
            route('login'),
            $response->headers->get('Location')
        );

        $this->assertNotContains(
            $response->getStatusCode(),
            [401, 403, 500]
        );

        if ($implementada) {
            $response->assertOk();
        }

        $this->assertGuest();
    }
}
