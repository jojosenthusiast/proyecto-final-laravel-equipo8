<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Regla 3 (kilometraje) y total gastado: una sola fuente de verdad en el modelo Vehiculo.
class ModeloVehiculoTest extends TestCase
{
    use RefreshDatabase;

    private function crearOrden(Vehiculo $vehiculo, array $datos): OrdenServicio
    {
        $tipo = TipoServicio::firstOrCreate(['nombre' => 'Frenos'], ['precio_base' => 80]);

        return OrdenServicio::factory()->create($datos + [
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
        ]);
    }

    public function test_sin_ordenes_el_ultimo_kilometraje_es_el_del_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);

        $this->assertSame(50000, $vehiculo->ultimoKilometraje());
    }

    public function test_con_ordenes_devuelve_el_mayor_kilometraje_registrado(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);
        $this->crearOrden($vehiculo, ['kilometraje_ingreso' => 60000]);
        $this->crearOrden($vehiculo, ['kilometraje_ingreso' => 85000]);

        $this->assertSame(85000, $vehiculo->ultimoKilometraje());
    }

    public function test_el_kilometraje_del_vehiculo_gana_si_es_mayor_que_el_de_sus_ordenes(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 90000]);
        $this->crearOrden($vehiculo, ['kilometraje_ingreso' => 85000]);

        $this->assertSame(90000, $vehiculo->ultimoKilometraje());
    }

    public function test_ignora_la_orden_indicada_para_no_compararla_consigo_misma(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);
        $this->crearOrden($vehiculo, ['kilometraje_ingreso' => 60000]);
        $editada = $this->crearOrden($vehiculo, ['kilometraje_ingreso' => 85000]);

        $this->assertSame(60000, $vehiculo->ultimoKilometraje($editada->id));
    }

    public function test_no_mezcla_ordenes_de_otros_vehiculos(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);
        $otro = Vehiculo::factory()->create(['kilometraje' => 10000]);
        $this->crearOrden($otro, ['kilometraje_ingreso' => 200000]);

        $this->assertSame(50000, $vehiculo->ultimoKilometraje());
    }

    public function test_total_gastado_suma_solo_las_ordenes_finalizadas(): void
    {
        $vehiculo = Vehiculo::factory()->create();
        $this->crearOrden($vehiculo, ['estado' => 'finalizada', 'costo_total' => 25.00]);
        $this->crearOrden($vehiculo, ['estado' => 'finalizada', 'costo_total' => 40.50]);
        $this->crearOrden($vehiculo, ['estado' => 'pendiente', 'costo_total' => 100.00]);
        $this->crearOrden($vehiculo, ['estado' => 'en_proceso', 'costo_total' => 70.00]);

        $this->assertSame(65.5, $vehiculo->totalGastado());
    }

    public function test_total_gastado_es_cero_sin_ordenes_finalizadas(): void
    {
        $this->assertSame(0.0, Vehiculo::factory()->create()->totalGastado());
    }
}
