<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Búsqueda por nombre y orden por columnas permitidas; cualquier columna inventada cae en nombre.
class TipoServicioBusquedaTest extends TestCase
{
    use RefreshDatabase;

    public function test_busca_por_nombre(): void
    {
        TipoServicio::factory()->create(['nombre' => 'Frenos']);
        TipoServicio::factory()->create(['nombre' => 'Alineación']);

        $this->get(route('servicios.index', ['busqueda' => 'fre']))
            ->assertOk()
            ->assertSee('Frenos')
            ->assertDontSee('Alineación')
            ->assertViewHas('tiposServicio', fn ($tipos) => $tipos->total() === 1);
    }

    public function test_ordena_por_precio_descendente(): void
    {
        TipoServicio::factory()->create(['nombre' => 'Barato', 'precio_base' => 10]);
        $caro = TipoServicio::factory()->create(['nombre' => 'Caro', 'precio_base' => 90]);

        $this->get(route('servicios.index', [
            'orden' => 'precio_base',
            'direccion' => 'desc',
        ]))
            ->assertOk()
            ->assertViewHas('tiposServicio', function ($tipos) use ($caro) {
                return $tipos->getCollection()->first()->id === $caro->id;
            });
    }

    public function test_ordena_por_cantidad_de_ordenes(): void
    {
        $conDos = TipoServicio::factory()->create(['nombre' => 'Con órdenes']);
        OrdenServicio::factory()->count(2)->create(['tipo_servicio_id' => $conDos->id]);
        TipoServicio::factory()->create(['nombre' => 'Sin órdenes']);

        $this->get(route('servicios.index', [
            'orden' => 'ordenes_count',
            'direccion' => 'desc',
        ]))
            ->assertOk()
            ->assertViewHas('tiposServicio', function ($tipos) use ($conDos) {
                return $tipos->getCollection()->first()->id === $conDos->id;
            });
    }

    public function test_una_columna_de_orden_inventada_cae_en_nombre(): void
    {
        $this->get(route('servicios.index', [
            'orden' => 'precio_base desc; drop table tipos_servicio',
            'direccion' => 'desc',
        ]))
            ->assertOk()
            ->assertViewHas('orden', 'nombre')
            ->assertViewHas('direccion', 'desc');
    }

    public function test_conserva_la_busqueda_en_los_enlaces_de_paginacion(): void
    {
        TipoServicio::factory()->create(['nombre' => 'Frenos']);

        $this->get(route('servicios.index', ['busqueda' => 'fre']))
            ->assertOk()
            ->assertViewHas('tiposServicio', function ($tipos) {
                return str_contains($tipos->url(2), 'busqueda=fre');
            });
    }

    public function test_sin_resultados_muestra_un_mensaje_con_la_busqueda(): void
    {
        $this->get(route('servicios.index', ['busqueda' => 'zzz']))
            ->assertOk()
            ->assertSee('No hay servicios que coincidan con «zzz».');
    }
}
