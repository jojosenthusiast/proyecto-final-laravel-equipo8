<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Ficha pública de un servicio: datos del catálogo, ingresos de órdenes finalizadas, historial
// reciente con enlaces y estado vacío.
class TipoServicioFichaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ficha_muestra_datos_ingresos_y_enlaces(): void
    {
        $servicio = TipoServicio::factory()->create([
            'nombre' => 'Frenos',
            'descripcion' => 'Revisión y cambio de pastillas.',
            'precio_base' => 50,
        ]);

        $primera = OrdenServicio::factory()->finalizada()->create([
            'tipo_servicio_id' => $servicio->id,
            'costo_total' => 25,
        ]);
        OrdenServicio::factory()->finalizada()->create([
            'tipo_servicio_id' => $servicio->id,
            'costo_total' => 40,
        ]);
        OrdenServicio::factory()->pendiente()->create([
            'tipo_servicio_id' => $servicio->id,
            'costo_total' => 99,
        ]);

        $this->get(route('servicios.show', $servicio))
            ->assertOk()
            ->assertSee('Frenos')
            ->assertSee('Revisión y cambio de pastillas.')
            ->assertSee('65.00')
            ->assertSee(route('ordenes.show', $primera), false)
            ->assertSee(route('vehiculos.show', $primera->vehiculo), false)
            ->assertViewHas('servicio', function ($servicio) {
                return $servicio->ordenes_count === 3
                    && (float) $servicio->ingresos === 65.0;
            });
    }

    public function test_la_ficha_sin_ordenes_muestra_el_estado_vacio(): void
    {
        $servicio = TipoServicio::factory()->create(['nombre' => 'Balanceo']);

        $this->get(route('servicios.show', $servicio))
            ->assertOk()
            ->assertSee('Este servicio no tiene órdenes registradas.');
    }

    public function test_la_ficha_muestra_solo_las_diez_ordenes_mas_recientes(): void
    {
        $servicio = TipoServicio::factory()->create(['nombre' => 'Alineación']);

        foreach (range(1, 12) as $dia) {
            OrdenServicio::factory()->create([
                'tipo_servicio_id' => $servicio->id,
                'fecha_ingreso' => '2026-01-'.str_pad((string) $dia, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $this->get(route('servicios.show', $servicio))
            ->assertOk()
            ->assertViewHas('ordenes', function ($ordenes) {
                return $ordenes->count() === 10
                    && $ordenes->first()->fecha_ingreso->format('Y-m-d') === '2026-01-12';
            });
    }
}
