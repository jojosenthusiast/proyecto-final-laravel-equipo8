<?php

namespace Tests\Feature;

use App\Services\ImagenVehiculoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

// El servicio de imágenes es la pieza que Jorge usa en J4 y J5: guarda, reemplaza y borra
// archivos del disco público sin tocar la base de datos.
class ImagenVehiculoServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImagenVehiculoService $imagenes;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->imagenes = app(ImagenVehiculoService::class);
    }

    public function test_guardar_crea_el_archivo_y_devuelve_su_ruta_relativa(): void
    {
        $archivo = UploadedFile::fake()->image('auto.jpg', 640, 480);

        $ruta = $this->imagenes->guardar($archivo);

        $this->assertStringStartsWith('vehiculos/', $ruta);
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_reemplazar_crea_la_nueva_y_borra_la_anterior(): void
    {
        Storage::disk('public')->put('vehiculos/anterior.jpg', 'foto anterior');

        $ruta = $this->imagenes->reemplazar(
            'vehiculos/anterior.jpg',
            UploadedFile::fake()->image('nueva.jpg', 640, 480)
        );

        Storage::disk('public')->assertExists($ruta);
        Storage::disk('public')->assertMissing('vehiculos/anterior.jpg');
    }

    public function test_eliminar_borra_el_archivo_indicado(): void
    {
        Storage::disk('public')->put('vehiculos/borrar.jpg', 'foto');

        $this->imagenes->eliminar('vehiculos/borrar.jpg');

        Storage::disk('public')->assertMissing('vehiculos/borrar.jpg');
    }

    public function test_eliminar_con_null_no_lanza_error(): void
    {
        $this->imagenes->eliminar(null);

        $this->assertSame([], Storage::disk('public')->allFiles('vehiculos'));
    }

    public function test_eliminar_un_archivo_que_ya_no_existe_no_lanza_error(): void
    {
        $this->imagenes->eliminar('vehiculos/inexistente.jpg');

        $this->assertSame([], Storage::disk('public')->allFiles('vehiculos'));
    }

    public function test_con_imagen_sin_archivo_ejecuta_la_operacion_con_null(): void
    {
        $resultado = $this->imagenes->conImagen(
            null,
            fn (?string $ruta) => $ruta === null ? 'sin foto' : 'con foto'
        );

        $this->assertSame('sin foto', $resultado);
        $this->assertSame([], Storage::disk('public')->allFiles('vehiculos'));
    }

    public function test_con_imagen_guarda_la_foto_y_borra_la_anterior_solo_al_terminar(): void
    {
        Storage::disk('public')->put('vehiculos/anterior.jpg', 'foto anterior');

        $ruta = $this->imagenes->conImagen(
            UploadedFile::fake()->image('nueva.jpg', 640, 480),
            fn (string $rutaNueva) => $rutaNueva,
            'vehiculos/anterior.jpg'
        );

        Storage::disk('public')->assertExists($ruta);
        Storage::disk('public')->assertMissing('vehiculos/anterior.jpg');
    }

    public function test_con_imagen_borra_la_foto_nueva_si_la_operacion_falla(): void
    {
        $this->expectException(RuntimeException::class);

        try {
            $this->imagenes->conImagen(
                UploadedFile::fake()->image('nueva.jpg', 640, 480),
                function (): void {
                    throw new RuntimeException('Falló el guardado en la base.');
                }
            );
        } finally {
            $this->assertSame([], Storage::disk('public')->allFiles('vehiculos'));
        }
    }
}
