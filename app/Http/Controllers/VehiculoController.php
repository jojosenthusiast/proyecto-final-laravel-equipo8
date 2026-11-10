<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    // [CRUD-READ] Listado de vehículos, ordenado por placa y paginado de 10 en 10.
    // withCount cuenta las órdenes en la misma consulta, sin una consulta extra por fila.
    public function index(Request $request)
    {
        // La marca viaja como parámetro de where(), nunca concatenada en el SQL.
        // withQueryString() hace que la página 2 de un listado filtrado conserve la marca.
        $vehiculos = Vehiculo::withCount('ordenes')
            ->when($request->query('marca'), fn ($consulta, $marca) => $consulta->where('marca', $marca))
            ->orderBy('placa')
            ->paginate(10)
            ->withQueryString();

        return view('vehiculos.index', [
            'vehiculos' => $vehiculos,
            // Las opciones del filtro salen de la base, no de una lista escrita a mano.
            'marcas' => Vehiculo::select('marca')->distinct()->orderBy('marca')->pluck('marca'),
        ]);
    }
}
