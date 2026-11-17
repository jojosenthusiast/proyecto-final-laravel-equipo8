<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(): View
    {
        // [CRUD-READ] Agrupa las órdenes en una consulta, incluso cuando un estado está vacío.
        $conteos = OrdenServicio::query()
            ->select('estado')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $ordenesPorEstado = [];
        foreach (OrdenServicio::ESTADOS as $estado) {
            $ordenesPorEstado[$estado] = (int) $conteos->get($estado, 0);
        }

        $totalOrdenes = array_sum($ordenesPorEstado);
        $porcentajes = array_map(
            fn (int $cantidad): int => $totalOrdenes ? (int) round($cantidad * 100 / $totalOrdenes) : 0,
            $ordenesPorEstado,
        );

        $hoy = now();
        $finalizadasDelMes = OrdenServicio::query()->estado('finalizada')
            ->delPeriodo($hoy->copy()->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString());

        // Los dos totales se obtienen juntos y el filtro mensual reutiliza el scope del reporte.
        $totales = DB::query()
            ->selectSub(Vehiculo::query()->selectRaw('COUNT(*)'), 'vehiculos')
            ->selectSub($finalizadasDelMes->selectRaw('COALESCE(SUM(costo_total), 0)'), 'ingresos')
            ->first();

        $ordenAntigua = OrdenServicio::query()->pendientes()
            ->with('vehiculo')
            ->oldest('fecha_ingreso')
            ->orderBy('id')
            ->first();

        $tiposServicio = TipoServicio::withCount('ordenes')
            ->orderByDesc('ordenes_count')
            ->orderBy('nombre')
            ->get();

        $ultimasOrdenes = DB::table('ordenes_servicio as orden')
            ->join('vehiculos as vehiculo', 'orden.vehiculo_id', '=', 'vehiculo.id')
            ->join('tipos_servicio as servicio', 'orden.tipo_servicio_id', '=', 'servicio.id')
            ->select('orden.id', 'orden.vehiculo_id', 'orden.estado', 'orden.fecha_ingreso', 'vehiculo.placa', 'servicio.nombre as servicio')
            ->orderByDesc('orden.fecha_ingreso')
            ->orderByDesc('orden.id')
            ->limit(5)
            ->get();

        return view('panel.index', [
            'totalVehiculos' => (int) $totales->vehiculos,
            'ingresosMes' => (float) $totales->ingresos,
            'totalOrdenes' => $totalOrdenes,
            'ordenesPorEstado' => $ordenesPorEstado,
            'porcentajes' => $porcentajes,
            'ordenAntigua' => $ordenAntigua,
            'tiposServicio' => $tiposServicio,
            'ultimasOrdenes' => $ultimasOrdenes,
        ]);
    }
}
