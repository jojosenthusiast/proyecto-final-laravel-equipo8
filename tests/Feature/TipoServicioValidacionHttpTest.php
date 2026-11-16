<?php

namespace Tests\Feature;

use App\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Batería HTTP del catálogo: simula un navegador sin validación HTML (novalidate) y comprueba
// que el servidor rechaza cada dato inválido con su mensaje en español.
class TipoServicioValidacionHttpTest extends TestCase
{
    use RefreshDatabase;

    public static function casosInvalidos(): array
    {
        return [
            'nombre vacío' => [
                'nombre', ['nombre' => ''], 'El nombre del servicio es obligatorio.', false,
            ],
            'nombre en blanco' => [
                'nombre', ['nombre' => '   '], 'El nombre del servicio es obligatorio.', false,
            ],
            'nombre demasiado largo' => [
                'nombre', ['nombre' => str_repeat('a', 61)], 'El nombre no puede tener más de 60 caracteres.', false,
            ],
            'nombre duplicado' => [
                'nombre', ['nombre' => 'Frenos'], 'Ya existe un servicio con ese nombre.', true,
            ],
            'precio vacío' => [
                'precio_base', ['precio_base' => ''], 'El precio base es obligatorio.', false,
            ],
            'precio cero' => [
                'precio_base', ['precio_base' => 0], 'El precio base debe ser mayor que cero.', false,
            ],
            'precio negativo' => [
                'precio_base', ['precio_base' => -10], 'El precio base debe ser mayor que cero.', false,
            ],
            'precio con letras' => [
                'precio_base', ['precio_base' => 'abc'], 'El precio base debe ser un número.', false,
            ],
            'precio demasiado alto' => [
                'precio_base', ['precio_base' => 100000], 'El precio base no puede superar los 99999.99.', false,
            ],
            'descripción demasiado larga' => [
                'descripcion', ['descripcion' => str_repeat('a', 256)], 'La descripción no puede tener más de 255 caracteres.', false,
            ],
        ];
    }

    #[DataProvider('casosInvalidos')]
    public function test_rechaza_datos_invalidos(
        string $campo,
        array $datos,
        string $mensaje,
        bool $duplicado
    ): void {
        if ($duplicado) {
            TipoServicio::factory()->create(['nombre' => $datos['nombre']]);
        }

        $respuesta = $this->iniciarSesion()->post(route('servicios.store'), array_merge([
            'nombre' => 'Alineación',
            'precio_base' => 25,
            'descripcion' => 'Servicio de prueba.',
        ], $datos));

        $respuesta->assertSessionHasErrors([$campo]);

        $this->assertSame($mensaje, session('errors')->first($campo));
    }

    public function test_conserva_los_datos_validos_cuando_otro_campo_falla(): void
    {
        $this->iniciarSesion()
            ->post(route('servicios.store'), [
                'nombre' => 'Alineación',
                'precio_base' => 0,
                'descripcion' => 'Texto que debe conservarse.',
            ])
            ->assertSessionHasInput('nombre', 'Alineación')
            ->assertSessionHasInput('descripcion', 'Texto que debe conservarse.');
    }
}
