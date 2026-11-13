<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Mapa de rutas de la Fase 2 (docs/CONTRATOS-FASE2.md). Si alguien renombra una ruta, cambia
// su método o su URI, esta prueba falla y avisa antes de romper el trabajo de otro módulo.
class RutasContratoTest extends TestCase
{
    use RefreshDatabase;

    public static function rutasDelContrato(): array
    {
        return [
            // Vehículos (Jorge)
            ['vehiculos.index', 'GET', 'vehiculos', 'VehiculoController@index'],
            ['vehiculos.show', 'GET', 'vehiculos/{vehiculo}', 'VehiculoController@show'],
            ['vehiculos.create', 'GET', 'vehiculos/create', 'VehiculoController@create'],
            ['vehiculos.store', 'POST', 'vehiculos', 'VehiculoController@store'],
            ['vehiculos.edit', 'GET', 'vehiculos/{vehiculo}/edit', 'VehiculoController@edit'],
            ['vehiculos.update', 'PUT', 'vehiculos/{vehiculo}', 'VehiculoController@update'],
            ['vehiculos.destroy', 'DELETE', 'vehiculos/{vehiculo}', 'VehiculoController@destroy'],
            // Órdenes (Leo)
            ['ordenes.index', 'GET', 'ordenes', 'OrdenServicioController@index'],
            ['ordenes.show', 'GET', 'ordenes/{orden}', 'OrdenServicioController@show'],
            ['ordenes.create', 'GET', 'ordenes/create', 'OrdenServicioController@create'],
            ['ordenes.store', 'POST', 'ordenes', 'OrdenServicioController@store'],
            ['ordenes.edit', 'GET', 'ordenes/{orden}/edit', 'OrdenServicioController@edit'],
            ['ordenes.update', 'PUT', 'ordenes/{orden}', 'OrdenServicioController@update'],
            ['ordenes.destroy', 'DELETE', 'ordenes/{orden}', 'OrdenServicioController@destroy'],
            ['ordenes.estado', 'PATCH', 'ordenes/{orden}/estado', 'OrdenServicioController@cambiarEstado'],
            // Servicios (Fernando)
            ['servicios.index', 'GET', 'servicios', 'TipoServicioController@index'],
            ['servicios.show', 'GET', 'servicios/{servicio}', 'TipoServicioController@show'],
            ['servicios.create', 'GET', 'servicios/create', 'TipoServicioController@create'],
            ['servicios.store', 'POST', 'servicios', 'TipoServicioController@store'],
            ['servicios.edit', 'GET', 'servicios/{servicio}/edit', 'TipoServicioController@edit'],
            ['servicios.update', 'PUT', 'servicios/{servicio}', 'TipoServicioController@update'],
            ['servicios.destroy', 'DELETE', 'servicios/{servicio}', 'TipoServicioController@destroy'],
            // Reporte (Carlos)
            ['reportes.index', 'GET', 'reportes', 'ReporteController@index'],
            ['reportes.historial', 'GET', 'reportes/historial', 'ReporteController@historial'],
            ['reportes.exportar', 'GET', 'reportes/exportar', 'ReporteController@exportar'],
            // Sesión y contraseña (Ángel)
            ['login', 'GET', 'login', 'Auth\SesionController@create'],
            ['logout', 'POST', 'logout', 'Auth\SesionController@destroy'],
            ['perfil.contrasena.edit', 'GET', 'perfil/contrasena', 'Auth\ContrasenaController@edit'],
            ['perfil.contrasena.update', 'PUT', 'perfil/contrasena', 'Auth\ContrasenaController@update'],
            // Panel (nombre de Fase 1 sin cambios)
            ['inicio', 'GET', '/', 'PanelController@index'],
        ];
    }

    #[DataProvider('rutasDelContrato')]
    public function test_cada_ruta_del_contrato_existe_con_su_metodo_uri_y_controlador(
        string $nombre,
        string $metodo,
        string $uri,
        string $accion
    ): void {
        $ruta = Route::getRoutes()->getByName($nombre);

        $this->assertNotNull($ruta, "Falta la ruta {$nombre}.");
        $this->assertContains($metodo, $ruta->methods(), "{$nombre} no acepta {$metodo}.");
        $this->assertSame($uri, $ruta->uri());
        $this->assertSame('App\Http\Controllers\\'.$accion, $ruta->getActionName());
    }

    public function test_el_login_por_post_va_al_mismo_controlador_y_solo_para_invitados(): void
    {
        $ruta = Route::getRoutes()->match(request()->create('/login', 'POST'));

        $this->assertSame('App\Http\Controllers\Auth\SesionController@store', $ruta->getActionName());
        $this->assertContains('guest', $ruta->gatherMiddleware());
        $this->assertContains('guest', Route::getRoutes()->getByName('login')->gatherMiddleware());
    }

    public function test_crear_vehiculo_no_cae_en_la_ficha_y_no_responde_404(): void
    {
        // Si vehiculos/{vehiculo} se registrara antes, "create" se tomaría como id y daría 404.
        $this->iniciarSesion()->get('/vehiculos/create')->assertStatus(501);
        $this->iniciarSesion()->get('/ordenes/create')->assertStatus(501);
        $this->iniciarSesion()->get('/servicios/create')->assertStatus(501);
    }

    public function test_el_menu_enlaza_ordenes_servicios_y_reportes(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee(route('ordenes.index'), false)
            ->assertSee(route('servicios.index'), false)
            ->assertSee(route('reportes.index'), false);
    }
}
