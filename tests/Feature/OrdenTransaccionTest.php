<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Database\Seeders\EscenariosDefensaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

// Regla 3 al registrar (el kilometraje de ingreso no baja del último registrado) y coherencia del
// vehículo: la orden y el nuevo kilometraje se guardan juntos o no se guarda ninguno.
class OrdenTransaccionTest extends TestCase
{
    use RefreshDatabase;

    private function datos(Vehiculo $vehiculo, int $kilometraje): array
    {
        return [
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => TipoServicio::factory()->create()->id,
            'fecha_ingreso' => now()->toDateString(),
            'kilometraje_ingreso' => $kilometraje,
            'descripcion' => 'Revisión general',
        ];
    }

    public function test_rechaza_un_kilometraje_menor_al_ultimo_registrado(): void
    {
        // Escenario de la defensa: P000002 tiene una orden con 85 000 km.
        $this->seed(EscenariosDefensaSeeder::class);
        $vehiculo = Vehiculo::where('placa', 'P000002')->sole();
        $ordenesAntes = OrdenServicio::count();

        $this->iniciarSesion()
            ->post(route('ordenes.store'), $this->datos($vehiculo, 80000))
            ->assertSessionHasErrors([
                'kilometraje_ingreso' => 'El kilometraje no puede ser menor que el último registrado (85000 km).',
            ]);

        $this->assertSame($ordenesAntes, OrdenServicio::count());
    }

    public function test_un_kilometraje_mayor_actualiza_el_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 80000]);

        $this->iniciarSesion()
            ->post(route('ordenes.store'), $this->datos($vehiculo, 90000))
            ->assertRedirect();

        $this->assertSame(90000, $vehiculo->fresh()->kilometraje);
        $this->assertSame(90000, OrdenServicio::sole()->kilometraje_ingreso);
    }

    public function test_un_kilometraje_igual_no_cambia_el_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 80000]);

        $this->iniciarSesion()
            ->post(route('ordenes.store'), $this->datos($vehiculo, 80000))
            ->assertRedirect();

        $this->assertSame(80000, $vehiculo->fresh()->kilometraje);
        $this->assertDatabaseCount('ordenes_servicio', 1);
    }

    public function test_si_falla_la_actualizacion_del_vehiculo_no_queda_la_orden(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 80000]);

        // Fallo simulado: el vehículo no se puede actualizar.
        Vehiculo::updating(function () {
            throw new RuntimeException('Fallo simulado al actualizar el vehículo.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->iniciarSesion()->post(route('ordenes.store'), $this->datos($vehiculo, 90000));
            $this->fail('Se esperaba la excepción del fallo simulado.');
        } catch (RuntimeException $e) {
            $this->assertSame('Fallo simulado al actualizar el vehículo.', $e->getMessage());
        }

        // DB::transaction revirtió la orden: ni la orden ni el kilometraje quedaron a medias.
        $this->assertDatabaseCount('ordenes_servicio', 0);
        $this->assertSame(80000, $vehiculo->fresh()->kilometraje);
    }

    public function test_un_vehiculo_no_numerico_da_error_de_validacion_y_no_500(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->iniciarSesion()
            ->post(route('ordenes.store'), array_merge($this->datos($vehiculo, 1000), ['vehiculo_id' => 'abc']))
            ->assertSessionHasErrors('vehiculo_id');
    }
}
