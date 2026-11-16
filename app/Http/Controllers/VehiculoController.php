<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehiculoRequest;
use App\Http\Requests\UpdateVehiculoRequest;
use App\Models\Vehiculo;
use App\Services\ImagenVehiculoService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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

    // [CRUD-CREATE] Formulario de registro. La ruta está protegida con auth: sin sesión, Laravel
    // redirige al inicio de sesión antes de llegar aquí.
    public function create()
    {
        return view('vehiculos.create');
    }

    // [CRUD-CREATE] Registra el vehículo. StoreVehiculoRequest valida antes de entrar al método:
    // si algo falla, Laravel regresa al formulario con los errores y old() con lo escrito.
    // [INYECCION-DEPENDENCIAS] Laravel crea el ImagenVehiculoService y lo pasa como parámetro.
    public function store(StoreVehiculoRequest $request, ImagenVehiculoService $imagenes)
    {
        $datos = $this->datosSinImagen($request);

        // conImagen() guarda la foto (si llegó) y crea el vehículo en una transacción. Si el
        // insert falla, borra la foto recién subida: no queda un archivo sin vehículo.
        // La base de datos guarda solo la ruta de la foto («vehiculos/abc123.png»), no el archivo.
        $vehiculo = $imagenes->conImagen(
            $request->file('imagen'),
            fn (?string $ruta) => Vehiculo::create($ruta ? [...$datos, 'imagen' => $ruta] : $datos),
        );

        return redirect()->route('vehiculos.show', $vehiculo)
            ->with('exito', 'Vehículo registrado correctamente.');
    }

    // [CRUD-UPDATE] Formulario de edición con los datos actuales y la foto actual (imagen_url).
    public function edit(Vehiculo $vehiculo)
    {
        return view('vehiculos.edit', ['vehiculo' => $vehiculo]);
    }

    // [CRUD-UPDATE] Guarda los cambios. UpdateVehiculoRequest valida con la placa única que ignora
    // al propio vehículo, así guardar sin cambiar la placa no da «placa repetida».
    public function update(UpdateVehiculoRequest $request, Vehiculo $vehiculo, ImagenVehiculoService $imagenes)
    {
        $datos = $this->datosSinImagen($request);

        // Reemplazo seguro de la foto: conImagen() guarda la nueva, actualiza la fila en una
        // transacción y solo entonces borra la anterior. Si la actualización falla, borra la
        // nueva y la anterior sigue intacta. Sin archivo nuevo no se borra nada.
        $imagenes->conImagen(
            $request->file('imagen'),
            fn (?string $ruta) => $vehiculo->update($ruta ? [...$datos, 'imagen' => $ruta] : $datos),
            $request->hasFile('imagen') ? $vehiculo->imagen : null,
        );

        return redirect()->route('vehiculos.show', $vehiculo)
            ->with('exito', 'Vehículo actualizado correctamente.');
    }

    // Los datos validados sin la clave 'imagen': ahí llega el archivo subido (o null si el campo
    // vino vacío), no la ruta. Si quedara, editar sin elegir foto borraría la ruta guardada.
    // La ruta solo se agrega cuando conImagen() ya guardó un archivo nuevo.
    private function datosSinImagen(StoreVehiculoRequest $request): array
    {
        return Arr::except($request->validated(), 'imagen');
    }

    // [CRUD-DELETE] Elimina el vehículo. Solo llega por DELETE (formulario con @method('DELETE')
    // y @csrf): un enlace o una visita por GET a la misma URL abre la ficha y no borra nada.
    public function destroy(Vehiculo $vehiculo)
    {
        // [REGLA-NEGOCIO] La llave foránea de ordenes_servicio usa restrictOnDelete: un vehículo
        // con órdenes no se puede borrar sin perder su historial. Se revisa antes para responder
        // con un mensaje claro en lugar de dejar que la base lance un error 500.
        $cantidad = $vehiculo->ordenes()->count();

        if ($cantidad > 0) {
            return $this->noSePuedeEliminar($vehiculo, $cantidad);
        }

        // Si alguien registra una orden justo entre la revisión y el borrado, la base rechaza el
        // DELETE por la llave foránea; se responde con el mismo mensaje y sin error 500.
        try {
            // La foto no se borra aquí: el observer de Vehiculo (F4) la elimina después del borrado.
            $vehiculo->delete();
        } catch (QueryException) {
            return $this->noSePuedeEliminar($vehiculo, $vehiculo->ordenes()->count());
        }

        // Siempre al listado: la ficha del vehículo borrado ya no existe.
        return redirect()->route('vehiculos.index')
            ->with('exito', 'Vehículo eliminado correctamente.');
    }

    // Regresa a la página desde donde se pidió el borrado (listado o ficha) con el motivo.
    private function noSePuedeEliminar(Vehiculo $vehiculo, int $cantidad)
    {
        $ordenes = $cantidad === 1 ? '1 orden registrada' : "{$cantidad} órdenes registradas";

        return redirect()->back(fallback: route('vehiculos.show', $vehiculo))
            ->with('error', "No se puede eliminar: el vehículo tiene {$ordenes}.");
    }
}
