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
    public function show(TipoServicio $servicio) // [CRUD-READ]
    {
        $servicio->loadCount('ordenes')
            ->loadSum(
                ['ordenes as ingresos' => fn ($consulta) => $consulta->estado('finalizada')],
                'costo_total'
            );

        $ordenes = $servicio->ordenes()
            ->with('vehiculo')
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('servicios.show', compact('servicio', 'ordenes'));
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

    public function destroy(TipoServicio $servicio) // [CRUD-DELETE]
    {
        if ($servicio->ordenes()->exists()) {
            return redirect()
                ->route('servicios.index')
                ->with('error', 'No se puede eliminar el servicio porque tiene órdenes registradas.');
        }

        $servicio->delete();

        return redirect()
            ->route('servicios.index')
            ->with('exito', 'Servicio eliminado correctamente.');
    }
}
