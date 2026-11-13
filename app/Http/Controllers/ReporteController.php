<?php

namespace App\Http\Controllers;

use App\Http\Requests\HistorialVehiculoRequest;
use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

// Reporte del caso (Carlos): pendientes e ingresos (C8), historial por vehículo (C9)
// y filtro por fechas con exportación a CSV (C10).
class ReporteController extends Controller
{
    // [CRUD-READ] Página del reporte. Las sumas y los conteos los calcula la base de datos.
    public function index(Request $request)
    {
        // Órdenes que siguen en el taller, de la más antigua a la más reciente.
        // with() carga vehículo y servicio en la misma tanda, sin una consulta por fila.
        $pendientes = OrdenServicio::pendientes()
            ->with(['vehiculo', 'tipoServicio'])
            ->orderBy('fecha_ingreso')
            ->orderBy('id')
            ->get()
            ->each(fn (OrdenServicio $orden) => $orden->setAttribute(
                'dias_en_taller',
                (int) $orden->fecha_ingreso->diffInDays(today())
            ));

        // Ingresos por tipo de servicio: solo cuentan las órdenes finalizadas.
        $ingresos = TipoServicio::query()
            ->withCount(['ordenes as ordenes_finalizadas' => fn ($q) => $q->estado('finalizada')])
            ->withSum(['ordenes as ingresos' => fn ($q) => $q->estado('finalizada')], 'costo_total')
            ->orderByDesc('ingresos')
            ->orderBy('nombre')
            ->get();

        $totalGeneral = (float) OrdenServicio::estado('finalizada')->sum('costo_total');

        return view('reportes.index', compact('pendientes', 'ingresos', 'totalGeneral'));
    }

    // [INYECCION-DEPENDENCIAS] Laravel crea y valida HistorialVehiculoRequest antes de llamar al método.
    // [CRUD-READ] Historial de servicios de un vehículo, con total gastado y último kilometraje.
    public function historial(HistorialVehiculoRequest $request)
    {
        $vehiculos = Vehiculo::orderBy('placa')->get(['id', 'placa', 'propietario_nombre']);

        $vehiculo = null;
        $totalGastado = 0.0;
        $ultimoKilometraje = null;

        if ($id = $request->validated('vehiculo_id')) {
            $vehiculo = Vehiculo::findOrFail($id)->load([
                'ordenes' => fn ($q) => $q->orderByDesc('fecha_ingreso')->orderByDesc('id'),
                'ordenes.tipoServicio',
            ]);
            // Métodos de dominio del modelo (C4): una sola fuente de verdad para la ficha y el reporte.
            $totalGastado = $vehiculo->totalGastado();
            $ultimoKilometraje = $vehiculo->ultimoKilometraje();
        }

        return view('reportes.historial', compact('vehiculos', 'vehiculo', 'totalGastado', 'ultimoKilometraje'));
    }

    public function exportar(Request $request)
    {
        abort(501, 'Pendiente: C10 (Carlos)');
    }
}
