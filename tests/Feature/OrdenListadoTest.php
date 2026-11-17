<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
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
}
