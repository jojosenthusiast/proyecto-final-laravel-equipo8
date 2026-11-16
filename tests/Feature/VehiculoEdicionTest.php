<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

// Edición del vehículo: datos precargados, foto actual visible y reemplazo de la foto que no deja
// archivos huérfanos ni borra la anterior si el guardado falla. Las fotos de prueba son PNG
// porque algunas instalaciones de PHP traen GD sin soporte para JPEG.
class VehiculoEdicionTest extends TestCase
{
    use RefreshDatabase;

    private const FOTO_ANTERIOR = 'vehiculos/anterior.png';

    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put(self::FOTO_ANTERIOR, 'foto anterior');

        $this->vehiculo = Vehiculo::factory()->create([
            'placa' => 'P810001',
            'marca' => 'Mazda',
            'modelo' => 'CX-5',
            'color' => 'Rojo',
            'kilometraje' => 30000,
            'propietario_telefono' => '2222-3333',
            'imagen' => self::FOTO_ANTERIOR,
        ]);
    }

    // Los datos actuales del vehículo con los cambios indicados, como los enviaría el formulario.
    private function datos(array $cambios = []): array
    {
        return $cambios + $this->vehiculo->only([
            'placa', 'marca', 'modelo', 'anio', 'color', 'kilometraje',
            'propietario_nombre', 'propietario_telefono',
        ]);
    }

    private function actualizar(array $cambios = [])
    {
        return $this->iniciarSesion()->from(route('vehiculos.edit', $this->vehiculo))
            ->put(route('vehiculos.update', $this->vehiculo), $this->datos($cambios));
    }

    public function test_el_formulario_viene_precargado_y_muestra_la_foto_actual(): void
    {
        $this->iniciarSesion()->get(route('vehiculos.edit', $this->vehiculo))->assertOk()
            ->assertSee('action="'.route('vehiculos.update', $this->vehiculo).'"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="P810001"', false)
            ->assertSee('value="CX-5"', false)
            ->assertSee('value="2222-3333"', false)
            ->assertSee('id="vista-imagen" src="'.asset('storage/'.self::FOTO_ANTERIOR).'"', false)
            ->assertSee('Si no eliges otra foto, se conserva la actual.')
            ->assertSee('Guardar cambios');
    }

    public function test_cambiar_solo_el_color_conserva_la_foto(): void
    {
        $this->actualizar(['color' => 'Azul'])
            ->assertRedirect(route('vehiculos.show', $this->vehiculo))
            ->assertSessionHas('exito', 'Vehículo actualizado correctamente.');

        $this->vehiculo->refresh();
        $this->assertSame('Azul', $this->vehiculo->color);
        $this->assertSame(self::FOTO_ANTERIOR, $this->vehiculo->imagen);
        Storage::disk('public')->assertExists(self::FOTO_ANTERIOR);
    }

    public function test_un_campo_de_imagen_vacio_no_borra_la_ruta_guardada(): void
    {
        // El navegador envía el campo file aunque no se elija nada; llega vacío y no debe
        // pisar la ruta de la foto actual con null.
        $this->actualizar(['imagen' => ''])->assertSessionHasNoErrors();

        $this->assertSame(self::FOTO_ANTERIOR, $this->vehiculo->fresh()->imagen);
    }

    public function test_subir_una_foto_nueva_la_guarda_y_borra_la_anterior(): void
    {
        $this->actualizar(['imagen' => UploadedFile::fake()->image('nueva.png')])
            ->assertSessionHasNoErrors();

        $nueva = $this->vehiculo->fresh()->imagen;

        $this->assertNotSame(self::FOTO_ANTERIOR, $nueva);
        Storage::disk('public')->assertExists($nueva);
        Storage::disk('public')->assertMissing(self::FOTO_ANTERIOR);
        $this->assertSame([$nueva], Storage::disk('public')->allFiles());
    }

    public function test_si_la_actualizacion_falla_se_borra_la_foto_nueva_y_queda_la_anterior(): void
    {
        // Se simula una falla de la base de datos justo al guardar la fila.
        Vehiculo::updating(fn () => throw new RuntimeException('Falla simulada al actualizar.'));

        $this->actualizar(['color' => 'Verde', 'imagen' => UploadedFile::fake()->image('nueva.png')])
            ->assertServerError();

        $this->vehiculo->refresh();
        $this->assertSame('Rojo', $this->vehiculo->color);
        $this->assertSame(self::FOTO_ANTERIOR, $this->vehiculo->imagen);
        $this->assertSame([self::FOTO_ANTERIOR], Storage::disk('public')->allFiles());
    }

    public function test_si_el_registro_falla_no_queda_la_foto_subida(): void
    {
        Vehiculo::creating(fn () => throw new RuntimeException('Falla simulada al registrar.'));

        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datos([
            'placa' => 'P810009',
            'imagen' => UploadedFile::fake()->image('nueva.png'),
        ]))->assertServerError();

        $this->assertDatabaseMissing('vehiculos', ['placa' => 'P810009']);
        $this->assertSame([self::FOTO_ANTERIOR], Storage::disk('public')->allFiles());
    }

    public function test_un_error_de_validacion_no_guarda_la_foto_ni_cambia_los_datos(): void
    {
        $this->actualizar(['anio' => 1979, 'color' => 'Negro', 'imagen' => UploadedFile::fake()->image('nueva.png')])
            ->assertRedirect(route('vehiculos.edit', $this->vehiculo))
            ->assertSessionHasErrors('anio');

        $this->assertSame('Rojo', $this->vehiculo->fresh()->color);
        $this->assertSame([self::FOTO_ANTERIOR], Storage::disk('public')->allFiles());
    }

    public function test_conservar_la_propia_placa_no_da_error_de_placa_repetida(): void
    {
        $this->actualizar(['placa' => 'p810001 '])->assertSessionHasNoErrors();

        $this->assertSame('P810001', $this->vehiculo->fresh()->placa);
    }

    public function test_usar_la_placa_de_otro_vehiculo_si_da_error(): void
    {
        Vehiculo::factory()->create(['placa' => 'P810002']);

        $this->actualizar(['placa' => 'P810002'])
            ->assertRedirect(route('vehiculos.edit', $this->vehiculo))
            ->assertSessionHasErrors(['placa' => 'Ya hay un vehículo registrado con esa placa.']);

        $this->assertSame('P810001', $this->vehiculo->fresh()->placa);
    }

    public function test_tras_editar_la_ficha_muestra_el_mensaje_y_los_datos_nuevos(): void
    {
        $this->iniciarSesion()->followingRedirects()
            ->put(route('vehiculos.update', $this->vehiculo), $this->datos(['modelo' => 'CX-30']))
            ->assertOk()
            ->assertSee('Vehículo actualizado correctamente.')
            ->assertSee('CX-30');
    }

    public function test_sin_sesion_no_se_puede_editar(): void
    {
        $this->get(route('vehiculos.edit', $this->vehiculo))->assertRedirect(route('login'));
        $this->put(route('vehiculos.update', $this->vehiculo), $this->datos(['color' => 'Azul']))
            ->assertRedirect(route('login'));

        $this->assertSame('Rojo', $this->vehiculo->fresh()->color);
    }
}
