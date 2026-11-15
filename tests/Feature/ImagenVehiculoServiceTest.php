<?php

namespace Tests\Feature;

use App\Services\ImagenVehiculoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
}
