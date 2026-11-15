<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use App\Rules\KilometrajeNoMenorAlUltimo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

// Regla 3 del caso: el kilometraje de ingreso nunca baja del último registrado para el vehículo.
class ReglaKilometrajeTest extends TestCase
{
    use RefreshDatabase;

    private function crearOrden(Vehiculo $vehiculo, int $kilometraje): OrdenServicio
    {
        $tipo = TipoServicio::factory()->create();

        return OrdenServicio::factory()->create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
            'kilometraje_ingreso' => $kilometraje,
        ]);
    }

    private function validar(mixed $valor, ?int $vehiculoId, ?int $ignorarOrdenId = null)
    {
        return Validator::make(
            ['kilometraje_ingreso' => $valor],
            ['kilometraje_ingreso' => [new KilometrajeNoMenorAlUltimo($vehiculoId, $ignorarOrdenId)]]
        );
    }

    public function test_un_kilometraje_igual_al_ultimo_es_valido(): void
    {
        $vehiculo = Vehiculo::factory()->conKilometraje(50000)->create();
        $this->crearOrden($vehiculo, 85000);

        $this->assertFalse($this->validar(85000, $vehiculo->id)->fails());
    }

    public function test_un_kilometraje_menor_al_ultimo_falla_con_el_mensaje_en_espanol(): void
    {
        $vehiculo = Vehiculo::factory()->conKilometraje(50000)->create();
        $this->crearOrden($vehiculo, 85000);

        $validador = $this->validar(80000, $vehiculo->id);

        $this->assertTrue($validador->fails());
        $this->assertSame(
            'El kilometraje no puede ser menor que el último registrado (85000 km).',
            $validador->errors()->first('kilometraje_ingreso')
        );
    }

    public function test_al_editar_la_unica_orden_se_compara_contra_el_kilometraje_del_vehiculo(): void
    {
        $vehiculo = Vehiculo::factory()->conKilometraje(50000)->create();
        $orden = $this->crearOrden($vehiculo, 85000);

        // Sin ignorar la orden, 60000 fallaría contra los 85000 de esa misma orden.
        $this->assertFalse($this->validar(60000, $vehiculo->id, $orden->id)->fails());
    }

    public function test_un_vehiculo_inexistente_no_falla_para_dejar_el_error_a_exists(): void
    {
        $this->assertFalse($this->validar(1, 999999)->fails());
    }

    public function test_un_kilometraje_que_no_es_numero_no_falla_en_esta_regla(): void
    {
        $vehiculo = Vehiculo::factory()->create();

        $this->assertFalse($this->validar('no-es-un-numero', $vehiculo->id)->fails());
    }
}
