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
        $buscar = $this->textoDeConsulta($request, 'buscar');
        $marca = $this->textoDeConsulta($request, 'marca');

        // La marca y el texto viajan como parámetros enlazados, nunca concatenados en el SQL.
        // Los dos filtros se combinan con AND. withQueryString() hace que la página 2 de un
        // listado filtrado conserve la búsqueda y la marca.
        $vehiculos = Vehiculo::withCount('ordenes')
            ->buscar($buscar)
            ->when($marca, fn ($consulta, $marca) => $consulta->where('marca', $marca))
            ->orderBy('placa')
            ->paginate(10)
            ->withQueryString();

        return view('vehiculos.index', [
            'vehiculos' => $vehiculos,
            'buscar' => $buscar,
            'filtrado' => $buscar !== '' || $marca !== '',
            // Las opciones del filtro salen de la base, no de una lista escrita a mano.
            'marcas' => Vehiculo::select('marca')->distinct()->orderBy('marca')->pluck('marca'),
        ]);
    }

    // [SEGURIDAD] Un filtro de la URL solo se acepta como texto. Si alguien manda ?marca[]=x,
    // se ignora en lugar de llegar como arreglo a la consulta y provocar un error 500.
    private function textoDeConsulta(Request $request, string $clave): string
    {
        $valor = $request->query($clave);

        return is_string($valor) ? trim($valor) : '';
    }

    // [CRUD-READ] Ficha del vehículo. El parámetro Vehiculo es route model binding:
    // Laravel busca el registro por su llave y responde 404 si no existe.
    public function show(Vehiculo $vehiculo)
    {
        // Las órdenes se cargan de la más reciente a la más antigua y con su tipo
        // de servicio en la misma tanda, para no consultar una vez por cada fila.
        $vehiculo->load([
            'ordenes' => fn ($consulta) => $consulta->orderByDesc('fecha_ingreso'),
            'ordenes.tipoServicio',
        ]);

        return view('vehiculos.show', compact('vehiculo'));
    }

    // Esqueletos fijados en C6: las firmas son contrato. Jorge reemplaza cada abort(501) en su
    // commit y puede cambiar Request por su Form Request sin cambiar el nombre del método.
    public function create()
    {
        abort(501, 'Pendiente: J4 (Jorge)');
    }

    public function store(Request $request)
    {
        abort(501, 'Pendiente: J4 (Jorge)');
    }

    public function edit(Vehiculo $vehiculo)
    {
        abort(501, 'Pendiente: J5 (Jorge)');
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        abort(501, 'Pendiente: J5 (Jorge)');
    }

    public function destroy(Vehiculo $vehiculo)
    {
        abort(501, 'Pendiente: J6 (Jorge)');
    }
}
