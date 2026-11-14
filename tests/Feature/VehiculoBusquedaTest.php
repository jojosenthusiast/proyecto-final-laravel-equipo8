<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Búsqueda del listado principal por placa, propietario o modelo, combinable con el filtro de marca.
class VehiculoBusquedaTest extends TestCase
{
    use RefreshDatabase;

    private function placasDelListado(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('vehiculos')->pluck('placa')->sort()->values()->all();
    }

    public function test_busca_por_parte_del_nombre_del_propietario(): void
    {
        Vehiculo::factory()->create(['placa' => 'P100001', 'propietario_nombre' => 'María Fernanda López']);
        Vehiculo::factory()->create(['placa' => 'P100002', 'propietario_nombre' => 'José Martínez']);

        $this->assertSame(['P100001'], $this->placasDelListado('/vehiculos?buscar=fernanda'));
    }

    public function test_busca_por_placa_y_por_modelo(): void
    {
        Vehiculo::factory()->create(['placa' => 'P123456', 'marca' => 'Toyota', 'modelo' => 'Corolla']);
        Vehiculo::factory()->create(['placa' => 'P654321', 'marca' => 'Honda', 'modelo' => 'Civic']);

        $this->assertSame(['P123456'], $this->placasDelListado('/vehiculos?buscar=p1234'));
        $this->assertSame(['P654321'], $this->placasDelListado('/vehiculos?buscar=civic'));
    }

    public function test_busqueda_y_marca_devuelven_la_interseccion(): void
    {
        Vehiculo::factory()->create(['placa' => 'P200001', 'marca' => 'Toyota', 'modelo' => 'Hilux', 'propietario_nombre' => 'Ana Pérez']);
        Vehiculo::factory()->create(['placa' => 'P200002', 'marca' => 'Nissan', 'modelo' => 'Frontier', 'propietario_nombre' => 'Ana Gómez']);
        Vehiculo::factory()->create(['placa' => 'P200003', 'marca' => 'Toyota', 'modelo' => 'Yaris', 'propietario_nombre' => 'Luis Ramos']);

        // «ana» coincide con dos propietarios, pero solo uno es Toyota. Si los OR quedaran sin
        // paréntesis junto al AND de la marca, el Nissan también aparecería.
        $this->assertSame(['P200001'], $this->placasDelListado('/vehiculos?buscar=ana&marca=Toyota'));
    }

    public function test_la_paginacion_conserva_la_busqueda_y_la_marca(): void
    {
        Vehiculo::factory()->count(12)->sequence(fn ($s) => ['placa' => sprintf('P3000%02d', $s->index)])
            ->create(['marca' => 'Kia', 'modelo' => 'Rio']);
        // El propietario va fijo: uno aleatorio como «Rosario» o «Dario» también contiene «rio» y
        // haría que este vehículo apareciera en la búsqueda.
        Vehiculo::factory()->create(['placa' => 'P399999', 'marca' => 'Kia', 'modelo' => 'Sportage', 'propietario_nombre' => 'Ana López']);

        $pagina = $this->get('/vehiculos?buscar=rio&marca=Kia')->assertOk()->viewData('vehiculos');

        $this->assertSame(12, $pagina->total());
        $this->assertStringContainsString('buscar=rio', $pagina->nextPageUrl());
        $this->assertStringContainsString('marca=Kia', $pagina->nextPageUrl());
        $this->assertCount(2, $this->get($pagina->nextPageUrl())->viewData('vehiculos'));
    }

    public function test_un_texto_con_comilla_simple_no_rompe_la_consulta(): void
    {
        Vehiculo::factory()->create(['placa' => 'P400001', 'propietario_nombre' => "Patrick O'Brien"]);
        Vehiculo::factory()->create(['placa' => 'P400002']);

        $this->assertSame(['P400001'], $this->placasDelListado("/vehiculos?buscar=O'Brien"));
        $this->get("/vehiculos?buscar=' OR 1=1 --")->assertOk()
            ->assertSee('Ningún vehículo coincide con la búsqueda.');
    }

    public function test_un_filtro_enviado_como_arreglo_se_ignora_sin_error(): void
    {
        Vehiculo::factory()->count(3)->create();

        $this->get('/vehiculos?buscar[]=x&marca[]=Toyota')->assertOk()
            ->assertViewHas('vehiculos', fn ($pagina) => $pagina->total() === 3);
    }

    private function crearTresVehiculos(): void
    {
        Vehiculo::factory()->create(['placa' => 'P500002', 'anio' => 2020, 'kilometraje' => 10000, 'marca' => 'Kia', 'modelo' => 'Rio']);
        Vehiculo::factory()->create(['placa' => 'P500003', 'anio' => 1998, 'kilometraje' => 250000, 'marca' => 'Kia', 'modelo' => 'Rio']);
        Vehiculo::factory()->create(['placa' => 'P500001', 'anio' => 2010, 'kilometraje' => 90000, 'marca' => 'Honda', 'modelo' => 'Fit']);
    }

    private function placasEnOrden(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('vehiculos')->pluck('placa')->all();
    }

    public function test_sin_parametros_el_listado_se_ordena_por_placa(): void
    {
        $this->crearTresVehiculos();

        $this->assertSame(['P500001', 'P500002', 'P500003'], $this->placasEnOrden('/vehiculos'));
    }

    public function test_ordena_por_anio_y_por_kilometraje_en_ambas_direcciones(): void
    {
        $this->crearTresVehiculos();

        $this->assertSame(['P500003', 'P500001', 'P500002'], $this->placasEnOrden('/vehiculos?orden=anio'));
        $this->assertSame(['P500002', 'P500001', 'P500003'], $this->placasEnOrden('/vehiculos?orden=anio&direccion=desc'));
        $this->assertSame(['P500003', 'P500001', 'P500002'], $this->placasEnOrden('/vehiculos?orden=kilometraje&direccion=desc'));
        $this->assertSame(['P500003', 'P500002', 'P500001'], $this->placasEnOrden('/vehiculos?orden=placa&direccion=desc'));
    }

    public function test_una_columna_no_permitida_se_ignora_y_ordena_por_placa(): void
    {
        $this->crearTresVehiculos();

        $this->assertSame(['P500001', 'P500002', 'P500003'], $this->placasEnOrden('/vehiculos?orden='.urlencode('placa;drop table vehiculos')));
        $this->assertSame(['P500001', 'P500002', 'P500003'], $this->placasEnOrden('/vehiculos?orden=propietario_telefono'));
        $this->assertSame(['P500001', 'P500002', 'P500003'], $this->placasEnOrden('/vehiculos?orden[]=anio'));
        $this->assertSame(3, Vehiculo::count(), 'La tabla sigue intacta.');
    }

    public function test_una_direccion_no_permitida_se_trata_como_ascendente(): void
    {
        $this->crearTresVehiculos();

        $this->assertSame(['P500003', 'P500001', 'P500002'], $this->placasEnOrden('/vehiculos?orden=anio&direccion='.urlencode('desc; drop table vehiculos')));
    }

    public function test_el_orden_se_combina_con_la_busqueda_y_la_marca(): void
    {
        $this->crearTresVehiculos();

        $this->assertSame(['P500002', 'P500003'], $this->placasEnOrden('/vehiculos?buscar=rio&marca=Kia&orden=kilometraje'));
        $this->assertSame(['P500003', 'P500002'], $this->placasEnOrden('/vehiculos?buscar=rio&marca=Kia&orden=kilometraje&direccion=desc'));
    }

    public function test_los_encabezados_alternan_la_direccion_y_conservan_la_busqueda_y_la_marca(): void
    {
        $this->crearTresVehiculos();

        $respuesta = $this->get('/vehiculos?buscar=rio&marca=Kia&orden=anio&direccion=asc')->assertOk();

        // La columna activa invierte su dirección; las demás empiezan en ascendente.
        $respuesta->assertSee(e(route('vehiculos.index', ['buscar' => 'rio', 'marca' => 'Kia', 'orden' => 'anio', 'direccion' => 'desc'])), false);
        $respuesta->assertSee(e(route('vehiculos.index', ['buscar' => 'rio', 'marca' => 'Kia', 'orden' => 'kilometraje', 'direccion' => 'asc'])), false);
        $respuesta->assertSee('aria-sort="ascending"', false);
        // El formulario de búsqueda conserva el orden elegido.
        $respuesta->assertSee('<input type="hidden" name="orden" value="anio">', false);
    }

    public function test_el_formulario_conserva_el_texto_buscado_y_cada_celda_tiene_su_etiqueta(): void
    {
        Vehiculo::factory()->create(['modelo' => 'Corolla']);

        $this->get('/vehiculos?buscar=Corolla')->assertOk()
            ->assertSee('value="Corolla"', false)
            ->assertSee('data-label="Placa"', false)
            ->assertSee('data-label="Propietario"', false)
            ->assertSee('Quitar filtros');
    }
}
