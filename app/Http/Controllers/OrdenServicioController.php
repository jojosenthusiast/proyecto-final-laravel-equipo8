<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarEstadoOrdenRequest;
use App\Http\Requests\StoreOrdenServicioRequest;
use App\Http\Requests\UpdateOrdenServicioRequest;
use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

// Esqueleto fijado en C6: las firmas son contrato. Leo reemplaza cada abort(501) en su commit
// y puede cambiar Request por su Form Request sin cambiar el nombre del método ni del parámetro.
class OrdenServicioController extends Controller
{
    /**
     * Lista las órdenes de servicio, paginadas y filtrables por estado, tipo de servicio, placa
     * y rango de fechas de ingreso.
     */
    public function index(Request $request) // [CRUD-READ]
    {
        // [VALIDACION] La búsqueda llega por GET; las fechas se validan antes de consultar.
        $busqueda = $request->validate([
            'placa' => ['nullable', 'string', 'max:10'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'placa.string' => 'La placa debe ser texto.',
            'placa.max' => 'La placa no puede tener más de 10 caracteres.',
            'desde.date' => 'La fecha inicial no es una fecha válida.',
            'hasta.date' => 'La fecha final no es una fecha válida.',
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial.',
        ]);

        $placa = trim($busqueda['placa'] ?? '') ?: null;
        $desde = $busqueda['desde'] ?? null;
        $hasta = $busqueda['hasta'] ?? null;

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
            // [SEGURIDAD] La placa viaja como parámetro enlazado del LIKE, nunca concatenada al SQL.
            ->when($placa, fn ($consulta, string $valor) => $consulta->whereHas(
                'vehiculo',
                fn ($q) => $q->where('placa', 'like', '%'.$valor.'%')
            ))
            ->delPeriodo($desde, $hasta)
            ->latest('fecha_ingreso')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $tiposServicio = TipoServicio::orderBy('nombre')->get(['id', 'nombre']);

        return view('ordenes.index', compact('ordenes', 'tiposServicio', 'estado', 'tipo', 'placa', 'desde', 'hasta'));
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

        // [REGLA-NEGOCIO] La orden y el kilometraje del vehículo se guardan juntos o no se guarda
        // ninguno: si falla la actualización del vehículo, DB::transaction revierte la orden y el
        // vehículo nunca queda con un kilometraje distinto al de su última orden.
        $orden = DB::transaction(function () use ($datos) {
            $orden = new OrdenServicio($datos);
            $orden->estado = 'pendiente';
            $orden->costo_adicional = $datos['costo_adicional'] ?? 0;

            // [REGLA-NEGOCIO] El total lo calcula el modelo: precio base del servicio + costo adicional.
            $orden->calcularCostoTotal();
            $orden->save();

            $this->actualizarKilometrajeDelVehiculo($orden);

            return $orden;
        });

        return redirect()
            ->route('ordenes.show', $orden)
            ->with('exito', 'Orden registrada correctamente.');
    }

    public function edit(OrdenServicio $orden) // [CRUD-UPDATE]
    {
        // [SEGURIDAD] Regla 5 en el servidor: una orden finalizada responde 403 aunque se
        // escriba la URL a mano.
        Gate::authorize('update', $orden);

        return view('ordenes.edit', array_merge(compact('orden'), $this->opcionesFormulario()));
    }

    public function update(UpdateOrdenServicioRequest $request, OrdenServicio $orden) // [CRUD-UPDATE]
    {
        Gate::authorize('update', $orden);

        $datos = $request->validated();

        // [REGLA-NEGOCIO] Igual que al registrar: la orden y el kilometraje del vehículo se
        // guardan juntos y el total se recalcula, porque pudo cambiar el servicio o el adicional.
        DB::transaction(function () use ($orden, $datos) {
            $orden->fill($datos);
            $orden->costo_adicional = $datos['costo_adicional'] ?? 0;
            $orden->load('tipoServicio');
            $orden->calcularCostoTotal();
            $orden->save();

            $this->actualizarKilometrajeDelVehiculo($orden);
        });

        return redirect()
            ->route('ordenes.show', $orden)
            ->with('exito', 'Orden actualizada correctamente.');
    }

    public function cambiarEstado(CambiarEstadoOrdenRequest $request, OrdenServicio $orden) // [CRUD-UPDATE]
    {
        // El request ya comprobó que el nuevo estado es exactamente $orden->siguienteEstado().
        $orden->estado = $request->validated('estado');

        // [REGLA-NEGOCIO] Al finalizar se registra la entrega; si ya tenía fecha, se conserva
        // (el request comprobó que no es anterior al ingreso, regla 4).
        if ($orden->estaFinalizada() && $orden->fecha_entrega === null) {
            $orden->fecha_entrega = today();
        }

        $orden->save();

        return redirect()
            ->route('ordenes.show', $orden)
            ->with('exito', 'La orden pasó a '.CambiarEstadoOrdenRequest::NOMBRES[$orden->estado].'.');
    }

    public function destroy(OrdenServicio $orden) // [CRUD-DELETE]
    {
        // [SEGURIDAD] La política solo permite borrar órdenes pendientes; el resto responde 403.
        Gate::authorize('delete', $orden);

        $numero = $orden->id;
        $orden->delete();

        return redirect()
            ->route('ordenes.index')
            ->with('exito', "Orden #{$numero} eliminada correctamente.");
    }

    /**
     * Sube el kilometraje del vehículo si la orden registró uno mayor; nunca lo baja.
     */
    private function actualizarKilometrajeDelVehiculo(OrdenServicio $orden): void
    {
        $vehiculo = Vehiculo::findOrFail($orden->vehiculo_id);

        if ($orden->kilometraje_ingreso > $vehiculo->kilometraje) {
            $vehiculo->update(['kilometraje' => $orden->kilometraje_ingreso]);
        }
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
