<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Reporte del caso (Carlos): pendientes e ingresos (C8), historial por vehículo (C9)
// y filtro por fechas con exportación a CSV (C10).
class ReporteController extends Controller
{
    public function index(Request $request)
    {
        abort(501, 'Pendiente: C8 (Carlos)');
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
