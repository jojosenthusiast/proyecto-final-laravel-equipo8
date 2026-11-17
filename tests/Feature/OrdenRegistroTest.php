<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Registro de órdenes con StoreOrdenServicioRequest: validación en el servidor (como si el
// formulario tuviera novalidate), mensajes en español y regla 4 (entrega no anterior al ingreso).
class OrdenRegistroTest extends TestCase
{
    use RefreshDatabase;

    private Vehiculo $vehiculo;

    private TipoServicio $tipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vehiculo = Vehiculo::factory()->create(['kilometraje' => 50000]);
        $this->tipo = TipoServicio::factory()->create(['precio_base' => 40]);
    }

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'vehiculo_id' => $this->vehiculo->id,
            'tipo_servicio_id' => $this->tipo->id,
            'fecha_ingreso' => now()->subDays(2)->toDateString(),
            'fecha_entrega' => null,
            'kilometraje_ingreso' => 52000,
            'descripcion' => 'Revisión de frenos delanteros',
            'costo_adicional' => 10,
        ], $cambios);
    }

    public function test_el_formulario_requiere_sesion(): void
    {
        $this->get(route('ordenes.create'))->assertRedirect(route('login'));
        $this->post(route('ordenes.store'), $this->datosValidos())->assertRedirect(route('login'));

        $this->assertDatabaseCount('ordenes_servicio', 0);
    }

    public function test_el_formulario_lista_vehiculos_y_servicios(): void
    {
        $this->iniciarSesion()
            ->get(route('ordenes.create'))
            ->assertOk()
            ->assertViewIs('ordenes.create')
            ->assertSee($this->vehiculo->placa)
            ->assertSee($this->tipo->nombre);
    }

    public function test_preselecciona_el_vehiculo_recibido_por_url(): void
    {
        $otro = Vehiculo::factory()->create();

        $this->iniciarSesion()
            ->get(route('ordenes.create', ['vehiculo' => $otro->id]))
            ->assertOk()
            ->assertSee('<option value="'.$otro->id.'" selected', false)
            ->assertDontSee('<option value="'.$this->vehiculo->id.'" selected', false);
    }

    public function test_un_envio_vacio_marca_cada_campo_obligatorio(): void
    {
        $this->iniciarSesion()
            ->from(route('ordenes.create'))
            ->post(route('ordenes.store'), [])
            ->assertRedirect(route('ordenes.create'))
            ->assertSessionHasErrors([
                'vehiculo_id' => 'Selecciona un vehículo.',
                'tipo_servicio_id' => 'Selecciona un tipo de servicio.',
                'fecha_ingreso' => 'La fecha de ingreso es obligatoria.',
                'kilometraje_ingreso' => 'El kilometraje de ingreso es obligatorio.',
                'descripcion' => 'La descripción del trabajo es obligatoria.',
            ]);

        $this->assertDatabaseCount('ordenes_servicio', 0);
    }

    public function test_conserva_lo_escrito_al_fallar(): void
    {
        $this->iniciarSesion()
            ->from(route('ordenes.create'))
            ->post(route('ordenes.store'), $this->datosValidos(['vehiculo_id' => null]))
            ->assertSessionHasInput('descripcion', 'Revisión de frenos delanteros');
    }

    public function test_la_entrega_no_puede_ser_anterior_al_ingreso(): void
    {
        $this->iniciarSesion()
            ->post(route('ordenes.store'), $this->datosValidos([
                'fecha_ingreso' => now()->subDays(2)->toDateString(),
                'fecha_entrega' => now()->subDays(5)->toDateString(),
            ]))
            ->assertSessionHasErrors([
                'fecha_entrega' => 'La fecha de entrega no puede ser anterior a la fecha de ingreso.',
            ]);

        $this->assertDatabaseCount('ordenes_servicio', 0);
    }

    public function test_el_ingreso_no_puede_ser_futuro(): void
    {
        $this->iniciarSesion()
            ->post(route('ordenes.store'), $this->datosValidos([
                'fecha_ingreso' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('fecha_ingreso');
    }

    public function test_rechaza_vehiculo_y_servicio_inexistentes(): void
    {
        $this->iniciarSesion()
            ->post(route('ordenes.store'), $this->datosValidos([
                'vehiculo_id' => 999999,
                'tipo_servicio_id' => 999999,
            ]))
            ->assertSessionHasErrors(['vehiculo_id', 'tipo_servicio_id']);
    }

    public function test_datos_validos_crean_la_orden_pendiente(): void
    {
        $respuesta = $this->iniciarSesion()
            ->post(route('ordenes.store'), $this->datosValidos([
                'fecha_entrega' => now()->toDateString(),
                'estado' => 'finalizada',
            ]));

        $orden = OrdenServicio::sole();

        $respuesta->assertRedirect(route('ordenes.show', $orden))
            ->assertSessionHas('exito', 'Orden registrada correctamente.');

        // El estado nunca viene del formulario: toda orden nueva empieza pendiente.
        $this->assertSame('pendiente', $orden->estado);
        $this->assertSame($this->vehiculo->id, $orden->vehiculo_id);
        $this->assertSame(52000, $orden->kilometraje_ingreso);
        $this->assertSame('Revisión de frenos delanteros', $orden->descripcion);
    }
}
