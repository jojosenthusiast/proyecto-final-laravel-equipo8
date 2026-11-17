<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrdenServicioRequest;
use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

// Esqueleto fijado en C6: las firmas son contrato. Leo reemplaza cada abort(501) en su commit
// y puede cambiar Request por su Form Request sin cambiar el nombre del método ni del parámetro.
class OrdenServicioController extends Controller
{
    /**
     * Lista las órdenes de servicio, paginadas y filtrables por estado y tipo de servicio.
     */
    public function index(Request $request) // [CRUD-READ]
    {
        // [SEGURIDAD] Solo se filtra por un estado que exista en el modelo; cualquier otro valor
        // se ignora en lugar de llegar a la consulta.
        $estado = $request->query('estado');
        $estado = in_array($estado, OrdenServicio::ESTADOS, true) ? $estado : null;

        $tipo = filter_var($request->query('tipo'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;

        // Eager loading de vehículo y servicio: la vista los muestra en cada fila y
        // preventLazyLoading rompe la página ante una consulta N+1.
        $ordenes = OrdenServicio::with(['vehiculo', 'tipoServicio'])
            ->when($estado, fn ($consulta, string $valor) => $consulta->estado($valor))
            ->when($tipo, fn ($consulta, int $valor) => $consulta->where('tipo_servicio_id', $valor))
            ->latest('fecha_ingreso')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $tiposServicio = TipoServicio::orderBy('nombre')->get(['id', 'nombre']);

        return view('ordenes.index', compact('ordenes', 'tiposServicio', 'estado', 'tipo'));
    }

    /**
     * Muestra una orden con su vehículo, su servicio y el desglose del costo.
     */
    public function show(OrdenServicio $orden) // [CRUD-READ] [INYECCION-DEPENDENCIAS]
    {
        // Route model binding: Laravel busca la orden por {orden} y responde 404 si no existe.
        $orden->load(['vehiculo', 'tipoServicio']);

        return view('ordenes.show', compact('orden'));
    }

    // Acepta ?vehiculo=ID para preseleccionar el vehículo desde su ficha.
    public function create(Request $request) // [CRUD-CREATE]
    {
        $orden = new OrdenServicio([
            // Selección desde la entidad principal: la ficha del vehículo enlaza con ?vehiculo=ID.
            'vehiculo_id' => filter_var($request->query('vehiculo'), FILTER_VALIDATE_INT) ?: null,
            'fecha_ingreso' => today(),
        ]);

        return view('ordenes.create', array_merge(compact('orden'), $this->opcionesFormulario()));
    }

    public function store(StoreOrdenServicioRequest $request) // [CRUD-CREATE]
    {
        // [SEGURIDAD] Solo los campos validados; costo_total y estado no vienen del formulario.
        $datos = $request->validated();

        $orden = new OrdenServicio($datos);
        $orden->estado = 'pendiente';
        $orden->costo_adicional = $datos['costo_adicional'] ?? 0;

        // [REGLA-NEGOCIO] costo_total = precio base del servicio + costo adicional.
        $precioBase = (float) TipoServicio::whereKey($orden->tipo_servicio_id)->value('precio_base');
        $orden->costo_total = round($precioBase + (float) $orden->costo_adicional, 2);

        $orden->save();

        return redirect()
            ->route('ordenes.show', $orden)
            ->with('exito', 'Orden registrada correctamente.');
    }

    public function edit(OrdenServicio $orden)
    {
        abort(501, 'Pendiente: L6 (Leo)');
    }

    public function update(Request $request, OrdenServicio $orden)
    {
        abort(501, 'Pendiente: L6 (Leo)');
    }

    public function cambiarEstado(Request $request, OrdenServicio $orden)
    {
        abort(501, 'Pendiente: L7 (Leo)');
    }

    public function destroy(OrdenServicio $orden)
    {
        abort(501, 'Pendiente: L8 (Leo)');
    }

    /**
     * Listas desplegables del formulario: vehículos por placa y servicios con su precio.
     */
    private function opcionesFormulario(): array
    {
        return [
            'vehiculos' => Vehiculo::orderBy('placa')->get(['id', 'placa', 'marca', 'modelo']),
            'tiposServicio' => TipoServicio::orderBy('nombre')->get(['id', 'nombre', 'precio_base']),
        ];
    }
}
