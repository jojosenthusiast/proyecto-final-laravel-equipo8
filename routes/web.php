<?php

use App\Http\Controllers\Auth\ContrasenaController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\OrdenServicioController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TipoServicioController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

// Mapa de rutas de la Fase 2. Es un contrato del equipo (docs/CONTRATOS-FASE2.md):
// no se renombran rutas ni parámetros. El orden importa: las rutas de escritura van antes
// que las de lectura para que /vehiculos/create no caiga en /vehiculos/{vehiculo}.

// 1. Inicio de sesión, solo para invitados (Ángel, A2).
Route::get('/login', [SesionController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [SesionController::class, 'store'])->middleware('guest');

// 2. Rutas de escritura: crear, editar, eliminar, cambiar estado, salir y cambiar la contraseña.
// [SEGURIDAD] Ángel agrega ->middleware('auth') en A4
Route::group([], function () {
    Route::resource('vehiculos', VehiculoController::class)
        ->except(['index', 'show']);

    Route::resource('ordenes', OrdenServicioController::class)
        ->except(['index', 'show'])
        ->parameters(['ordenes' => 'orden']);
    Route::patch('/ordenes/{orden}/estado', [OrdenServicioController::class, 'cambiarEstado'])
        ->name('ordenes.estado');

    Route::resource('servicios', TipoServicioController::class)
        ->except(['index', 'show'])
        ->parameters(['servicios' => 'servicio']);

    Route::post('/logout', [SesionController::class, 'destroy'])->name('logout');
    Route::get('/perfil/contrasena', [ContrasenaController::class, 'edit'])->name('perfil.contrasena.edit');
    Route::put('/perfil/contrasena', [ContrasenaController::class, 'update'])->name('perfil.contrasena.update');
});

// 3. Rutas públicas de lectura.
Route::resource('vehiculos', VehiculoController::class)->only(['index', 'show']);

Route::resource('ordenes', OrdenServicioController::class)
    ->only(['index', 'show'])
    ->parameters(['ordenes' => 'orden']);

Route::resource('servicios', TipoServicioController::class)
    ->only(['index', 'show'])
    ->parameters(['servicios' => 'servicio']);

Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
Route::get('/reportes/historial', [ReporteController::class, 'historial'])->name('reportes.historial');
Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');

Route::get('/', [PanelController::class, 'index'])
    ->name('inicio');
