# Contratos de la Fase 2

Interfaz común del equipo para trabajar en paralelo sin esperas. La fija Carlos en C1 a C6. Después del sábado 14 de noviembre a las 17:00 no se renombra ninguna ruta, parámetro, método ni clase de esta página: si hace falta algo nuevo, se agrega.

- `RutasContratoTest` comprueba el mapa de rutas; `ModeloOrdenTest`, `ModeloVehiculoTest` y `FabricasTest` comprueban los métodos y las fábricas.
- Los controladores esqueleto responden `501` con el commit y el dueño pendientes (por ejemplo `Pendiente: J4 (Jorge)`). Cada dueño reemplaza el `abort(501)` en su commit. Puede cambiar `Request` por su Form Request, pero no el nombre del método ni el del parámetro.
- Las rutas de escritura están en `Route::group([], ...)`; Ángel agrega `->middleware('auth')` en A4. Las pruebas de escritura usan `$this->iniciarSesion()` desde el primer día.
- Los parámetros de ruta son `{vehiculo}`, `{orden}` y `{servicio}`, así que el route model binding usa `Vehiculo $vehiculo`, `OrdenServicio $orden` y `TipoServicio $servicio`.
- `POST /login` no tiene nombre propio: el formulario envía a `route('login')`. El panel conserva el nombre de Fase 1, `inicio`.

## Mapa de rutas

Orden de registro en routes/web.php: primero `login` (invitado), después el grupo de escritura y al final las rutas públicas de lectura, para que `/vehiculos/create` no caiga en `/vehiculos/{vehiculo}`.

| Nombre | Método y URI | Controlador | Dueño | Acceso |
| --- | --- | --- | --- | --- |
| vehiculos.index / vehiculos.show | GET /vehiculos, GET /vehiculos/{vehiculo} | VehiculoController@index, @show | Jorge | Pública |
| vehiculos.create / store | GET /vehiculos/create, POST /vehiculos | VehiculoController@create, @store | Jorge | Sesión |
| vehiculos.edit / update | GET /vehiculos/{vehiculo}/edit, PUT /vehiculos/{vehiculo} | VehiculoController@edit, @update | Jorge | Sesión |
| vehiculos.destroy | DELETE /vehiculos/{vehiculo} | VehiculoController@destroy | Jorge | Sesión |
| ordenes.index / ordenes.show | GET /ordenes, GET /ordenes/{orden} | OrdenServicioController@index, @show | Leo | Pública |
| ordenes.create / store | GET /ordenes/create (acepta ?vehiculo=ID), POST /ordenes | OrdenServicioController@create, @store | Leo | Sesión |
| ordenes.edit / update / destroy | GET /ordenes/{orden}/edit, PUT y DELETE /ordenes/{orden} | OrdenServicioController@edit, @update, @destroy | Leo | Sesión |
| ordenes.estado | PATCH /ordenes/{orden}/estado | OrdenServicioController@cambiarEstado | Leo | Sesión |
| servicios.index / servicios.show | GET /servicios, GET /servicios/{servicio} | TipoServicioController@index, @show | Fernando | Pública |
| servicios.create / store / edit / update / destroy | GET /servicios/create, POST /servicios, GET /servicios/{servicio}/edit, PUT y DELETE /servicios/{servicio} | TipoServicioController | Fernando | Sesión |
| reportes.index / historial / exportar | GET /reportes, GET /reportes/historial, GET /reportes/exportar | ReporteController@index, @historial, @exportar | Carlos | Pública |
| login / logout | GET y POST /login, POST /logout | Auth\SesionController@create, @store, @destroy | Ángel | Invitado / sesión |
| perfil.contrasena.edit / update | GET y PUT /perfil/contrasena | Auth\ContrasenaController@edit, @update | Ángel | Sesión |
| Panel (nombre de Fase 1 sin cambios) | GET / | PanelController | Milton | Pública |

## Métodos y piezas compartidas

| Firma | Para qué | Commit | Quién lo usa |
| --- | --- | --- | --- |
| `OrdenServicio::ESTADOS`, `TRANSICIONES` | Estados válidos y la única transición permitida desde cada uno | C3 | Leo (L1, L7), Carlos |
| `scopeEstado($estado)`, `scopePendientes()`, `scopeDelPeriodo(?$desde, ?$hasta)` | Filtros de órdenes con parámetros enlazados | C3 | Carlos (reporte), Leo (L9), Milton (M6), Fernando (F8) |
| `estaFinalizada(): bool`, `puedeEditarse(): bool`, `siguienteEstado(): ?string` | Regla 5 y transiciones | C3 | Leo (L6, L7) |
| `Vehiculo::ultimoKilometraje(?int $ignorarOrdenId = null): int` | Regla 3 | C4 | Fernando (F2), Jorge (J8), Carlos (C9) |
| `Vehiculo::totalGastado(): float` | Suma de órdenes finalizadas | C4 | Jorge (J8), Carlos (C9) |
| Fábricas: `pendiente()`, `enProceso()`, `finalizada()`, `conKilometraje(n)`, TipoServicioFactory | Datos de prueba coherentes con las reglas | C5 | Todos |
| `$this->iniciarSesion()` | Usuario autenticado en pruebas | C1 | Todos |
| Fórmula del costo | costo_total = precio_base + costo_adicional, calculado en el backend (Leo, L4) | C2 | Leo, Carlos (fábricas y reporte) |
| `ImagenVehiculoService::guardar()`, `reemplazar()`, `eliminar()`, `conImagen()` | Archivos en el disco public | F1, F3 | Jorge (J4, J5), Fernando |
| `new KilometrajeNoMenorAlUltimo(?int $vehiculoId, ?int $ignorarOrdenId = null)` | Regla 3 como objeto Rule | F2 | Leo (L5, L6), Jorge (J7) |
| `VehiculoObserver::deleted` | Borra la foto al eliminar un vehículo | F4 | Jorge (J6) |

## Contrato de marcado HTML

| Elemento | Marcado |
| --- | --- |
| Campo de formulario | `<div class="campo"><label for="placa">Placa</label><input id="placa" name="placa" value="{{ old('placa', $vehiculo->placa ?? '') }}" @error('placa') aria-invalid="true" aria-describedby="placa-error" @enderror> @error('placa') <p class="error-campo" id="placa-error">{{ $message }}</p> @enderror</div>` |
| Select | Igual que el campo, con `<select id="..." name="...">` y `@selected(old('vehiculo_id', $valor) == $opcion->id)`. |
| Eliminación | `<form method="POST" action="..." data-confirmar="¿Eliminar el vehículo P123456?" onsubmit="return confirm(this.dataset.confirmar)">@csrf @method('DELETE')<button class="boton boton-peligro">Eliminar</button></form>` |
| Imagen | `<input type="file" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp" data-vista-previa="vista-imagen">` y `<img id="vista-imagen" src="{{ $vehiculo->imagen_url ?? asset('img/vehiculo-default.svg') }}" alt="Foto del vehículo">` |
| Tabla | `<table class="tabla">` y cada celda con `<td data-label="Placa">`. |
| Mensajes flash | En el controlador: `->with('exito', '...')` o `->with('error', '...')`. El layout los muestra; las vistas no los repiten. |
| Estado de orden | `<span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">` (clases de Fase 1: `etiqueta-pendiente`, `etiqueta-en-proceso`, `etiqueta-finalizada`; el estado `en_proceso` lleva guion en la clase). |
| Estilos | Solo las clases de estilos.css de Fase 1 y las de esta tabla. Nadie más que Milton edita estilos.css o biela.js. |
