<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
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

    public function historial(Request $request)
    {
        abort(501, 'Pendiente: C9 (Carlos)');
    }

    public function exportar(Request $request)
    {
        abort(501, 'Pendiente: C10 (Carlos)');
    }
}
