<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Al eliminar un vehículo, su foto debe desaparecer del disco público sin que el controlador
// tenga que borrarla a mano.
class VehiculoObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_eliminar_un_vehiculo_con_foto_borra_su_archivo(): void
    {
        Storage::disk('public')->put('vehiculos/foto.jpg', 'foto');
        $vehiculo = Vehiculo::factory()->create(['imagen' => 'vehiculos/foto.jpg']);

        $vehiculo->delete();

        Storage::disk('public')->assertMissing('vehiculos/foto.jpg');
        $this->assertDatabaseMissing('vehiculos', ['id' => $vehiculo->id]);
    }

    public function test_eliminar_un_vehiculo_sin_foto_no_lanza_error(): void
    {
        $vehiculo = Vehiculo::factory()->create(['imagen' => null]);

        $this->assertTrue($vehiculo->delete());

        $this->assertDatabaseMissing('vehiculos', ['id' => $vehiculo->id]);
        $this->assertSame([], Storage::disk('public')->allFiles('vehiculos'));
    }
}
