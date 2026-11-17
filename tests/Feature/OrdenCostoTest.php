<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Criterio 4, «cálculos realizados por el sistema»: costo_total = precio_base + costo_adicional,
// calculado por el backend y nunca recibido del formulario.
class OrdenCostoTest extends TestCase
{
    use RefreshDatabase;

    public function test_calcula_el_total_con_el_precio_base_y_el_adicional(): void
    {
        $tipo = TipoServicio::factory()->create(['precio_base' => 45.5]);
        $orden = new OrdenServicio(['costo_adicional' => 20.25]);
        $orden->tipoServicio()->associate($tipo);

        $orden->calcularCostoTotal();

        $this->assertSame('65.75', $orden->costo_total);
    }

    public function test_sin_costo_adicional_el_total_es_el_precio_base(): void
    {
        $tipo = TipoServicio::factory()->create(['precio_base' => 30]);
        $orden = new OrdenServicio(['costo_adicional' => null]);
        $orden->tipoServicio()->associate($tipo);

        $orden->calcularCostoTotal();

        $this->assertSame('30.00', $orden->costo_total);
    }

    public function test_redondea_a_dos_decimales(): void
    {
        $tipo = TipoServicio::factory()->create(['precio_base' => 0.1]);
        $orden = new OrdenServicio(['costo_adicional' => 0.2]);
        $orden->tipoServicio()->associate($tipo);

        $orden->calcularCostoTotal();

        $this->assertSame('0.30', $orden->costo_total);
    }

    public function test_el_costo_total_enviado_en_el_formulario_no_tiene_efecto(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 1000]);
        $tipo = TipoServicio::factory()->create(['precio_base' => 40]);

        $this->iniciarSesion()->post(route('ordenes.store'), [
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
            'fecha_ingreso' => now()->toDateString(),
            'kilometraje_ingreso' => 1500,
            'descripcion' => 'Cambio de aceite',
            'costo_adicional' => 15,
            'costo_total' => 1,
        ])->assertRedirect();

        $this->assertSame('55.00', OrdenServicio::sole()->costo_total);
    }

    public function test_sin_costo_adicional_en_el_formulario_se_guarda_cero(): void
    {
        $vehiculo = Vehiculo::factory()->create(['kilometraje' => 1000]);
        $tipo = TipoServicio::factory()->create(['precio_base' => 40]);

        $this->iniciarSesion()->post(route('ordenes.store'), [
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
            'fecha_ingreso' => now()->toDateString(),
            'kilometraje_ingreso' => 1500,
            'descripcion' => 'Cambio de aceite',
        ])->assertRedirect();

        $orden = OrdenServicio::sole();
        $this->assertSame('0.00', $orden->costo_adicional);
        $this->assertSame('40.00', $orden->costo_total);
    }
}
