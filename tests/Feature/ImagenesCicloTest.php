<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Criterio 5 de la rúbrica (manejo de imágenes) de punta a punta por HTTP: tipos aceptados y
// rechazados, tamaño máximo, reemplazo, eliminación, imagen por defecto y URL pública.
class ImagenesCicloTest extends TestCase
{
    use RefreshDatabase;

    // Imágenes reales de 1×1 px escritas en bytes. UploadedFile::fake()->image() necesita que GD
    // sepa generar cada formato, y algunas instalaciones de PHP no traen JPEG ni WEBP; con los
    // bytes, la prueba no depende de GD y Laravel las reconoce por su contenido.
    private const JPG = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

    private const WEBP = 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function foto(string $formato, string $nombre = 'auto'): UploadedFile
    {
        return match ($formato) {
            'jpg' => $this->archivoReal("{$nombre}.jpg", base64_decode(self::JPG)),
            'webp' => $this->archivoReal("{$nombre}.webp", base64_decode(self::WEBP)),
            'png' => UploadedFile::fake()->image("{$nombre}.png", 320, 240),
        };
    }

    // Un archivo subido como lo arma Laravel en una petición real. A diferencia de
    // UploadedFile::fake(), que deduce el tipo por la extensión del nombre, este lo detecta por el
    // contenido (finfo), igual que con un archivo enviado desde el navegador.
    private function archivoReal(string $nombre, string $contenido): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'biela');
        file_put_contents($ruta, $contenido);
        $this->temporales[] = $ruta;

        return new UploadedFile($ruta, $nombre, null, null, true);
    }

    private array $temporales = [];

    protected function tearDown(): void
    {
        array_map(fn (string $ruta) => is_file($ruta) && unlink($ruta), $this->temporales);

        parent::tearDown();
    }

    private function datos(array $cambios = []): array
    {
        return $cambios + [
            'placa' => 'P840001',
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'anio' => 2021,
            'color' => 'Azul',
            'kilometraje' => 15000,
            'propietario_nombre' => 'Rosa Méndez',
            'propietario_telefono' => '7123-4567',
        ];
    }

    private function registrar(?UploadedFile $foto = null): Vehiculo
    {
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datos($foto ? ['imagen' => $foto] : []))
            ->assertSessionHasNoErrors();

        return Vehiculo::where('placa', 'P840001')->firstOrFail();
    }

    private function editar(Vehiculo $vehiculo, array $cambios = [])
    {
        return $this->iniciarSesion()
            ->put(route('vehiculos.update', $vehiculo), $this->datos($cambios));
    }

    private function archivos(): array
    {
        return Storage::disk('public')->allFiles();
    }

    public function test_tipo_acepta_una_foto_jpg(): void
    {
        $vehiculo = $this->registrar($this->foto('jpg'));

        $this->assertStringEndsWith('.jpg', $vehiculo->imagen);
        Storage::disk('public')->assertExists($vehiculo->imagen);
    }

    public function test_tipo_acepta_una_foto_png(): void
    {
        $vehiculo = $this->registrar($this->foto('png'));

        $this->assertStringEndsWith('.png', $vehiculo->imagen);
        Storage::disk('public')->assertExists($vehiculo->imagen);
    }

    public function test_tipo_acepta_una_foto_webp(): void
    {
        $vehiculo = $this->registrar($this->foto('webp'));

        $this->assertStringEndsWith('.webp', $vehiculo->imagen);
        Storage::disk('public')->assertExists($vehiculo->imagen);
    }

    public function test_tipo_rechaza_un_pdf_con_mensaje_en_espanol(): void
    {
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datos([
            'imagen' => UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors(['imagen' => 'El archivo debe ser una imagen.']);

        $this->assertSame(0, Vehiculo::count());
        $this->assertSame([], $this->archivos());
    }

    public function test_tipo_rechaza_un_pdf_renombrado_como_png(): void
    {
        // [SEGURIDAD] Laravel revisa el contenido del archivo, no la extensión que trae el nombre:
        // un PDF llamado foto.png sigue siendo un PDF y se rechaza.
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datos([
            'imagen' => $this->archivoReal('foto.png', "%PDF-1.4\n%falso\n"),
        ]))->assertSessionHasErrors(['imagen' => 'El archivo debe ser una imagen.']);

        $this->assertSame(0, Vehiculo::count());

        $this->assertSame([], $this->archivos());
    }

    public function test_tamano_rechaza_una_foto_de_3_mb_y_acepta_una_de_2_mb(): void
    {
        $this->iniciarSesion()->post(route('vehiculos.store'), $this->datos([
            'imagen' => UploadedFile::fake()->image('grande.png')->size(3072),
        ]))->assertSessionHasErrors(['imagen' => 'La imagen no puede pesar más de 2 MB.']);

        $this->assertSame([], $this->archivos());

        // El límite es max:2048 (en KB): justo 2 MB todavía se acepta.
        $vehiculo = $this->registrar(UploadedFile::fake()->image('justa.png')->size(2048));
        Storage::disk('public')->assertExists($vehiculo->imagen);
    }

    public function test_almacenamiento_guarda_la_foto_con_nombre_aleatorio_y_en_la_bd_solo_la_ruta(): void
    {
        // [SEGURIDAD] El archivo no conserva el nombre que trae del cliente: Laravel le pone un
        // nombre aleatorio, así nadie elige la ruta ni pisa la foto de otro vehículo.
        $vehiculo = $this->registrar($this->foto('png', 'mi auto'));

        $this->assertMatchesRegularExpression('#^vehiculos/[A-Za-z0-9]{40}\.png$#', $vehiculo->imagen);
        $this->assertDatabaseHas('vehiculos', ['id' => $vehiculo->id, 'imagen' => $vehiculo->imagen]);
    }

    public function test_reemplazo_borra_la_foto_anterior_y_deja_solo_la_nueva(): void
    {
        $vehiculo = $this->registrar($this->foto('jpg'));
        $anterior = $vehiculo->imagen;

        $this->editar($vehiculo, ['imagen' => $this->foto('webp')])->assertSessionHasNoErrors();

        $nueva = $vehiculo->fresh()->imagen;
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($nueva);
        $this->assertSame([$nueva], $this->archivos());
    }

    public function test_editar_sin_foto_nueva_conserva_la_actual(): void
    {
        $vehiculo = $this->registrar($this->foto('png'));

        $this->editar($vehiculo, ['color' => 'Negro'])->assertSessionHasNoErrors();

        $this->assertSame($vehiculo->imagen, $vehiculo->fresh()->imagen);
        $this->assertSame([$vehiculo->imagen], $this->archivos());
    }

    public function test_eliminacion_del_vehiculo_borra_su_foto(): void
    {
        $vehiculo = $this->registrar($this->foto('png'));

        $this->iniciarSesion()->delete(route('vehiculos.destroy', $vehiculo))
            ->assertSessionHas('exito', 'Vehículo eliminado correctamente.');

        $this->assertModelMissing($vehiculo);
        $this->assertSame([], $this->archivos());
    }

    public function test_por_defecto_sin_foto_la_ficha_y_el_listado_muestran_vehiculo_default_svg(): void
    {
        $vehiculo = $this->registrar();

        $this->assertNull($vehiculo->imagen);
        $this->get(route('vehiculos.show', $vehiculo))->assertOk()
            ->assertSee('src="'.asset('img/vehiculo-default.svg').'"', false);
        $this->get(route('vehiculos.index'))->assertOk()
            ->assertSee('src="'.asset('img/vehiculo-default.svg').'"', false);
        $this->assertFileExists(public_path('img/vehiculo-default.svg'));
    }

    public function test_url_publica_las_vistas_usan_asset_storage_y_storage_link_apunta_al_disco_public(): void
    {
        $vehiculo = $this->registrar($this->foto('png'));
        $url = asset('storage/'.$vehiculo->imagen);

        $this->get(route('vehiculos.show', $vehiculo))->assertOk()->assertSee('src="'.$url.'"', false);
        $this->get(route('vehiculos.index'))->assertOk()->assertSee('src="'.$url.'"', false);
        $this->iniciarSesion()->get(route('vehiculos.edit', $vehiculo))->assertOk()
            ->assertSee('id="vista-imagen" src="'.$url.'"', false);

        // php artisan storage:link crea public/storage apuntando a storage/app/public, que es la
        // raíz del disco public. Sin ese enlace, la URL /storage/... daría 404 en el navegador.
        $this->assertSame(storage_path('app/public'), config('filesystems.links')[public_path('storage')]);
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
    }

    public function test_ciclo_completo_registrar_reemplazar_y_eliminar_no_deja_archivos_huerfanos(): void
    {
        $vehiculo = $this->registrar($this->foto('jpg'));
        $this->assertCount(1, $this->archivos());

        $this->editar($vehiculo, ['imagen' => $this->foto('png')])->assertSessionHasNoErrors();
        $this->editar($vehiculo, ['imagen' => $this->foto('webp')])->assertSessionHasNoErrors();
        $this->editar($vehiculo, ['color' => 'Gris'])->assertSessionHasNoErrors();
        $this->assertSame([$vehiculo->fresh()->imagen], $this->archivos());

        $this->iniciarSesion()->delete(route('vehiculos.destroy', $vehiculo));
        $this->assertSame([], $this->archivos());
    }
}
