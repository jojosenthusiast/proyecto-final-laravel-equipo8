<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TipoServicioController;

// Catálogo de servicios del taller.
Route::get('/servicios', [TipoServicioController::class, 'index'])
    ->name('servicios.index');

Route::get('/', function () {
    return view('welcome');
});
