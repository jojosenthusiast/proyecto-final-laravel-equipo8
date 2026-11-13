<?php

namespace App\Http\Controllers;

use App\Models\TipoServicio;
use Illuminate\Http\Request;

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

    // Esqueletos fijados en C6: las firmas son contrato. Fernando reemplaza cada abort(501) en su
    // commit y puede cambiar Request por su Form Request sin cambiar el nombre del método.
    public function show(TipoServicio $servicio)
    {
        abort(501, 'Pendiente: F8 (Fernando)');
    }

    public function create()
    {
        abort(501, 'Pendiente: F5 (Fernando)');
    }

    public function store(Request $request)
    {
        abort(501, 'Pendiente: F5 (Fernando)');
    }

    public function edit(TipoServicio $servicio)
    {
        abort(501, 'Pendiente: F5 (Fernando)');
    }

    public function update(Request $request, TipoServicio $servicio)
    {
        abort(501, 'Pendiente: F5 (Fernando)');
    }

    public function destroy(TipoServicio $servicio)
    {
        abort(501, 'Pendiente: F6 (Fernando)');
    }
}
