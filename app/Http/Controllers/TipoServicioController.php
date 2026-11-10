<?php

namespace App\Http\Controllers;

use App\Models\TipoServicio;

class TipoServicioController extends Controller
{
    /**
     * Muestra los servicios disponibles del taller.
     */
    public function index() // [CRUD-READ]
    {
        $tiposServicio = TipoServicio::withCount('ordenes')
            ->orderBy('nombre')
            ->get();

        return view('servicios.index', compact('tiposServicio'));
    }
}
