<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Batería HTTP del registro de vehículos para la defensa. Simula el formulario con novalidate: el
// navegador no frena nada (ni required, ni min, ni accept) y cada dato incorrecto llega al
// servidor, que debe rechazarlo con su mensaje en español, sin guardar la fila ni el archivo.
class VehiculoValidacionHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    // Un registro correcto. Cada caso cambia un solo campo, así el único error esperado es el suyo.
    private function datosValidos(): array
    {
        return [
            'placa' => 'P830001',
            'marca' => 'Toyota',
            'modelo' => 'Hilux',
            'anio' => 2019,
            'color' => 'Blanco',
            'kilometraje' => 72000,
            'propietario_nombre' => 'Carmen Rivas',
            'propietario_telefono' => '7000-1234',
        ];
    }

    // Caso => [campo, valor enviado, mensaje esperado]. Los archivos van como funciones porque el
    // proveedor de datos corre antes de que arranque la aplicación. Las imágenes son PNG o GIF
    // porque algunas instalaciones de PHP traen GD sin soporte para JPEG.
    public static function casosInvalidos(): array
    {
        $anioMaximo = (int) date('Y') + 1;
        $mensajeAnio = "El año debe estar entre 1980 y {$anioMaximo}.";

        return [
            // Regla 1: placa obligatoria, P + 6 dígitos y única.
            'placa vacía' => ['placa', '', 'El campo placa es obligatorio.'],
            'placa ABC123' => ['placa', 'ABC123', 'La placa debe tener el formato P123456.'],
            'placa P12345 (5 dígitos)' => ['placa', 'P12345', 'La placa debe tener el formato P123456.'],
            'placa P1234567 (7 dígitos)' => ['placa', 'P1234567', 'La placa debe tener el formato P123456.'],
            'placa con espacio en medio' => ['placa', 'P 123456', 'La placa debe tener el formato P123456.'],
            'placa duplicada' => ['placa', 'P000777', 'Ya hay un vehículo registrado con esa placa.'],
            'placa duplicada en minúsculas' => ['placa', ' p000777 ', 'Ya hay un vehículo registrado con esa placa.'],

            // Regla 2: año entre 1980 y el año actual + 1.
            'año 1979' => ['anio', 1979, $mensajeAnio],
            'año actual + 2' => ['anio', $anioMaximo + 1, $mensajeAnio],
            'año con letras' => ['anio', 'dos mil', 'El campo año debe ser un número entero.'],
            'año con decimales' => ['anio', '2020.5', 'El campo año debe ser un número entero.'],

            // Kilometraje: entero de 0 a 999 999.
            'kilometraje negativo' => ['kilometraje', -1, 'El kilometraje no puede ser negativo.'],
            'kilometraje con letras' => ['kilometraje', 'mucho', 'El campo kilometraje debe ser un número entero.'],
            'kilometraje de un millón' => ['kilometraje', 1000000, 'El kilometraje no puede ser mayor que 999 999 km.'],

            // Teléfono con formato 0000-0000.
            'teléfono 70001234 sin guion' => ['propietario_telefono', '70001234', 'El teléfono debe tener el formato 0000-0000.'],
            'teléfono con letras' => ['propietario_telefono', '7000-ABCD', 'El teléfono debe tener el formato 0000-0000.'],
            'teléfono vacío' => ['propietario_telefono', '', 'El campo teléfono del propietario es obligatorio.'],

            // Textos obligatorios y largos máximos.
            'marca vacía' => ['marca', '', 'El campo marca es obligatorio.'],
            'modelo de 51 caracteres' => ['modelo', str_repeat('a', 51), 'El modelo no puede tener más de 50 caracteres.'],
            'color de 31 caracteres' => ['color', str_repeat('a', 31), 'El color no puede tener más de 30 caracteres.'],
            'propietario vacío' => ['propietario_nombre', '', 'El campo nombre del propietario es obligatorio.'],
            'propietario de 101 caracteres' => ['propietario_nombre', str_repeat('a', 101), 'El nombre del propietario no puede tener más de 100 caracteres.'],

            // Imagen opcional: solo JPG, PNG o WEBP de hasta 2 MB.
            'imagen PDF' => ['imagen', fn () => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'), 'El archivo debe ser una imagen.'],
            'imagen de 3 MB' => ['imagen', fn () => UploadedFile::fake()->image('grande.png')->size(3072), 'La imagen no puede pesar más de 2 MB.'],
            'imagen GIF' => ['imagen', fn () => UploadedFile::fake()->image('animada.gif'), 'La imagen debe ser JPG, PNG o WEBP.'],
        ];
    }

    #[DataProvider('casosInvalidos')]
    public function test_rechaza_el_dato_invalido_con_su_mensaje(string $campo, mixed $valor, string $mensaje): void
    {
        Vehiculo::factory()->create(['placa' => 'P000777']);
        $valor = $valor instanceof \Closure ? $valor() : $valor;

        $respuesta = $this->iniciarSesion()->from(route('vehiculos.create'))
            ->post(route('vehiculos.store'), [$campo => $valor] + $this->datosValidos());

        $respuesta->assertRedirect(route('vehiculos.create'))
            ->assertSessionHasErrors([$campo => $mensaje]);

        // Solo falla el campo del caso: el resto de los datos es válido.
        $this->assertSame([$campo], session('errors')->keys());

        // No se guardó nada: solo existe el vehículo que se creó para la placa duplicada.
        $this->assertSame(1, Vehiculo::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_los_datos_validos_de_la_bateria_si_se_registran(): void
    {
        // Comprueba la base de todos los casos: sin el dato incorrecto, el registro pasa.
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datosValidos())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehiculos', ['placa' => 'P830001']);
    }

    public function test_los_limites_exactos_son_validos(): void
    {
        $anioMaximo = now()->year + 1;

        foreach ([
            ['placa' => 'P830002', 'anio' => 1980, 'kilometraje' => 0],
            ['placa' => 'p830003', 'anio' => $anioMaximo, 'kilometraje' => 999999],
        ] as $datos) {
            $this->iniciarSesion()->post(route('vehiculos.store'), $datos + $this->datosValidos())
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseHas('vehiculos', ['placa' => 'P830002', 'anio' => 1980, 'kilometraje' => 0]);
        $this->assertDatabaseHas('vehiculos', ['placa' => 'P830003', 'anio' => $anioMaximo, 'kilometraje' => 999999]);
    }

    public function test_un_envio_con_varios_errores_conserva_lo_escrito_con_old(): void
    {
        $this->iniciarSesion()->from(route('vehiculos.create'))
            ->post(route('vehiculos.store'), ['placa' => 'ABC123', 'anio' => 1979] + $this->datosValidos())
            ->assertRedirect(route('vehiculos.create'))
            ->assertSessionHasErrors(['placa', 'anio'])
            ->assertSessionHasInput('marca', 'Toyota')
            ->assertSessionHasInput('propietario_nombre', 'Carmen Rivas')
            // También se devuelven los campos con error, para que el usuario corrija sobre lo escrito.
            ->assertSessionHasInput('placa', 'ABC123');

        $this->assertSame(0, Vehiculo::count());
    }

    public function test_un_campo_enviado_como_arreglo_se_rechaza_sin_error_500(): void
    {
        // Alguien edita el HTML y manda marca[]=x: la regla string lo rechaza con un 302, no un 500.
        $this->iniciarSesion()->from(route('vehiculos.create'))
            ->post(route('vehiculos.store'), ['marca' => ['Toyota']] + $this->datosValidos())
            ->assertRedirect(route('vehiculos.create'))
            ->assertSessionHasErrors(['marca' => 'El campo marca debe ser texto.']);

        $this->assertSame(0, Vehiculo::count());
    }
}
