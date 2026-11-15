<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\VerificaSeguridad;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;
    use VerificaSeguridad;

    public function test_formulario_login_contiene_csrf(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        $this->assertFormularioConCsrf($response);
    }

    public function test_formulario_logout_contiene_csrf(): void
    {
        $this->iniciarSesion();

        $response = $this->get('/');

        $response->assertOk();

        $response->assertSee(
            'action="' . route('logout') . '"',
            false
        );

        $this->assertFormularioConCsrf($response);
    }

    public function test_html_del_propietario_se_muestra_escapado(): void
    {
        $texto = '<script>alert(1)</script>';

        Vehiculo::factory()->create([
            'propietario_nombre' => $texto,
        ]);

        $response = $this->get('/vehiculos');

        $response->assertOk();

        $this->assertHtmlEscapado($response, $texto);
    }

    public function test_id_no_permite_asignacion_masiva(): void
    {
        $datos = Arr::only(
            Vehiculo::factory()->raw(),
            (new Vehiculo())->getFillable()
        );

        $datos['id'] = 999;

        $vehiculo = Vehiculo::create($datos);

        $this->assertNotEquals(999, $vehiculo->id);

        $this->assertDatabaseMissing('vehiculos', [
            'id' => 999,
        ]);
    }

    public function test_get_no_permite_eliminar_vehiculos(): void
    {
        $this->iniciarSesion();

        $vehiculo = Vehiculo::factory()->create();

        Route::delete(
            '/prueba-seguridad/vehiculos/{vehiculo}',
            function (Vehiculo $vehiculo) {
                $vehiculo->delete();

                return response()->noContent();
            }
        );

        $this->get(
            '/prueba-seguridad/vehiculos/' . $vehiculo->id
        )->assertStatus(405);

        $this->assertDatabaseHas('vehiculos', [
            'id' => $vehiculo->id,
        ]);
    }

    public function test_filtro_marca_resiste_inyeccion_sql(): void
    {
        $primero = Vehiculo::factory()->create([
            'marca' => 'Toyota',
        ]);

        $segundo = Vehiculo::factory()->create([
            'marca' => 'Honda',
        ]);

        $consulta = "' OR 1=1 --";

        $response = $this->get(
            '/vehiculos?' . http_build_query([
                'marca' => $consulta,
            ])
        );

        $response->assertOk();

        $response->assertDontSee($primero->placa);
        $response->assertDontSee($segundo->placa);

        $this->assertDatabaseCount('vehiculos', 2);
    }
}
