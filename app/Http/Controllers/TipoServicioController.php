<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoServicioRequest;
use App\Http\Requests\UpdateTipoServicioRequest;
use App\Models\TipoServicio;
use Illuminate\Http\Request;

class TipoServicioController extends Controller
{
    /**
     * Muestra los servicios disponibles del taller, con búsqueda y orden.
     */
    public function index(Request $request) // [CRUD-READ]
    {
        // [SEGURIDAD] Solo se ordena por columnas permitidas y con dirección asc/desc; cualquier
        // otro valor cae en el orden por nombre.
        $orden = $request->query('orden');
        $orden = in_array($orden, ['nombre', 'precio_base', 'ordenes_count'], true)
            ? $orden
            : 'nombre';

        $direccion = $request->query('direccion') === 'desc' ? 'desc' : 'asc';

        $busqueda = $request->query('busqueda');
        $busqueda = is_string($busqueda) ? trim($busqueda) : null;

        $tiposServicio = TipoServicio::withCount('ordenes')
            ->buscar($busqueda)
            ->orderBy($orden, $direccion)
            ->paginate(10)
            ->withQueryString();

        return view('servicios.index', compact('tiposServicio', 'orden', 'direccion'));
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
