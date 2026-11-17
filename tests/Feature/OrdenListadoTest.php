<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Listado de órdenes: paginado, filtrable por estado y tipo de servicio, y sin consultas N+1.
class OrdenListadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_las_ordenes_sin_lazy_loading(): void
    {
        // preventLazyLoading está activo: si la vista accediera a una relación sin cargar,
        // la petición respondería 500 por LazyLoadingViolationException.
        OrdenServicio::factory()->count(3)->create();

        $this->get(route('ordenes.index'))
            ->assertOk()
            ->assertViewIs('ordenes.index')
            ->assertViewHas('ordenes', fn ($ordenes) => $ordenes->total() === 3);
    }

    public function test_filtra_por_estado(): void
    {
        $pendiente = OrdenServicio::factory()->pendiente()->create();
        OrdenServicio::factory()->finalizada()->create();

        $this->get(route('ordenes.index', ['estado' => 'pendiente']))
            ->assertOk()
            ->assertViewHas('ordenes', function ($ordenes) use ($pendiente) {
                return $ordenes->total() === 1
                    && $ordenes->getCollection()->first()->id === $pendiente->id;
            });
    }

    public function test_un_estado_inventado_se_ignora_y_no_rompe_la_pagina(): void
    {
        OrdenServicio::factory()->pendiente()->create();
        OrdenServicio::factory()->finalizada()->create();

        $this->get(route('ordenes.index', ['estado' => "pendiente' or 1=1 --"]))
            ->assertOk()
            ->assertViewHas('estado', null)
            ->assertViewHas('ordenes', fn ($ordenes) => $ordenes->total() === 2);
    }

    public function test_filtra_por_tipo_de_servicio(): void
    {
        $frenos = TipoServicio::factory()->create(['nombre' => 'Frenos']);
        $alineacion = TipoServicio::factory()->create(['nombre' => 'Alineación']);
        $deFrenos = OrdenServicio::factory()->create(['tipo_servicio_id' => $frenos->id]);
        OrdenServicio::factory()->create(['tipo_servicio_id' => $alineacion->id]);

        $this->get(route('ordenes.index', ['tipo' => $frenos->id]))
            ->assertOk()
            ->assertViewHas('ordenes', function ($ordenes) use ($deFrenos) {
                return $ordenes->total() === 1
                    && $ordenes->getCollection()->first()->id === $deFrenos->id;
            });
    }

    public function test_combina_estado_y_tipo(): void
    {
        $frenos = TipoServicio::factory()->create();
        $buscada = OrdenServicio::factory()->enProceso()->create(['tipo_servicio_id' => $frenos->id]);
        OrdenServicio::factory()->pendiente()->create(['tipo_servicio_id' => $frenos->id]);
        OrdenServicio::factory()->enProceso()->create();

        $this->get(route('ordenes.index', ['estado' => 'en_proceso', 'tipo' => $frenos->id]))
            ->assertOk()
            ->assertViewHas('ordenes', function ($ordenes) use ($buscada) {
                return $ordenes->total() === 1
                    && $ordenes->getCollection()->first()->id === $buscada->id;
            });
    }

    public function test_pagina_de_quince_en_quince_y_conserva_los_filtros(): void
    {
        OrdenServicio::factory()->count(16)->pendiente()->create();

        $this->get(route('ordenes.index', ['estado' => 'pendiente']))
            ->assertOk()
            ->assertViewHas('ordenes', function ($ordenes) {
                return $ordenes->count() === 15
                    && $ordenes->total() === 16
                    && str_contains($ordenes->url(2), 'estado=pendiente');
            });
    }

    public function test_muestra_placa_servicio_y_etiqueta_del_estado(): void
    {
        $orden = OrdenServicio::factory()->enProceso()->create();
        $orden->load(['vehiculo', 'tipoServicio']);

        $this->get(route('ordenes.index'))
            ->assertOk()
            ->assertSee($orden->vehiculo->placa)
            ->assertSee($orden->tipoServicio->nombre)
            ->assertSee('etiqueta etiqueta-en-proceso', false)
            ->assertSee(route('ordenes.show', $orden), false);
    }

    public function test_sin_ordenes_muestra_el_estado_vacio(): void
    {
        $this->get(route('ordenes.index'))
            ->assertOk()
            ->assertSee('No hay órdenes registradas.');
    }

    public function test_sin_resultados_por_filtro_muestra_un_mensaje(): void
    {
        OrdenServicio::factory()->pendiente()->create();

        $this->get(route('ordenes.index', ['estado' => 'finalizada']))
            ->assertOk()
            ->assertSee('No hay órdenes que coincidan con los filtros.');
    }

    public function test_la_ficha_muestra_los_datos_y_el_desglose_del_costo(): void
    {
        $tipo = TipoServicio::factory()->create(['nombre' => 'Frenos', 'precio_base' => 40]);
        $orden = OrdenServicio::factory()->finalizada()->create([
            'tipo_servicio_id' => $tipo->id,
            'costo_adicional' => 12.5,
            'kilometraje_ingreso' => 85000,
            'descripcion' => 'Cambio de pastillas delanteras',
        ]);
        $orden->load('vehiculo');

        $this->get(route('ordenes.show', $orden))
            ->assertOk()
            ->assertViewIs('ordenes.show')
            ->assertSee($orden->vehiculo->placa)
            ->assertSee('Frenos')
            ->assertSee('Cambio de pastillas delanteras')
            ->assertSee('85,000 km')
            ->assertSee($orden->fecha_ingreso->format('d/m/Y'))
            ->assertSee($orden->fecha_entrega->format('d/m/Y'))
            ->assertSee('etiqueta etiqueta-finalizada', false)
            ->assertSeeInOrder(['Precio base', '$40.00', 'Costo adicional', '$12.50', 'Costo total', '$52.50'])
            ->assertSee(route('vehiculos.show', $orden->vehiculo), false)
            ->assertSee(route('servicios.show', $tipo), false);
    }

    public function test_la_ficha_sin_entrega_lo_indica(): void
    {
        $orden = OrdenServicio::factory()->pendiente()->create(['fecha_entrega' => null]);

        $this->get(route('ordenes.show', $orden))
            ->assertOk()
            ->assertSee('Sin entregar');
    }

    public function test_una_orden_inexistente_responde_404(): void
    {
        $this->get(route('ordenes.show', 999999))->assertNotFound();
    }

    public function test_busca_por_placa(): void
    {
        $uno = Vehiculo::factory()->create(['placa' => 'P000011']);
        $dos = Vehiculo::factory()->create(['placa' => 'P000022']);
        $otro = Vehiculo::factory()->create(['placa' => 'P123456']);
        OrdenServicio::factory()->create(['vehiculo_id' => $uno->id]);
        OrdenServicio::factory()->create(['vehiculo_id' => $dos->id]);
        OrdenServicio::factory()->create(['vehiculo_id' => $otro->id]);

        $this->get(route('ordenes.index', ['placa' => 'P0000']))
            ->assertOk()
            ->assertSee('P000011')
            ->assertSee('P000022')
            ->assertDontSee('P123456')
            ->assertViewHas('ordenes', fn ($ordenes) => $ordenes->total() === 2);
    }

    public function test_la_placa_con_comodines_sql_no_rompe_la_busqueda(): void
    {
        OrdenServicio::factory()->create();

        $this->get(route('ordenes.index', ['placa' => "%' OR 1--"]))
            ->assertOk()
            ->assertViewHas('ordenes', fn ($ordenes) => $ordenes->total() === 0);
    }

    public function test_filtra_por_rango_de_fechas_de_ingreso(): void
    {
        $dentro = OrdenServicio::factory()->create(['fecha_ingreso' => '2026-10-10']);
        OrdenServicio::factory()->create(['fecha_ingreso' => '2026-09-01']);
        OrdenServicio::factory()->create(['fecha_ingreso' => '2026-11-05']);

        $this->get(route('ordenes.index', ['desde' => '2026-10-01', 'hasta' => '2026-10-31']))
            ->assertOk()
            ->assertViewHas('ordenes', function ($ordenes) use ($dentro) {
                return $ordenes->total() === 1
                    && $ordenes->getCollection()->first()->id === $dentro->id;
            });
    }

    public function test_un_rango_sin_ordenes_muestra_el_estado_vacio(): void
    {
        OrdenServicio::factory()->create(['fecha_ingreso' => '2026-10-10']);

        $this->get(route('ordenes.index', ['desde' => '2020-01-01', 'hasta' => '2020-01-31']))
            ->assertOk()
            ->assertSee('No hay órdenes que coincidan con los filtros.');
    }

    public function test_un_rango_invertido_da_error_en_espanol(): void
    {
        $this->from(route('ordenes.index'))
            ->get(route('ordenes.index', ['desde' => '2026-10-31', 'hasta' => '2026-10-01']))
            ->assertRedirect(route('ordenes.index'))
            ->assertSessionHasErrors([
                'hasta' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ]);
    }

    public function test_una_fecha_invalida_da_error_en_espanol(): void
    {
        $this->from(route('ordenes.index'))
            ->get(route('ordenes.index', ['desde' => 'ayer']))
            ->assertSessionHasErrors([
                'desde' => 'La fecha inicial no es una fecha válida.',
            ]);
    }

    public function test_la_busqueda_se_combina_con_estado_y_tipo_y_se_conserva_al_paginar(): void
    {
        $tipo = TipoServicio::factory()->create();
        $vehiculo = Vehiculo::factory()->create(['placa' => 'P000099']);
        OrdenServicio::factory()->count(16)->pendiente()->create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
            'fecha_ingreso' => '2026-10-15',
        ]);
        OrdenServicio::factory()->enProceso()->create([
            'vehiculo_id' => $vehiculo->id,
            'tipo_servicio_id' => $tipo->id,
            'fecha_ingreso' => '2026-10-15',
        ]);

        $filtros = [
            'placa' => 'P000099',
            'estado' => 'pendiente',
            'tipo' => $tipo->id,
            'desde' => '2026-10-01',
            'hasta' => '2026-10-31',
        ];

        $this->get(route('ordenes.index', $filtros))
            ->assertOk()
            ->assertViewHas('ordenes', function ($ordenes) {
                $siguiente = $ordenes->url(2);

                return $ordenes->total() === 16
                    && str_contains($siguiente, 'placa=P000099')
                    && str_contains($siguiente, 'estado=pendiente')
                    && str_contains($siguiente, 'desde=2026-10-01')
                    && str_contains($siguiente, 'hasta=2026-10-31');
            });
    }
}
