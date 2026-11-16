<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// El comando solo borra fotos que no pertenecen a ningún vehículo; las que están en uso se quedan.
class ImagenesHuerfanasCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_detecta_y_borra_solo_las_fotos_huerfanas(): void
    {
        Storage::disk('public')->put('vehiculos/huerfana.jpg', 'foto sin dueño');
        Vehiculo::factory()->create(['imagen' => 'vehiculos/en-uso.jpg']);
        Storage::disk('public')->put('vehiculos/en-uso.jpg', 'foto en uso');

        $this->artisan('biela:imagenes-huerfanas')
            ->expectsOutputToContain('1 archivo huérfano')
            ->assertExitCode(0);

        Storage::disk('public')->assertExists('vehiculos/huerfana.jpg');
        Storage::disk('public')->assertExists('vehiculos/en-uso.jpg');

        $this->artisan('biela:imagenes-huerfanas', ['--eliminar' => true])
            ->expectsOutputToContain('1 archivo huérfano')
            ->assertExitCode(0);

        Storage::disk('public')->assertMissing('vehiculos/huerfana.jpg');
        Storage::disk('public')->assertExists('vehiculos/en-uso.jpg');
    }

    public function test_sin_huerfanos_no_borra_nada(): void
    {
        Vehiculo::factory()->create(['imagen' => 'vehiculos/en-uso.jpg']);
        Storage::disk('public')->put('vehiculos/en-uso.jpg', 'foto en uso');

        $this->artisan('biela:imagenes-huerfanas')
            ->expectsOutputToContain('No hay archivos huérfanos')
            ->assertExitCode(0);

        Storage::disk('public')->assertExists('vehiculos/en-uso.jpg');
    }
}
