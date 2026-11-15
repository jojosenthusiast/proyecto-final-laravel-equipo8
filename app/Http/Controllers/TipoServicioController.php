<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoServicioRequest;
use App\Http\Requests\UpdateTipoServicioRequest;
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

    // Esqueletos fijados en C6: las firmas son contrato. Fernando reemplaza cada abort(501) en su
    // commit y puede cambiar Request por su Form Request sin cambiar el nombre del método.
    public function show(TipoServicio $servicio)
    {
        abort(501, 'Pendiente: F8 (Fernando)');
    }

    public function create() // [CRUD-CREATE]
    {
        return view('servicios.create');
    }

    public function store(StoreTipoServicioRequest $request) // [CRUD-CREATE]
    {
        $tipo = TipoServicio::create($request->validated());

        return redirect()
            ->route('servicios.index')
            ->with('exito', "Servicio «{$tipo->nombre}» registrado correctamente.");
    }

    public function edit(TipoServicio $servicio) // [CRUD-UPDATE]
    {
        return view('servicios.edit', compact('servicio'));
    }

    public function update(UpdateTipoServicioRequest $request, TipoServicio $servicio) // [CRUD-UPDATE]
    {
        $servicio->update($request->validated());

        return redirect()
            ->route('servicios.index')
            ->with('exito', "Servicio «{$servicio->nombre}» actualizado correctamente.");
    }

    public function destroy(TipoServicio $servicio)
    {
        abort(501, 'Pendiente: F6 (Fernando)');
    }
}
