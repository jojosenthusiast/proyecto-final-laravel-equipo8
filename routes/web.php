<?php

use App\Http\Controllers\PanelController;
use App\Http\Controllers\TipoServicioController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

// Listado y ficha de vehículos; en Fase 2 se quita el only() para el CRUD completo.
Route::resource('vehiculos', VehiculoController::class)->only(['index', 'show']);

// Catálogo de servicios del taller.
Route::get('/servicios', [TipoServicioController::class, 'index'])
    ->name('servicios.index');

Route::get('/', [PanelController::class, 'index'])
    ->name('inicio');
