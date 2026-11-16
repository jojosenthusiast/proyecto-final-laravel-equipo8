<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Registro de vehículos con foto: formulario, alta con y sin imagen, errores por campo y old().
// Las fotos de prueba son PNG: UploadedFile::fake()->image() las dibuja con GD, y algunas
// instalaciones de PHP traen GD sin soporte para JPEG.
class VehiculoRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El disco público se reemplaza por uno temporal: las pruebas no tocan storage/app/public.
        Storage::fake('public');
    }

    private function datosValidos(array $cambios = []): array
    {
        return $cambios + [
            'placa' => 'P800001',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio' => 2018,
            'color' => 'Gris',
            'kilometraje' => 65000,
            'propietario_nombre' => 'Ana Pérez',
            'propietario_telefono' => '7777-1234',
        ];
    }

    public function test_el_formulario_envia_archivos_y_sigue_el_contrato_de_marcado(): void
    {
        $this->iniciarSesion()->get(route('vehiculos.create'))->assertOk()
            ->assertSee('action="'.route('vehiculos.store').'"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('type="file" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp"', false)
            ->assertSee('data-vista-previa="vista-imagen"', false)
            ->assertSee('id="vista-imagen" src="'.asset('img/vehiculo-default.svg').'"', false)
            ->assertSee('<label for="placa">Placa</label>', false)
            ->assertSee('<label for="propietario_telefono">Teléfono del propietario</label>', false);
    }

    public function test_registrar_con_imagen_guarda_el_archivo_y_la_ruta(): void
    {
        $respuesta = $this->iniciarSesion()->post(route('vehiculos.store'), $this->datosValidos([
            'imagen' => UploadedFile::fake()->image('auto.png', 640, 480),
        ]));

        $vehiculo = Vehiculo::where('placa', 'P800001')->firstOrFail();

        $respuesta->assertRedirect(route('vehiculos.show', $vehiculo))
            ->assertSessionHas('exito', 'Vehículo registrado correctamente.');

        // La fila guarda la ruta relativa dentro de vehiculos/ y el archivo existe en el disco.
        $this->assertStringStartsWith('vehiculos/', $vehiculo->imagen);
        Storage::disk('public')->assertExists($vehiculo->imagen);
        $this->assertSame(asset('storage/'.$vehiculo->imagen), $vehiculo->imagen_url);
    }

    public function test_registrar_sin_imagen_usa_la_foto_por_defecto(): void
    {
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datosValidos())
            ->assertSessionHasNoErrors();

        $vehiculo = Vehiculo::where('placa', 'P800001')->firstOrFail();

        $this->assertNull($vehiculo->imagen);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->get(route('vehiculos.show', $vehiculo))
            ->assertSee(asset('img/vehiculo-default.svg'), false);
    }

    public function test_tras_registrar_la_ficha_muestra_el_mensaje_de_exito(): void
    {
        $this->iniciarSesion()->followingRedirects()
            ->post(route('vehiculos.store'), $this->datosValidos())
            ->assertOk()
            ->assertSee('Vehículo registrado correctamente.')
            ->assertSee('P800001');
    }

    public function test_la_placa_se_guarda_en_mayusculas_y_sin_espacios(): void
    {
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datosValidos(['placa' => ' p800002 ']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vehiculos', ['placa' => 'P800002']);
    }

    public function test_un_envio_vacio_marca_error_en_cada_campo_obligatorio(): void
    {
        // Simula un formulario con novalidate: el navegador no frena nada y todo llega vacío.
        $this->iniciarSesion()->from(route('vehiculos.create'))
            ->post(route('vehiculos.store'), [])
            ->assertRedirect(route('vehiculos.create'))
            ->assertSessionHasErrors([
                'placa' => 'El campo placa es obligatorio.',
                'marca' => 'El campo marca es obligatorio.',
                'modelo' => 'El campo modelo es obligatorio.',
                'anio' => 'El campo año es obligatorio.',
                'color' => 'El campo color es obligatorio.',
                'kilometraje' => 'El campo kilometraje es obligatorio.',
                'propietario_nombre' => 'El campo nombre del propietario es obligatorio.',
                'propietario_telefono' => 'El campo teléfono del propietario es obligatorio.',
            ])
            ->assertSessionDoesntHaveErrors('imagen');

        $this->assertSame(0, Vehiculo::count());
    }

    public function test_los_errores_aparecen_junto_a_su_campo_y_old_conserva_lo_escrito(): void
    {
        $this->iniciarSesion()->from(route('vehiculos.create'))->followingRedirects()
            ->post(route('vehiculos.store'), $this->datosValidos(['placa' => 'X123', 'propietario_telefono' => '77771234']))
            ->assertOk()
            ->assertSee('aria-invalid="true" aria-describedby="placa-error"', false)
            ->assertSee('<p class="error-campo" id="placa-error">La placa debe tener el formato P123456.</p>', false)
            ->assertSee('El teléfono debe tener el formato 0000-0000.')
            ->assertSee('value="X123"', false)
            ->assertSee('value="Toyota"', false)
            ->assertSee('value="Ana Pérez"', false);
    }

    public function test_un_archivo_que_no_es_imagen_se_rechaza_sin_guardar_nada(): void
    {
        $this->iniciarSesion()->from(route('vehiculos.create'))
            ->post(route('vehiculos.store'), $this->datosValidos([
                'imagen' => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
            ]))
            ->assertRedirect(route('vehiculos.create'))
            ->assertSessionHasErrors('imagen');

        $this->assertSame(0, Vehiculo::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_una_imagen_de_mas_de_2_mb_se_rechaza(): void
    {
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datosValidos([
            'imagen' => UploadedFile::fake()->image('grande.png')->size(2049),
        ]))->assertSessionHasErrors(['imagen' => 'La imagen no puede pesar más de 2 MB.']);

        $this->assertSame(0, Vehiculo::count());
    }

    public function test_sin_sesion_no_se_puede_registrar(): void
    {
        $this->get(route('vehiculos.create'))->assertRedirect(route('login'));
        $this->post(route('vehiculos.store'), $this->datosValidos())->assertRedirect(route('login'));

        $this->assertSame(0, Vehiculo::count());
    }

    public function test_el_listado_ofrece_registrar_solo_con_sesion(): void
    {
        $this->get(route('vehiculos.index'))->assertOk()
            ->assertDontSee(route('vehiculos.create'), false);

        $this->iniciarSesion()->get(route('vehiculos.index'))->assertOk()
            ->assertSee(route('vehiculos.create'), false)
            ->assertSee('Registrar vehículo');
    }
}
