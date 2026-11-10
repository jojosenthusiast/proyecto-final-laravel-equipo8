<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\Vehiculo;

class PanelController extends Controller
{
    /**
     * Obtiene los indicadores principales del taller.
     */
    public function index()
    {
        // [CRUD-READ] Consultas de lectura con Eloquent.

        $totalVehiculos = Vehiculo::count();

        $totalOrdenes = OrdenServicio::count();

        // Se mantienen los tres estados incluso si alguno no tiene órdenes.
        $ordenesPorEstado = [
            'pendiente' => OrdenServicio::where('estado', 'pendiente')->count(),
            'en_proceso' => OrdenServicio::where('estado', 'en_proceso')->count(),
            'finalizada' => OrdenServicio::where('estado', 'finalizada')->count(),
        ];

        return view('panel.index', compact(
            'totalVehiculos',
            'totalOrdenes',
            'ordenesPorEstado'
        ));
    }
}
