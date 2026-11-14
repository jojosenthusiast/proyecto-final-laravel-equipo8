<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    // [SEGURIDAD] Únicas columnas por las que se puede ordenar el listado. orderBy() no enlaza el
    // nombre de la columna como parámetro (solo los valores), así que lo que llega en ?orden= se
    // compara con esta lista antes de tocar la consulta.
    private const COLUMNAS_ORDENABLES = ['placa', 'anio', 'kilometraje'];

    // [CRUD-READ] Listado de vehículos paginado de 10 en 10, por placa salvo que se pida otro orden.
    // withCount cuenta las órdenes en la misma consulta, sin una consulta extra por fila.
    public function index(Request $request)
    {
        $buscar = $this->textoDeConsulta($request, 'buscar');
        $marca = $this->textoDeConsulta($request, 'marca');

        // Un valor fuera de la lista (por ejemplo «placa;drop table vehiculos») se ignora y se
        // ordena por placa, sin error. La dirección solo puede ser asc o desc.
        $orden = $this->textoDeConsulta($request, 'orden');
        $orden = in_array($orden, self::COLUMNAS_ORDENABLES, true) ? $orden : 'placa';
        $direccion = $this->textoDeConsulta($request, 'direccion') === 'desc' ? 'desc' : 'asc';

        // La marca y el texto viajan como parámetros enlazados, nunca concatenados en el SQL.
        // Los dos filtros se combinan con AND. withQueryString() hace que la página 2 de un
        // listado filtrado conserve la búsqueda, la marca y el orden.
        $vehiculos = Vehiculo::withCount('ordenes')
            ->buscar($buscar)
            ->when($marca, fn ($consulta, $marca) => $consulta->where('marca', $marca))
            ->orderBy($orden, $direccion)
            // Con años o kilometrajes repetidos, la placa desempata: así cada vehículo queda
            // siempre en la misma página.
            ->when($orden !== 'placa', fn ($consulta) => $consulta->orderBy('placa'))
            ->paginate(10)
            ->withQueryString();

        return view('vehiculos.index', [
            'vehiculos' => $vehiculos,
            'buscar' => $buscar,
            'orden' => $orden,
            'direccion' => $direccion,
            // Los enlaces de los encabezados cambian el orden sin perder la búsqueda ni la marca.
            'filtros' => array_filter(['buscar' => $buscar, 'marca' => $marca], fn ($valor) => $valor !== ''),
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
    // La ficha es el centro de acciones del vehículo: desde aquí se registra una orden nueva, se
    // abre cada orden y se edita o elimina el vehículo.
    public function show(Vehiculo $vehiculo)
    {
        // [RELACION] Las órdenes se cargan de la más reciente a la más antigua y con su tipo de
        // servicio en la misma tanda (eager loading), para no consultar una vez por cada fila.
        // Con preventLazyLoading activo, olvidar 'ordenes.tipoServicio' lanzaría una excepción.
        $vehiculo->load([
            'ordenes' => fn ($consulta) => $consulta->latest('fecha_ingreso')->latest('id'),
            'ordenes.tipoServicio',
        ]);

        return view('vehiculos.show', [
            'vehiculo' => $vehiculo,
            // [ENCAPSULAMIENTO] Los cálculos viven en el modelo (C4) y los hace la base de datos:
            // el total suma solo las órdenes finalizadas y el último kilometraje es el mayor entre
            // el del vehículo y el de sus órdenes (regla 3).
            'totalGastado' => $vehiculo->totalGastado(),
            'ultimoKilometraje' => $vehiculo->ultimoKilometraje(),
        ]);
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
