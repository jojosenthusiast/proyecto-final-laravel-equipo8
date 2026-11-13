<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use Illuminate\Http\Request;

// Esqueleto fijado en C6: las firmas son contrato. Leo reemplaza cada abort(501) en su commit
// y puede cambiar Request por su Form Request sin cambiar el nombre del método ni del parámetro.
class OrdenServicioController extends Controller
{
    public function index(Request $request)
    {
        abort(501, 'Pendiente: L1 (Leo)');
    }

    public function show(OrdenServicio $orden)
    {
        abort(501, 'Pendiente: L2 (Leo)');
    }

    // Acepta ?vehiculo=ID para preseleccionar el vehículo desde su ficha.
    public function create(Request $request)
    {
        abort(501, 'Pendiente: L3 (Leo)');
    }

    public function store(Request $request)
    {
        abort(501, 'Pendiente: L3 (Leo)');
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
}
