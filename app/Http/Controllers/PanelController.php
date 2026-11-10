<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;

class PanelController extends Controller
{
    /**
     * Obtiene los indicadores y movimientos del taller.
     */
    public function index()
    {
        // [CRUD-READ] Consultas de lectura mediante Eloquent.

        // Total de vehículos registrados.
        $totalVehiculos = Vehiculo::count();

        // Total de órdenes de servicio.
        $totalOrdenes = OrdenServicio::count();

        // Cantidad de órdenes por estado.
        $ordenesPorEstado = [
            'pendiente' => OrdenServicio::where(
                'estado',
                'pendiente'
            )->count(),

            'en_proceso' => OrdenServicio::where(
                'estado',
                'en_proceso'
            )->count(),

            'finalizada' => OrdenServicio::where(
                'estado',
                'finalizada'
            )->count(),
        ];

        // Servicios más solicitados, ordenados por cantidad de órdenes.
        $tiposServicio = TipoServicio::withCount('ordenes')
            ->orderByDesc('ordenes_count')
            ->orderBy('nombre')
            ->get();

        // Cinco órdenes más recientes con sus relaciones cargadas.
        $ultimasOrdenes = OrdenServicio::with([
            'vehiculo',
            'tipoServicio',
        ])
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('panel.index', compact(
            'totalVehiculos',
            'totalOrdenes',
            'ordenesPorEstado',
            'tiposServicio',
            'ultimasOrdenes'
        ));
    }
}
