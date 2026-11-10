<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    // [CRUD-READ] Listado de vehículos, ordenado por placa y paginado de 10 en 10.
    public function index(Request $request)
    {
        $vehiculos = Vehiculo::orderBy('placa')->paginate(10);

        return view('vehiculos.index', [
            'vehiculos' => $vehiculos,
        ]);
    }
}
