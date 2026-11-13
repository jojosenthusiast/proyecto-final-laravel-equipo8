<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Red de seguridad de la Fase 1: si la Fase 2 rompe una página que ya funcionaba, esta prueba falla.
// Solo revisa estados HTTP y datos, nunca textos de diseño, para que el pulido visual no la rompa.
class Fase1RegresionTest extends TestCase
{
    use RefreshDatabase;

    // Siembra los datos de la Fase 1 (servicios, 20 vehículos y órdenes) antes de cada prueba.
    // Se usa $this->seed() y no la propiedad $seed: RefreshDatabase migra una sola vez por
    // proceso y $seed solo sembraría si esta clase fuera la primera en ejecutarse.
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_el_panel_responde_200(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_el_listado_de_vehiculos_responde_200(): void
    {
        $this->get('/vehiculos')->assertOk();
    }

    public function test_la_ficha_de_un_vehiculo_responde_200(): void
    {
        $vehiculo = Vehiculo::first();

        $this->get('/vehiculos/'.$vehiculo->id)
            ->assertOk()
            ->assertViewHas('vehiculo', fn ($v) => $v->is($vehiculo));
    }

    public function test_el_catalogo_de_servicios_responde_200(): void
    {
        $this->get('/servicios')->assertOk();
    }

    public function test_un_vehiculo_inexistente_responde_404(): void
    {
        $this->get('/vehiculos/999999')->assertNotFound();
    }

    public function test_el_listado_muestra_10_vehiculos_por_pagina(): void
    {
        $this->get('/vehiculos')
            ->assertOk()
            ->assertViewHas('vehiculos', fn ($pagina) => $pagina->count() === 10 && $pagina->total() === Vehiculo::count());
    }

    public function test_el_filtro_por_marca_solo_devuelve_esa_marca(): void
    {
        $marca = Vehiculo::value('marca');

        $this->get('/vehiculos?marca='.urlencode($marca))
            ->assertOk()
            ->assertViewHas('vehiculos', fn ($pagina) => $pagina->isNotEmpty()
                && $pagina->every(fn ($vehiculo) => $vehiculo->marca === $marca));
    }

    public function test_una_consulta_n_mas_1_lanza_excepcion_fuera_de_produccion(): void
    {
        // [SEGURIDAD] preventLazyLoading convierte cada consulta N+1 olvidada en un error visible.
        $this->expectException(LazyLoadingViolationException::class);

        OrdenServicio::all()->first()->vehiculo;
    }
}
