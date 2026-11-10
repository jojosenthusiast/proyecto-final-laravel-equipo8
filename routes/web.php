<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TipoServicioController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\PanelController;

// Listado de vehículos; Fernando agrega 'show' y en Fase 2 se quita el only().
Route::resource('vehiculos', VehiculoController::class)->only(['index']);

// Catálogo de servicios del taller.
Route::get('/servicios', [TipoServicioController::class, 'index'])
    ->name('servicios.index');

Route::get('/', [PanelController::class, 'index'])
    ->name('inicio');
