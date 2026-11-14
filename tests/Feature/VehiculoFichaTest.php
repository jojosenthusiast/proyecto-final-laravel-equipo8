<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// La ficha como centro de acciones: desde ella se llega a todas las acciones del vehículo y de
// sus órdenes, con los totales calculados por el modelo y sin consultas N+1.
class VehiculoFichaTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vehiculo = Vehiculo::factory()->create(['placa' => 'P700001', 'kilometraje' => 40000]);
    }

    private function crearOrden(array $datos, ?string $estado = null): OrdenServicio
    {
        $tipo = TipoServicio::firstOrCreate(['nombre' => 'Cambio de aceite'], ['precio_base' => 30]);
        $fabrica = OrdenServicio::factory();
        $fabrica = $estado ? $fabrica->{$estado}() : $fabrica;

        return $fabrica->create($datos + ['vehiculo_id' => $this->vehiculo->id, 'tipo_servicio_id' => $tipo->id]);
    }

    public function test_la_ficha_enlaza_a_registrar_orden_con_el_vehiculo_preseleccionado(): void
    {
        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertSee('Registrar orden')
            ->assertSee(e(route('ordenes.create', ['vehiculo' => $this->vehiculo->id])), false)
            ->assertSee('/ordenes/create?vehiculo='.$this->vehiculo->id, false);
    }

    public function test_el_total_gastado_suma_solo_las_ordenes_finalizadas(): void
    {
        $this->crearOrden(['fecha_ingreso' => '2026-09-01', 'costo_total' => 120.50], 'finalizada');
        $this->crearOrden(['fecha_ingreso' => '2026-10-01', 'costo_total' => 80.00], 'finalizada');
        $this->crearOrden(['fecha_ingreso' => '2026-11-01', 'costo_total' => 999.99], 'enProceso');

        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertViewHas('totalGastado', 200.50)
            ->assertSee('$200.50');
    }

    public function test_muestra_el_ultimo_kilometraje_registrado_en_las_ordenes(): void
    {
        $this->crearOrden(['kilometraje_ingreso' => 85000]);

        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertViewHas('ultimoKilometraje', 85000)
            ->assertSee('85,000 km');
    }

    public function test_cada_orden_enlaza_a_su_detalle_y_muestra_su_estado(): void
    {
        $pendiente = $this->crearOrden([], 'pendiente');
        $enProceso = $this->crearOrden([], 'enProceso');

        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertSee(route('ordenes.show', $pendiente), false)
            ->assertSee(route('ordenes.show', $enProceso), false)
            ->assertSee('etiqueta etiqueta-pendiente', false)
            ->assertSee('etiqueta etiqueta-en-proceso', false);
    }

    public function test_las_ordenes_van_de_la_mas_reciente_a_la_mas_antigua(): void
    {
        $this->crearOrden(['fecha_ingreso' => '2026-08-15', 'descripcion' => 'Orden de agosto']);
        $this->crearOrden(['fecha_ingreso' => '2026-11-10', 'descripcion' => 'Orden de noviembre']);
        $this->crearOrden(['fecha_ingreso' => '2026-10-03', 'descripcion' => 'Orden de octubre']);

        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertSeeInOrder(['Orden de noviembre', 'Orden de octubre', 'Orden de agosto']);
    }

    public function test_la_ficha_con_ordenes_no_hace_consultas_perezosas(): void
    {
        // preventLazyLoading está activo fuera de producción: si la vista pidiera el tipo de
        // servicio de una orden sin haberlo cargado con load(), la petición fallaría con
        // LazyLoadingViolationException.
        $this->assertTrue(Model::preventsLazyLoading());
        $this->crearOrden([], 'finalizada');
        $this->crearOrden([], 'pendiente');

        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertSee('Cambio de aceite');
    }

    public function test_un_visitante_no_ve_editar_ni_eliminar(): void
    {
        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertDontSee(route('vehiculos.edit', $this->vehiculo), false)
            ->assertDontSee('name="_method" value="DELETE"', false);
    }

    public function test_con_sesion_se_ven_editar_y_eliminar_con_confirmacion(): void
    {
        $this->iniciarSesion()->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertSee(route('vehiculos.edit', $this->vehiculo), false)
            ->assertSee('action="'.route('vehiculos.destroy', $this->vehiculo).'"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('data-confirmar="¿Eliminar el vehículo P700001?"', false);
    }

    public function test_sin_ordenes_muestra_el_mensaje_y_los_totales_en_cero(): void
    {
        $this->get(route('vehiculos.show', $this->vehiculo))->assertOk()
            ->assertSee('Este vehículo no ha tenido órdenes de servicio.')
            ->assertViewHas('totalGastado', 0.0)
            ->assertViewHas('ultimoKilometraje', 40000);
    }
}
