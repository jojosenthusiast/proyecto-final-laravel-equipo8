<?php

namespace Tests\Feature;

use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\VerificaSeguridad;
use Tests\TestCase;

class RecorridoDefensaTest extends TestCase
{
    use RefreshDatabase;
    use VerificaSeguridad;

    public function test_el_recorrido_de_defensa_con_datos_sembrados(): void
    {
        $this->seed();
        $this->iniciarSesion();

        $cerrado = Vehiculo::where('placa', 'P000001')->firstOrFail();
        $vehiculo = Vehiculo::where('placa', 'P000002')->firstOrFail();
        $ordenFinalizada = $cerrado->ordenes()->estado('finalizada')->firstOrFail();

        foreach ([
            route('inicio'),
            route('vehiculos.show', $vehiculo),
            route('vehiculos.edit', $vehiculo),
            route('reportes.historial', ['vehiculo_id' => $vehiculo->id]),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        foreach (['vehiculos.index', 'ordenes.index', 'reportes.index', 'servicios.index'] as $ruta) {
            $this->get(route($ruta))->assertOk()->assertSee('data-label=', false);
        }

        foreach (['vehiculos.create', 'ordenes.create', 'servicios.create'] as $ruta) {
            $this->assertFormularioConCsrf($this->get(route($ruta))->assertOk());
        }

        $this->get(route('ordenes.edit', $ordenFinalizada))->assertForbidden();
    }
}
