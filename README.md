# Biela

*Fichas de vehículos y órdenes de servicio*

Proyecto final de **Desarrollo Web con Software Libre**, Facultad de Ingeniería y Arquitectura, Universidad Católica de El Salvador (UNICAES).
Caso 8, Taller mecánico. Equipo 8.

| Integrante | Usuario de GitHub |
| --- | --- |
| Carlos | [@jojosenthusiast](https://github.com/jojosenthusiast) |
| Ángel | [@angelcoto-stack](https://github.com/angelcoto-stack) |
| Jorge | [@JorgeRamos2001](https://github.com/JorgeRamos2001) |
| Leonel | [@leonelgit](https://github.com/leonelgit) |
| Fernando | [@fernandovalle45](https://github.com/fernandovalle45) |
| Milton | [@Milton84-toc](https://github.com/Milton84-toc) |

## 1. Descripción

Biela registra los vehículos que ingresan a un taller mecánico y las órdenes de servicio que se les realizan. El nombre es el de la pieza que une el pistón con el cigüeñal.

**Fase 1 (30 %)** incluye:

- Proyecto Laravel con conexión a MySQL o MariaDB mediante variables de entorno.
- Tres migraciones con llaves foráneas y restricciones, y tres modelos Eloquent con sus relaciones.
- Datos de prueba con factories y seeders.
- Listado paginado de vehículos con miniatura, ficha de detalle, catálogo de servicios y panel de resumen.
- Este README con el diagrama entidad-relación.

**Fase 2 (100 %)** agrega el CRUD completo, validación con Form Requests, manejo de imágenes y las reglas de negocio del caso.

## 2. Requisitos

| Requisito | Versión |
| --- | --- |
| PHP | 8.3 o superior (desarrollado con 8.4) |
| Composer | 2.x |
| Laravel | 13 (se comprueba con `php artisan --version`; desarrollado con 13.40) |
| Base de datos | MySQL o MariaDB local (XAMPP o Laragon) |

Extensiones de PHP necesarias: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `curl` y `zip`.
Se comprueban con `php -m`.

La interfaz usa CSS propio, sin Bootstrap ni Tailwind, y no requiere `npm`.

## 3. Instalación

1. `git clone <url del repositorio>` y `cd proyecto-final-laravel-equipo8`
2. `composer install`
3. Copiar `.env.example` a `.env`
4. `php artisan key:generate`
5. Crear una base vacía llamada `taller_mecanico` y revisar usuario y contraseña en `.env`
6. `php artisan migrate --seed`
7. `php artisan storage:link`
8. `php artisan serve` y abrir `http://127.0.0.1:8000`

Las tablas se crean únicamente con migraciones. Solo la base vacía se crea a mano.

## 4. Modelo de datos

```mermaid
erDiagram
    tipos_servicio {
        bigint id PK
        string nombre UK "60"
        string descripcion "255, nullable"
        decimal precio_base "8,2"
        timestamp created_at
        timestamp updated_at
    }
    vehiculos {
        bigint id PK
        string placa UK "10"
        string marca "50"
        string modelo "50"
        smallint anio "unsigned"
        string color "30"
        int kilometraje "unsigned"
        string propietario_nombre "100"
        string propietario_telefono "20"
        string imagen "255, nullable"
        timestamp created_at
        timestamp updated_at
    }
    ordenes_servicio {
        bigint id PK
        bigint vehiculo_id FK
        bigint tipo_servicio_id FK
        date fecha_ingreso
        date fecha_entrega "nullable"
        int kilometraje_ingreso "unsigned"
        text descripcion
        decimal costo_total "10,2"
        enum estado "pendiente, en_proceso, finalizada"
        timestamp created_at
        timestamp updated_at
    }
    vehiculos ||--o{ ordenes_servicio : "tiene"
    tipos_servicio ||--o{ ordenes_servicio : "clasifica"
```

## 5. Tablas y relaciones

| Tabla | Modelo | Relaciones |
| --- | --- | --- |
| `tipos_servicio` | `TipoServicio` | `ordenes()` hasMany `OrdenServicio` |
| `vehiculos` | `Vehiculo` | `ordenes()` hasMany `OrdenServicio` |
| `ordenes_servicio` | `OrdenServicio` | `vehiculo()` belongsTo `Vehiculo`, `tipoServicio()` belongsTo `TipoServicio` |

Cada modelo declara su tabla con `protected $table`, porque Laravel pluraliza en inglés y buscaría `tipo_servicios` y `orden_servicios`, que no existen.
Cada modelo define `$fillable` y `casts()` para proteger la asignación masiva y tipar fechas, dinero y enteros.

### Por qué `restrictOnDelete`

Las dos llaves foráneas de `ordenes_servicio` usan `restrictOnDelete()`. Un taller no pierde el historial de un vehículo ni borra un tipo de servicio que ya tiene órdenes. En la Fase 2 el borrado mostrará un mensaje claro en lugar de una excepción.

### Reglas de negocio (implementadas en la Fase 2)

Se validan en el servidor:

1. Placa única con formato definido por el equipo (`P` y seis dígitos, por ejemplo `P123456`). Se valida en `StoreVehiculoRequest` y `UpdateVehiculoRequest`.
2. Año del vehículo entre 1980 y el año actual + 1. Se valida en `StoreVehiculoRequest`.
3. El kilometraje de ingreso no puede ser menor que el último registrado para el vehículo. Lo valida la regla `KilometrajeNoMenorAlUltimo`, que usan los Form Requests de vehículos y de órdenes.
4. La fecha de entrega no puede ser anterior a la de ingreso. Se valida en `StoreOrdenServicioRequest`.
5. Una orden finalizada no puede editarse. La aplica `OrdenServicioPolicy` y responde 403.

## 6. Vehículos

*Sección de Jorge (bloque 3).*

El listado `/vehiculos` muestra los vehículos ordenados por placa, de 10 en 10, con su miniatura, marca y modelo, año, kilometraje, propietario con su teléfono y la cantidad de órdenes de servicio. Debajo de la tabla se indica el rango visible ("Mostrando 1 a 10 de 20 vehículos") y los enlaces de paginación. Si no hay vehículos aparece "Todavía no hay vehículos registrados."

### Datos de prueba

`VehiculoSeeder` crea 20 vehículos con `VehiculoFactory`. Cada marca solo genera sus propios modelos (por ejemplo, Toyota con Corolla o Hilux), el año va de 1995 al año actual y el kilometraje de 5 000 a 250 000 km.

### Formato de la placa

La placa es una `P` seguida de seis dígitos, por ejemplo `P123456`. La columna es única en la migración. La factory ya respeta el formato y la validación se agrega en la Fase 2.

### Imagen por defecto

Los vehículos sin foto muestran `public/img/vehiculo-default.svg`, que sí se versiona. Las vistas nunca arman la ruta a mano: usan el accesor `imagen_url` del modelo `Vehiculo`, que devuelve la foto subida a `storage/app/public` si existe o la imagen por defecto si no. Los datos de prueba dejan `imagen` en `null`.

### Filtro por marca

El formulario de arriba de la tabla filtra por marca con `?marca=`. Las opciones salen de las marcas que hay en la base, no de una lista escrita a mano. El valor llega a la consulta con `where()` de Eloquent, nunca concatenado en el SQL, y `withQueryString()` hace que la paginación conserve el filtro.

### Cómo comprobarlo

```bash
php artisan migrate:fresh --seed
php artisan route:list --name=vehiculos
```

La segunda orden debe listar únicamente `vehiculos.index` (`GET /vehiculos`). Después, con `php artisan serve`:

- `http://127.0.0.1:8000/vehiculos` muestra 10 vehículos y la página 2 muestra los otros 10.
- `http://127.0.0.1:8000/vehiculos?marca=Toyota` muestra solo esa marca. Las marcas se reparten al azar, así que conviene elegir una que aparezca en el filtro.


## Órdenes y resumen del taller

### 1. Órdenes de servicio

El sistema Biela permite consultar las órdenes de servicio
asociadas a los vehículos registrados en el taller.

Cada orden contiene:

- Vehículo asociado.
- Tipo de servicio realizado.
- Fecha de ingreso.
- Fecha de entrega, cuando corresponde.
- Kilometraje al momento del ingreso.
- Descripción del trabajo.
- Costo total.
- Estado de la orden.

Las órdenes se relacionan con los vehículos y los tipos
de servicio mediante los modelos Eloquent de Laravel.

### 2. Estados de las órdenes

Las órdenes pueden presentar tres estados:

**Pendiente:** el servicio está registrado, pero todavía
no ha comenzado.

**En proceso:** el vehículo se encuentra recibiendo
el servicio correspondiente.

**Finalizada:** el servicio se ha completado y la orden
tiene registrada una fecha de entrega.

En los datos de prueba, las órdenes pendientes y en proceso
no tienen fecha de entrega.

Las órdenes finalizadas tienen una fecha de entrega igual
o posterior a la fecha de ingreso.

### 3. Generación de órdenes de prueba

El archivo OrdenServicioFactory.php genera los datos
iniciales de cada orden.

El archivo OrdenServicioSeeder.php utiliza esa Factory
para registrar órdenes de servicio asociadas a vehículos
y tipos de servicio existentes.

El proceso funciona de la siguiente manera:

1. Se consultan los tipos de servicio registrados.
2. Se seleccionan los primeros 12 vehículos.
3. Se generan entre 1 y 3 órdenes para cada vehículo.
4. Los estados se distribuyen entre pendiente,
   en_proceso y finalizada.
5. El kilometraje aumenta progresivamente para cada
   vehículo conforme avanzan las fechas.
6. Las órdenes finalizadas reciben una fecha de entrega
   posterior a su ingreso.

Los ocho vehículos restantes quedan sin órdenes de
servicio para comprobar posteriormente el historial vacío.

La cantidad de órdenes generadas puede variar entre
12 y 36 registros.

### 4. Panel de resumen

La página principal del sistema se encuentra en la ruta:

GET /

Esta ruta utiliza PanelController@index para consultar
los datos de MySQL mediante Eloquent.

El panel muestra cinco indicadores:

- Vehículos registrados: cantidad total de vehículos.
- Órdenes de servicio: cantidad total de órdenes.
- Pendientes: órdenes con estado pendiente.
- En proceso: órdenes con estado en_proceso.
- Finalizadas: órdenes con estado finalizada.

La suma de los tres estados debe coincidir con
el total de órdenes de servicio.

### 5. Servicios más solicitados

El panel incluye una tabla que muestra todos los
tipos de servicio y la cantidad de órdenes asociadas.

La información se obtiene mediante la relación
ordenes() del modelo TipoServicio.

Se utiliza withCount('ordenes') para calcular
los totales y ordenar los servicios de mayor a menor.

### 6. Últimas órdenes

El panel muestra las cinco órdenes de servicio
más recientes.

La tabla contiene:

- Placa del vehículo.
- Tipo de servicio.
- Fecha de ingreso.
- Estado de la orden.

Las relaciones vehiculo y tipoServicio se cargan
anticipadamente mediante with() para evitar
consultas adicionales por cada registro.

La placa permite acceder a la ficha del vehículo.
Esta funcionalidad se completará en el módulo
asignado a Fernando.

### 7. Comprobación de los datos

Para preparar una base de datos local de prueba
desde cero, ejecutar:

php artisan migrate:fresh --seed

ADVERTENCIA: este comando elimina las tablas
existentes y sus datos. No debe utilizarse en una
base de datos con información que se necesite conservar.

Para iniciar el servidor:

php artisan serve

Abrir en el navegador:

http://127.0.0.1:8000

Para comprobar los registros, ejecutar:

php artisan tinker

Dentro de Tinker, utilizar las siguientes consultas:

Total de vehículos:

\App\Models\Vehiculo::count();

Total de órdenes:

\App\Models\OrdenServicio::count();

Órdenes pendientes:

\App\Models\OrdenServicio::where('estado', 'pendiente')->count();

Órdenes en proceso:

\App\Models\OrdenServicio::where('estado', 'en_proceso')->count();

Órdenes finalizadas:

\App\Models\OrdenServicio::where('estado', 'finalizada')->count();

Servicios más solicitados:

\App\Models\TipoServicio::withCount('ordenes')->orderByDesc('ordenes_count')->get();

Últimas cinco órdenes:

\App\Models\OrdenServicio::with(['vehiculo', 'tipoServicio'])->orderByDesc('fecha_ingreso')->limit(5)->get();

Los resultados deben coincidir con los indicadores
y las tablas del panel principal.

### 8. Archivos desarrollados

Los archivos correspondientes a este módulo son:

- database/factories/OrdenServicioFactory.php
- database/seeders/OrdenServicioSeeder.php
- app/Http/Controllers/PanelController.php
- resources/views/panel/index.blade.php

También se integraron el seeder de órdenes en
DatabaseSeeder.php y la ruta principal en routes/web.php.

Responsable del módulo: Leonel Munguia.

## Ficha del vehículo

*Sección de Fernando (bloque 5).*

La ficha se abre desde el botón **Ver ficha** del listado o directamente en `/vehiculos/{id}`. Muestra la placa del vehículo, su imagen (la foto subida o la imagen por defecto de Biela), sus datos —marca, modelo, año, color, kilometraje, propietario, teléfono y fecha de registro—, la cantidad de órdenes, el total facturado y el historial de servicio.

El historial lista las órdenes de la más reciente a la más antigua con la fecha de ingreso, el tipo de servicio, el kilometraje de ingreso, la descripción, el estado, la fecha de entrega (o el texto **Sin entregar** cuando falta) y el costo. El total facturado suma únicamente las órdenes finalizadas. Si el vehículo no tiene órdenes aparece "Este vehículo no ha tenido órdenes de servicio." y un botón **Volver al listado**.

### Eager loading

`VehiculoController@show` carga las órdenes y su tipo de servicio con `load(['ordenes' => ..., 'ordenes.tipoServicio'])` antes de entregar la vista. Así la ficha no hace una consulta por cada orden y las relaciones quedan disponibles en la vista sin consultas adicionales.

### Route model binding

El método recibe `show(Vehiculo $vehiculo)`. Laravel busca el vehículo por su llave en la ruta y responde 404 si no existe; no se usa `Vehiculo::find()`.

### Cómo comprobarlo

```bash
php artisan migrate:fresh --seed
php artisan route:list --name=vehiculos
php artisan serve
```

La segunda orden debe listar `vehiculos.index` (`GET /vehiculos`) y `vehiculos.show` (`GET /vehiculos/{vehiculo}`). Después:

- `http://127.0.0.1:8000/vehiculos/1` muestra los datos, la imagen por defecto, el resumen y las órdenes de la más reciente a la más antigua, con su tipo de servicio y su estado.
- `http://127.0.0.1:8000/vehiculos/20` muestra el mensaje de que no tiene órdenes (los vehículos del 13 al 20 no tienen).
- `http://127.0.0.1:8000/vehiculos/9999` responde con la página 404.
- Desde `/vehiculos`, el botón **Ver ficha** lleva a la ficha y el enlace **Volver al listado** regresa al listado.

## Interfaz

*Sección de Milton (bloque 6).*

El layout `resources/views/layouts/app.blade.php` reúne el menú, las alertas y el pie mediante los partials de `resources/views/partials/`. El menú usa rutas nombradas y marca la sección activa, incluida **Vehículos** al abrir una ficha. Las alertas leen los mensajes de sesión `exito` y `error`. El nombre del taller proviene de `config('app.name')`.

La interfaz usa una base de papel cálido, tinta oscura y un acento óxido. Los colores se centralizan en `:root` de `public/css/estilos.css`.

| Uso | Variables |
| --- | --- |
| Fondo y superficies | `--biela-fondo`, `--biela-superficie` |
| Texto principal y secundario | `--biela-tinta`, `--biela-suave` |
| Bordes y acento | `--biela-linea`, `--biela-acento` |
| Pendiente | `--estado-pendiente`, `--estado-pendiente-fondo` |
| En proceso | `--estado-proceso`, `--estado-proceso-fondo` |
| Finalizada | `--estado-final`, `--estado-final-fondo` |

Los títulos usan Georgia (`--fuente-titulo`), el resto de la interfaz usa la tipografía del sistema (`--fuente-texto`) y las placas usan una fuente monoespaciada (`--fuente-placa`). Los bordes son finos y las esquinas usan `--radio`. La interfaz usa CSS propio y fuentes locales, sin degradados, sombras, estilos en línea, CDN, Vite ni npm. Cada etiqueta de estado conserva su texto en español.

| Elemento | Clases |
| --- | --- |
| Estructura y título | `contenedor`, `titulo-pagina`, `marca` |
| Tablas y listado | `tabla`, `miniatura`, `placa`, `filtro` |
| Acciones, alertas y mensajes vacíos | `boton`, `alerta`, `alerta-exito`, `alerta-error`, `vacio` |
| Ficha | `ficha`, `ficha-imagen`, `ficha-datos` |
| Estados | `etiqueta`, `etiqueta-pendiente`, `etiqueta-en-proceso`, `etiqueta-finalizada` |
| Panel | `indicadores`, `indicador`, `indicador-valor`, `indicador-nombre` |
| Paginación | `pagination`, `page-item`, `page-link`, `active`, `disabled` |

A 700 px o menos, el menú y los indicadores se apilan, la imagen queda encima de los datos de la ficha y cada tabla permite desplazarse horizontalmente dentro de su propio borde. Para comprobar la interfaz, ejecuta `php artisan view:clear` y abre `/`, `/vehiculos`, `/vehiculos/1` y `/servicios` a 375 px y en escritorio.


## Evidencias de conceptos de programación

Durante la Fase 1 del sistema Biela se implementaron
y documentaron los siguientes conceptos:

### Migraciones [MIGRACION]

Las tablas tipos_servicio, vehiculos y ordenes_servicio
se crean mediante migraciones de Laravel.

Las migraciones permiten mantener una estructura
de base de datos reproducible.

### Relaciones [RELACION]

Los modelos utilizan relaciones Eloquent:

- Vehiculo tiene muchas órdenes de servicio.
- TipoServicio tiene muchas órdenes de servicio.
- OrdenServicio pertenece a un vehículo.
- OrdenServicio pertenece a un tipo de servicio.

Las llaves foráneas mantienen la integridad
de los datos relacionados.

### Encapsulamiento [ENCAPSULAMIENTO]

Los modelos utilizan fillable para controlar
las columnas que pueden asignarse masivamente.

También utilizan casts para transformar fechas,
cantidades y valores monetarios.

El modelo Vehiculo incorpora un accesor para
obtener la imagen del vehículo.

### Lectura de datos [CRUD-READ]

Los controladores utilizan Eloquent para consultar
los registros de la base de datos.

- VehiculoController: consulta los vehículos.
- TipoServicioController: consulta los servicios.
- PanelController: obtiene indicadores y órdenes.

Las consultas se realizan sin concatenar SQL manualmente.

## Fase 2

Cada integrante escribe solo dentro de su subsección. Las subsecciones están separadas por varias líneas en blanco para que git combine los cambios paralelos sin conflictos. Los contratos comunes (rutas, métodos de los modelos, fábricas y marcado HTML) están en [docs/CONTRATOS-FASE2.md](docs/CONTRATOS-FASE2.md).





### Instalación y seguridad (Ángel)

*Pendiente: Ángel documenta aquí la instalación, las pruebas, el usuario de desarrollo, la seguridad y el flujo de Git.*





### Reporte (Carlos)

#### Cambios de esquema de la Fase 2

La Fase 2 tiene un solo cambio de esquema, hecho con dos migraciones nuevas. Las migraciones de la Fase 1 no se editan: la regla de continuidad del enunciado exige que todo cambio sea una migración nueva con `down()` reversible.

| Migración | Qué agrega | Para qué |
| --- | --- | --- |
| `2026_11_13_000001_add_costo_adicional_to_ordenes_servicio_table` | Columna `costo_adicional` decimal(10,2) con valor por defecto 0 e índice `(estado, fecha_ingreso)` en `ordenes_servicio` | El backend calcula `costo_total = precio_base + costo_adicional`. El índice sirve al reporte y al panel, que filtran por estado y período. |
| `2026_11_13_000002_add_indices_to_vehiculos_table` | Índices en `vehiculos.marca` y `vehiculos.propietario_nombre` | El filtro por marca y la búsqueda por propietario no recorren la tabla completa. |

El valor por defecto 0 mantiene válidas las órdenes y los seeders que ya existían. Se comprueba con `php artisan test --filter=EsquemaFase2Test`, `php artisan migrate:fresh --seed` y `php artisan migrate:rollback --step=2`.

#### Métodos de dominio y fábricas

- `OrdenServicio`: constantes `ESTADOS` y `TRANSICIONES`; scopes `estado()`, `pendientes()` y `delPeriodo()`; métodos `estaFinalizada()`, `puedeEditarse()` y `siguienteEstado()`.
- `Vehiculo`: `ultimoKilometraje(?int $ignorarOrdenId = null)` (regla 3) y `totalGastado()` (suma de las órdenes finalizadas).
- Fábricas: `OrdenServicio::factory()->pendiente()`, `->enProceso()`, `->finalizada()`; `TipoServicio::factory()`; `Vehiculo::factory()->conKilometraje(n)`.

#### Escenarios de la defensa

`php artisan migrate:fresh --seed` ejecuta `EscenariosDefensaSeeder` después de los datos de la Fase 1. Crea cuatro vehículos con placa fija para probar cada regla en vivo. El seeder es idempotente: busca cada vehículo por placa y solo crea sus órdenes si no tiene ninguna.

| Placa | Órdenes | Qué se prueba |
| --- | --- | --- |
| P000001 | Una orden finalizada en el mes actual | Regla 5: la orden finalizada no puede editarse. |
| P000002 | Una orden pendiente a 85 000 km | Regla 3: una orden nueva con 80 000 km se rechaza. |
| P000003 | Ninguna | El vehículo se puede eliminar. |
| P000004 | Dos finalizadas (hace uno y dos meses) y una en proceso | El vehículo no se puede eliminar; el filtro de fechas del reporte tiene datos en los últimos tres meses. |

Todas las órdenes cumplen `costo_total = precio_base + costo_adicional` y la fecha de entrega nunca es anterior a la de ingreso. Se comprueba con `php artisan test --filter=EscenariosDefensaTest`.

#### Página del reporte (`/reportes`)

| Consulta | Cómo se calcula |
| --- | --- |
| Órdenes pendientes | `OrdenServicio::pendientes()->with(['vehiculo', 'tipoServicio'])`, de la más antigua a la más reciente. Los días en el taller se calculan en el controlador con Carbon. |
| Ingresos por tipo de servicio | `withCount` y `withSum` sobre las órdenes finalizadas de cada tipo; la base de datos hace la suma. |
| Total general | `OrdenServicio::estado('finalizada')->sum('costo_total')`. |

Solo las órdenes finalizadas cuentan como ingreso. Las relaciones se cargan con `with()`, así la página no hace una consulta por fila. Se comprueba con `php artisan test --filter=ReporteTest`.

#### Historial por vehículo (`/reportes/historial`)

Un selector envía `vehiculo_id` por GET. `HistorialVehiculoRequest` lo valida (`nullable`, `integer`, `exists:vehiculos,id`) con mensajes en español; si no es válido, la página vuelve al selector y muestra el error junto al campo. Con un vehículo válido se cargan sus órdenes de la más reciente a la más antigua junto con su tipo de servicio (`load`), y se muestran `totalGastado()` y `ultimoKilometraje()` del modelo. Se comprueba con `php artisan test --filter=ReporteHistorialTest`.

#### Filtro por fechas y exportación a CSV

- `FiltroReporteRequest` valida `desde` y `hasta` (`nullable`, `date`; `hasta` con `after_or_equal:desde` cuando hay fecha inicial) con mensajes en español. Sin fechas, el reporte muestra el mes actual.
- El período se aplica con el scope `delPeriodo()` a los pendientes, al total y dentro de los closures de `withCount` y `withSum`. La consulta de ingresos está en un método privado que comparten la página y el CSV, así los dos muestran los mismos números.
- **Exportar ingresos a CSV** (`/reportes/exportar`, extra +3) descarga `ingresos-por-servicio.csv` con `response()->streamDownload()` y `fputcsv`, sin paquetes externos. El archivo empieza con el BOM UTF-8 para que Excel muestre las tildes, tiene los encabezados Servicio, Órdenes e Ingresos y respeta el mismo filtro de fechas.

Se comprueba con `php artisan test --filter=ReporteCsvTest`.


### Pruebas de seguridad

Se incorporó el trait `VerificaSeguridad`, ubicado en
`tests/Concerns/VerificaSeguridad.php`, que permite
reutilizar comprobaciones de seguridad en las pruebas.

Métodos disponibles:

- `assertFormularioConCsrf()`: verifica la presencia
  del token CSRF.
- `assertHtmlEscapado()`: verifica que el contenido HTML
  se muestra escapado.

Las pruebas comprueban la protección contra XSS,
inyección SQL, asignación masiva, eliminación mediante
GET y la presencia de tokens CSRF.

Para ejecutarlas:

```bash
php artisan test --filter=SeguridadTest
```

## Instalación y configuración

Biela es un sistema de gestión para un taller mecánico
desarrollado con Laravel bajo el patrón MVC.

### Requisitos del sistema

- PHP 8.4.
- Composer 2.
- MySQL o MariaDB.
- XAMPP (opcional).
- Git.
- Visual Studio Code u otro editor.
- Extensiones PHP:
  - pdo_mysql
  - pdo_sqlite
  - gd
  - fileinfo
  - mbstring

Para comprobar las extensiones instaladas:

    php -m

### Descargar el repositorio

    git clone https://github.com/jojosenthusiast/proyecto-final-laravel-equipo8.git
    cd proyecto-final-laravel-equipo8

### Instalar las dependencias

    composer install

### Configurar las variables de entorno

Desde Git Bash:

    cp .env.example .env

Desde PowerShell:

    Copy-Item .env.example .env

Generar la clave de la aplicación:

    php artisan key:generate

Iniciar MySQL desde XAMPP o el gestor utilizado.

Crear una base de datos local llamada biela y
configurar el archivo .env:

    APP_NAME=Biela
    APP_ENV=local
    APP_DEBUG=true
    APP_URL=http://127.0.0.1:8000

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=biela
    DB_USERNAME=root
    DB_PASSWORD=

Modificar el usuario y la contraseña de la base de
datos según la configuración de cada computadora.

### Preparar la base de datos

Ejecutar las migraciones y los seeders:

    php artisan migrate:fresh --seed

Advertencia: este comando elimina las tablas existentes
y vuelve a crearlas. Debe utilizarse únicamente en una
base de datos local de desarrollo.

Si la extensión pdo_mysql no está habilitada por defecto:

    php -d extension=pdo_mysql artisan migrate:fresh --seed

Crear el enlace simbólico para las imágenes:

    php artisan storage:link

### Ejecutar la aplicación

    php artisan serve

Abrir en el navegador:

http://127.0.0.1:8000

Todo el sistema funciona localmente y no requiere
servicios de alojamiento ni plataformas de pago.

## Usuario de desarrollo

El usuario de desarrollo se genera mediante
UsuarioSeeder durante la ejecución de los seeders.

- Correo: admin@biela.test
- Contraseña: Biela2026

Este usuario es exclusivo del entorno de desarrollo
y no debe utilizarse en producción.

Los visitantes pueden consultar la información pública
del sistema sin iniciar sesión.

Las operaciones de creación, edición, actualización,
eliminación y cambio de estado requieren autenticación.

## Pruebas automatizadas

Para ejecutar todas las pruebas del proyecto:

    php artisan test

Si pdo_sqlite no está habilitado por defecto:

    php -d extension=pdo_sqlite artisan test

### Pruebas de autenticación y seguridad

    php artisan test --filter=AutenticacionTest
    php artisan test --filter=MenuSesionTest
    php artisan test --filter=ProteccionRutasTest
    php artisan test --filter=PaginasErrorTest
    php artisan test --filter=SeguridadTest
    php artisan test --filter=CabecerasSeguridadTest
    php artisan test --filter=CambiarContrasenaTest

Las pruebas utilizan una base de datos de pruebas
independiente mediante SQLite.

### Integración continua

El proyecto utiliza GitHub Actions para ejecutar
automáticamente las pruebas cuando se suben cambios.

El archivo de configuración se encuentra en:

    .github/workflows/pruebas.yml

El resultado puede consultarse desde la pestaña
Actions del repositorio de GitHub.

Si una ejecución falla, se debe identificar la prueba
que provocó el error, informar al equipo y corregirla
antes de continuar integrando cambios.

## Seguridad

Durante la Fase 2 se implementaron las siguientes
medidas de seguridad:

- Autenticación mediante sesiones de Laravel.
- Inicio y cierre de sesión seguro.
- Regeneración del identificador de sesión.
- Protección de rutas mediante middleware auth.
- Protección CSRF en los formularios.
- Contraseñas almacenadas mediante hash.
- Límite de cinco intentos fallidos de autenticación
  por minuto, identificados por correo electrónico e IP.
- Escape de contenido HTML mediante Blade.
- Validación de datos en el servidor.
- Pruebas contra asignación masiva de identificadores.
- Pruebas contra inyección SQL en el filtro de marca.
- Páginas de error 403, 419 y 429 en español.
- Cambio de contraseña validando la contraseña actual.

### Cabeceras HTTP de seguridad

El middleware CabecerasSeguridad incorpora:

- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- Referrer-Policy: same-origin

Su ubicación es:

    app/Http/Middleware/CabecerasSeguridad.php

El middleware se registra en el grupo web del
archivo bootstrap/app.php.

### Utilidades para pruebas de seguridad

El trait VerificaSeguridad se encuentra en:

    tests/Concerns/VerificaSeguridad.php

Contiene los siguientes métodos reutilizables:

- assertFormularioConCsrf(): comprueba que un
  formulario contiene el token CSRF.
- assertHtmlEscapado(): comprueba que el contenido
  HTML se muestra escapado y no como código ejecutable.

Para utilizarlo en otras pruebas:

    use Tests\Concerns\VerificaSeguridad;

Dentro de la clase de prueba:

    use VerificaSeguridad;

## Flujo de trabajo con Git

Cada integrante desarrolla su módulo en una rama
correspondiente a la funcionalidad asignada.

Antes de comenzar una nueva funcionalidad:

    git fetch origin
    git switch -c nombre-de-la-rama origin/main

Después de desarrollar la funcionalidad:

    php artisan test
    git status
    git add ruta-del-archivo
    git commit -m "mensaje del commit"
    git pull --rebase origin main
    php artisan test
    git push origin HEAD:main

La rama se publica cuando se completa su último commit:

    git push origin nombre-de-la-rama

### Reglas del equipo

- Utilizar mensajes bajo Conventional Commits.
- Ejecutar las pruebas antes de subir cambios.
- No utilizar git push --force sobre main.
- No subir archivos .env ni credenciales privadas.
- No subir la carpeta vendor.
- No modificar archivos asignados a otros integrantes
  sin autorización.
- Resolver los conflictos antes de realizar un push.
- Si main presenta errores, detener temporalmente
  los pushes hasta que se corrijan.


### Imágenes y catálogo (Fernando)

#### Imágenes de los vehículos

`ImagenVehiculoService` (`app/Services/ImagenVehiculoService.php`) encapsula el manejo de archivos con `Storage::disk('public')` y es la única pieza que escribe en el disco:

- `guardar(UploadedFile $archivo): string` guarda la foto en `vehiculos/` y devuelve su ruta relativa.
- `reemplazar(?string $actual, UploadedFile $nuevo): string` guarda la nueva y después borra la anterior.
- `eliminar(?string $ruta): void` borra la foto si existe; `null` o un archivo que ya no está no son un error.
- `conImagen(?UploadedFile $archivo, callable $operacion, ?string $anterior = null): mixed` guarda la foto, ejecuta la operación dentro de `DB::transaction` y, si algo falla, borra la foto nueva y relanza la excepción; si termina bien, borra la anterior. Sin archivo ejecuta la operación con `null`.

La base de datos guarda solo la ruta, nunca el binario. Los archivos viven en `storage/app/public/vehiculos`, que no se sube al repositorio; para que el navegador los vea hay que ejecutar `php artisan storage:link`. Se comprueba con `php artisan test --filter=ImagenVehiculoServiceTest`.

`conImagen()` evita archivos huérfanos: si la fila no se guarda, la foto nueva se elimina; la foto anterior solo se borra cuando el cambio ya quedó guardado.

`VehiculoObserver` (`app/Observers/VehiculoObserver.php`) está registrado en `AppServiceProvider` y borra la foto cuando se elimina el vehículo. Así ningún controlador tiene que acordarse de hacerlo. Se comprueba con `php artisan test --filter=VehiculoObserverTest`.

#### Regla 3: kilometraje

`KilometrajeNoMenorAlUltimo` (`app/Rules/KilometrajeNoMenorAlUltimo.php`) es un objeto `Rule` que compara el kilometraje enviado con `Vehiculo::ultimoKilometraje()`. Si el valor es menor, falla con el mensaje «El kilometraje no puede ser menor que el último registrado (N km).». Si el vehículo no existe, la regla no falla y deja el error a `exists:vehiculos,id`. Al editar una orden se pasa su id como segundo argumento para no compararla consigo misma. Se comprueba con `php artisan test --filter=ReglaKilometrajeTest`.

#### Catálogo de servicios

El catálogo se administra desde `/servicios` con sesión iniciada. `TipoServicioController` implementa `create`, `store`, `edit` y `update`; el registro y la edición comparten el formulario `servicios/partials/formulario.blade.php`, que conserva los valores con `old()` y muestra cada error junto a su campo.

`StoreTipoServicioRequest` y `UpdateTipoServicioRequest` validan:

- `nombre`: obligatorio, texto, máximo 60 caracteres y único (en la edición se ignora el propio servicio).
- `precio_base`: obligatorio, numérico, mayor que cero y máximo 99999.99.
- `descripcion`: opcional, texto, máximo 255 caracteres.

Los mensajes están en español. Se comprueba con `php artisan test --filter=TipoServicioCrudTest`.

La columna «Órdenes» se calcula con `withCount('ordenes')` y cada celda de la tabla lleva `data-label`. El botón Eliminar envía `DELETE` con confirmación; si el servicio ya tiene órdenes, `destroy` no lo borra y devuelve un mensaje de error explicativo, porque la llave foránea es `restrictOnDelete`. Se comprueba con `php artisan test --filter=TipoServicioEliminarTest`.

`TipoServicioValidacionHttpTest` simula un navegador con `novalidate` y envía diez casos inválidos por HTTP: nombre vacío o en blanco, nombre de 61 caracteres, nombre duplicado, precio vacío, cero, negativo, con letras o demasiado alto, y descripción de 256 caracteres. Cada caso comprueba el mensaje en español y que los datos válidos se conservan con `old()`. Se ejecuta con `php artisan test --filter=TipoServicioValidacionHttpTest`.

#### Ficha del servicio

`GET /servicios/{servicio}` es pública. `TipoServicioController@show` carga con `loadCount('ordenes')` el total de órdenes y con `loadSum()` los ingresos de las órdenes finalizadas (usando `scopeEstado('finalizada')`), y trae las 10 órdenes más recientes con su vehículo ya cargado. La vista `servicios/show.blade.php` muestra descripción, precio base, total de órdenes, ingresos, el historial reciente con enlaces a `ordenes.show` y `vehiculos.show`, y un estado vacío cuando no hay órdenes. Se comprueba con `php artisan test --filter=TipoServicioFichaTest`.

#### Búsqueda y orden

El catálogo acepta `busqueda`, `orden` y `direccion` por la URL. `TipoServicio::scopeBuscar()` filtra por nombre con un parámetro enlazado. `TipoServicioController@index` solo permite ordenar por `nombre`, `precio_base` u `ordenes_count`; cualquier otra columna cae en `nombre`, y la dirección solo puede ser `asc` o `desc`. La lista se pagina de 10 en 10 con `withQueryString()`, así que los enlaces conservan la búsqueda y el orden. Se comprueba con `php artisan test --filter=TipoServicioBusquedaTest`.

#### Comando de fotos huérfanas

`php artisan biela:imagenes-huerfanas` lista las fotos de `storage/app/public/vehiculos` que no pertenecen a ningún vehículo. Sin opciones solo avisa; con `--eliminar` borra esos archivos y deja intactas las fotos en uso. Se comprueba con `php artisan test --filter=ImagenesHuerfanasCommandTest`.

#### Comprobación rápida de mi bloque

```sh
php artisan storage:link
php artisan test --filter='ImagenVehiculoServiceTest|ReglaKilometrajeTest|VehiculoObserverTest|TipoServicioCrudTest|TipoServicioEliminarTest|TipoServicioValidacionHttpTest|TipoServicioFichaTest|TipoServicioBusquedaTest|ImagenesHuerfanasCommandTest'
```





### Vehículos (Jorge)

Este es el CRUD completo de la entidad principal. Cualquiera puede ver el listado y la ficha. Registrar, editar y eliminar requieren sesión iniciada.

| Página | Ruta | Qué hace |
| --- | --- | --- |
| Listado | `GET /vehiculos` | Búsqueda por placa, propietario o modelo; filtro por marca; orden por placa, año o kilometraje; paginación de 10 en 10 que conserva los filtros. |
| Ficha | `GET /vehiculos/{vehiculo}` | Datos, foto, indicadores e historial de órdenes; enlaces a registrar orden, editar y eliminar. |
| Registro | `GET /vehiculos/create`, `POST /vehiculos` | Formulario con foto opcional (`StoreVehiculoRequest`). |
| Edición | `GET /vehiculos/{vehiculo}/edit`, `PUT /vehiculos/{vehiculo}` | Datos precargados, reemplazo seguro de la foto y regla 3 en el kilometraje (`UpdateVehiculoRequest`). |
| Eliminación | `DELETE /vehiculos/{vehiculo}` | Con confirmación; si el vehículo tiene órdenes, muestra un mensaje en lugar de un error 500. |

Las reglas del caso que viven en este módulo:

- **Regla 1:** placa única con formato `P123456`.
- **Regla 2:** año entre 1980 y el año actual + 1.
- **Regla 3:** en la edición, el kilometraje no baja del último registrado.

Archivos del módulo: `VehiculoController`, `StoreVehiculoRequest`, `UpdateVehiculoRequest`, `resources/views/vehiculos/*` y `Vehiculo::scopeBuscar()`. Todas sus pruebas se corren con:

```bash
php artisan test --filter='Vehiculo(Reglas|Busqueda|Ficha|Registro|Edicion|Eliminacion|ValidacionHttp)Test|ImagenesCicloTest'
```

#### Validación del vehículo (reglas 1 y 2)

El registro usa `StoreVehiculoRequest` y la edición usa `UpdateVehiculoRequest`, que hereda las mismas reglas y solo cambia la de placa única. Antes de validar, la placa se pasa a mayúsculas y se le quitan los espacios: «p123456 » se guarda como «P123456».

| Campo | Reglas | Mensaje si falla |
| --- | --- | --- |
| `placa` | Obligatoria, formato `P` + 6 dígitos (`regex:/^P\d{6}$/`), única. En la edición, `Rule::unique()->ignore()` excluye al propio vehículo. | «La placa debe tener el formato P123456.» / «Ya hay un vehículo registrado con esa placa.» |
| `anio` | Obligatorio, entero, entre 1980 y el año actual + 1, que se calcula en cada petición. | «El año debe estar entre 1980 y 2027.» (en 2026) |
| `marca`, `modelo` | Obligatorios, máximo 50 caracteres. | «La marca no puede tener más de 50 caracteres.» |
| `color` | Obligatorio, máximo 30 caracteres. | |
| `kilometraje` | Obligatorio, entero de 0 a 999 999. | «El kilometraje no puede ser negativo.» |
| `propietario_nombre` | Obligatorio, máximo 100 caracteres. | |
| `propietario_telefono` | Obligatorio, formato `0000-0000`. | «El teléfono debe tener el formato 0000-0000.» |
| `imagen` | Opcional; JPG, PNG o WEBP de hasta 2 MB. | «La imagen debe ser JPG, PNG o WEBP.» / «La imagen no puede pesar más de 2 MB.» |

La aplicación usa el idioma por defecto de Laravel, así que cada mensaje está escrito en español en `messages()` y los nombres de los campos en `attributes()`. Se comprueba con `php artisan test --filter=VehiculoReglasTest`.

#### Búsqueda en el listado

El campo «Buscar» de `/vehiculos` (`?buscar=`) encuentra vehículos por parte de la placa, del nombre del propietario o del modelo, y se combina con el filtro de marca. Por ejemplo, `?buscar=ana&marca=Toyota` devuelve solo los Toyota cuyo propietario se llama Ana. La búsqueda vive en el scope `Vehiculo::scopeBuscar()`:

- El texto llega a la consulta como parámetro enlazado de PDO, aunque el patrón lleve `%`, así que no es SQL concatenado. Una comilla simple o un `' OR 1=1 --` se buscan como texto normal.
- Los tres `orWhere` van dentro de un closure, que los encierra entre paréntesis: `(placa LIKE ? OR propietario_nombre LIKE ? OR modelo LIKE ?) AND marca = ?`.
- `withQueryString()` hace que la paginación conserve la búsqueda y la marca.
- Si un filtro llega como arreglo (`?marca[]=x`), se ignora en lugar de producir un error 500.
- Sin resultados, la página muestra «Ningún vehículo coincide con la búsqueda.» y un enlace para quitar los filtros.

Cada celda de la tabla lleva `data-label` con el nombre de su columna, según el contrato de marcado.

#### Orden del listado

Los encabezados Placa, Año y Kilometraje son enlaces que ordenan el listado (`?orden=anio&direccion=desc`). Si se pulsa el encabezado de la columna activa, se invierte la dirección (▲ ascendente, ▼ descendente). Los enlaces conservan la búsqueda y la marca, y el formulario de búsqueda conserva el orden.

- **Lista blanca:** `orderBy()` enlaza los valores pero no el nombre de la columna, así que `?orden=` se compara con `['placa', 'anio', 'kilometraje']` antes de llegar a la consulta. Cualquier otro valor, como `?orden=placa;drop table vehiculos`, se ignora y el listado se ordena por placa, sin error.
- **Dirección:** solo `asc` o `desc`; cualquier otro valor se trata como `asc`.
- **Desempate:** con años o kilometrajes repetidos, la placa decide el orden, así cada vehículo queda siempre en la misma página.

Se comprueba con `php artisan test --filter=VehiculoBusquedaTest`.

#### Ficha como centro de acciones

Desde `/vehiculos/{vehiculo}` se llega a todas las acciones del vehículo y de sus órdenes:

| Acción | A dónde lleva | Quién la ve |
| --- | --- | --- |
| Registrar orden | `route('ordenes.create', ['vehiculo' => $vehiculo])`, es decir `/ordenes/create?vehiculo=ID`. El formulario de órdenes lee el parámetro y deja el vehículo preseleccionado. | Todos |
| Ver orden (una por fila del historial) | `ordenes.show` de esa orden | Todos |
| Editar | `vehiculos.edit` | Solo con sesión (`@auth`) |
| Eliminar | Formulario `POST` con `@csrf`, `@method('DELETE')` y confirmación `data-confirmar` | Solo con sesión (`@auth`) |

La ficha muestra tres indicadores calculados por el modelo: la cantidad de órdenes, el total gastado (`Vehiculo::totalGastado()`, que suma solo las órdenes finalizadas) y el último kilometraje (`Vehiculo::ultimoKilometraje()`, el mayor entre el del vehículo y el de sus órdenes). El historial va de la orden más reciente a la más antigua, y cada estado usa su etiqueta de color (`etiqueta-pendiente`, `etiqueta-en-proceso`, `etiqueta-finalizada`).

Las órdenes y su tipo de servicio se cargan juntos con `load(['ordenes' => ..., 'ordenes.tipoServicio'])`. Como `preventLazyLoading` está activo fuera de producción, olvidar el tipo de servicio no pasaría en silencio: la página fallaría con `LazyLoadingViolationException`. Se comprueba con `php artisan test --filter=VehiculoFichaTest`.

#### Registro con imagen

Con sesión iniciada, el listado muestra el botón «Registrar vehículo», que lleva a `/vehiculos/create`. Sin sesión, la ruta redirige al inicio de sesión.

- **Formulario:** el parcial `vehiculos/partials/formulario.blade.php` lo comparten el registro y la edición. Sigue el contrato de marcado: cada campo va en `.campo` y, cuando falla, lleva `aria-invalid` y su mensaje en `.error-campo`, justo debajo. `old()` devuelve lo que el usuario escribió, así un error no obliga a llenar todo de nuevo. El archivo de la foto no se puede devolver con `old()` y hay que volver a elegirlo.
- **Foto:** el `<form>` lleva `enctype="multipart/form-data"`, sin el cual el navegador no envía el archivo. El campo `imagen` acepta `.jpg`, `.jpeg`, `.png` y `.webp`, y su `data-vista-previa="vista-imagen"` apunta al `<img>` donde se muestra la foto elegida.
- **Guardado:** `store(StoreVehiculoRequest $request, ImagenVehiculoService $imagenes)` recibe los datos ya validados. Si llegó una foto, el servicio de imágenes la guarda en el disco `public` y en la columna `imagen` solo queda la ruta (`vehiculos/abc123.png`), nunca el archivo. Sin foto, la columna queda en `null` y la ficha muestra `img/vehiculo-default.svg`.
- **Resultado:** redirige a la ficha del vehículo con el mensaje «Vehículo registrado correctamente.», que muestra el layout.

Se comprueba con `php artisan test --filter=VehiculoRegistroTest`. Las fotos de prueba son PNG porque algunas instalaciones de PHP traen GD sin soporte para JPEG, y `UploadedFile::fake()->image()` necesita GD para dibujarlas.

#### Edición con reemplazo seguro de la foto

Con sesión iniciada, el botón «Editar» de la ficha lleva a `/vehiculos/{vehiculo}/edit`. El formulario es el mismo parcial del registro, con los datos actuales precargados (`old('placa', $vehiculo->placa)`) y la foto actual visible (`$vehiculo->imagen_url`). Como los formularios HTML solo envían GET o POST, `@method('PUT')` agrega el campo `_method` para que la petición llegue a `vehiculos.update`.

- **Placa única:** `UpdateVehiculoRequest` usa `Rule::unique()->ignore()` sobre el propio vehículo. Guardar sin cambiar la placa no da error, pero usar la placa de otro vehículo sí: «Ya hay un vehículo registrado con esa placa.».
- **Sin foto nueva:** el campo `imagen` se quita de los datos validados antes de guardar. Si no se quitara, el campo vacío pisaría la ruta guardada con `null` y el vehículo perdería su foto.
- **Con foto nueva:** `ImagenVehiculoService::conImagen()` hace el reemplazo en este orden:
  1. Guarda la foto nueva.
  2. Actualiza la fila dentro de una transacción.
  3. Solo si eso salió bien, borra la foto anterior.

  Si la actualización falla, borra la foto nueva y la anterior sigue en el disco y en la fila. Así nunca queda un archivo sin dueño ni un vehículo apuntando a una foto borrada.
- **Registro:** `store()` también usa `conImagen()`. Si el insert falla, la foto recién subida se borra.
- **Resultado:** redirige a la ficha con el mensaje «Vehículo actualizado correctamente.».

Se comprueba con `php artisan test --filter=VehiculoEdicionTest`. Para simular una falla de la base de datos al guardar, las pruebas lanzan una excepción en el evento `updating` (o `creating`) del modelo.

#### Eliminación con confirmación

Con sesión iniciada, el listado y la ficha muestran el botón «Eliminar». Sigue el contrato de marcado: un formulario `POST` con `@csrf` y `@method('DELETE')`, más `data-confirmar="¿Eliminar el vehículo P123456?"`. El `onsubmit="return confirm(...)"` pide la confirmación aunque `biela.js` no cargue.

- **Solo por DELETE:** la URL de borrado es la misma de la ficha. Un enlace o una visita por GET solo muestra el vehículo, y un POST sin `_method=DELETE` responde 405. Sin sesión, redirige al inicio de sesión.
- **Vehículo con órdenes:** la llave foránea de `ordenes_servicio` usa `restrictOnDelete`, para que el taller no pierda el historial. Antes de borrar, `destroy()` cuenta las órdenes y, si hay alguna, regresa a la página de origen con «No se puede eliminar: el vehículo tiene 2 órdenes registradas.» (o «1 orden registrada»). No se llega a intentar el `DELETE`, así que no hay error 500.
- **Red de seguridad:** si alguien registra una orden justo entre la revisión y el borrado, la base rechaza el `DELETE` con una `QueryException`. El controlador la atrapa y responde con el mismo mensaje.
- **Foto:** el controlador no borra el archivo. Lo hace el observer de `Vehiculo` (`VehiculoObserver::deleted`) cuando el borrado ya sucedió. Si el borrado no sucede, la foto se queda.
- **Resultado:** redirige siempre al listado con «Vehículo eliminado correctamente.», porque la ficha del vehículo borrado ya no existe.

Se comprueba con `php artisan test --filter=VehiculoEliminacionTest`.

#### Kilometraje en la edición (regla 3)

La regla 3 del caso dice que el kilometraje nunca baja del último registrado. Además de aplicarse a las órdenes, también se aplica al editar el vehículo. `UpdateVehiculoRequest` agrega la regla `KilometrajeNoMenorAlUltimo` (de Fernando, F2) a `required`, `integer`, `min:0` y `max:999999` del registro.

- El «último registrado» lo calcula `Vehiculo::ultimoKilometraje()`: es el mayor entre el kilometraje del vehículo y el de sus órdenes. Si una orden registró 85 000 km, el vehículo no se puede editar a 80 000. Sale «El kilometraje no puede ser menor que el último registrado (85000 km).» junto al campo, y el valor escrito se conserva.
- Igualar o subir el kilometraje funciona.
- Un vehículo sin órdenes acepta cualquier valor mayor o igual al que ya tiene, pero no uno menor.
- Solo cuentan las órdenes del propio vehículo.
- El registro no aplica esta regla: un vehículo nuevo no tiene historial.

Se comprueba con `php artisan test --filter=VehiculoEdicionTest`.

#### Batería de validación con novalidate (guion de la defensa)

`VehiculoValidacionHttpTest` simula el formulario de registro con `novalidate`: el navegador no frena nada (ni `required`, ni `min`, ni `accept`), y cada dato incorrecto llega al servidor por `POST /vehiculos`. En cada caso se cambia un solo campo de un registro válido y se comprueba:

- que se regresa al formulario con el mensaje en español;
- que el único error es el de ese campo;
- que no se guardó ni la fila ni el archivo.

| Campo | Lo que se envía | Mensaje |
| --- | --- | --- |
| Placa | vacía | El campo placa es obligatorio. |
| Placa | `ABC123`, `P12345`, `P1234567`, `P 123456` | La placa debe tener el formato P123456. |
| Placa | `P000777` ya registrada (también como ` p000777 `) | Ya hay un vehículo registrado con esa placa. |
| Año | `1979`, año actual + 2 | El año debe estar entre 1980 y 2027. (en 2026) |
| Año | `dos mil`, `2020.5` | El campo año debe ser un número entero. |
| Kilometraje | `-1` | El kilometraje no puede ser negativo. |
| Kilometraje | `mucho` | El campo kilometraje debe ser un número entero. |
| Kilometraje | `1000000` | El kilometraje no puede ser mayor que 999 999 km. |
| Teléfono | `70001234`, `7000-ABCD` | El teléfono debe tener el formato 0000-0000. |
| Teléfono, marca, propietario | vacíos | El campo … es obligatorio. |
| Modelo, color, propietario | 51, 31 y 101 caracteres | … no puede tener más de 50 / 30 / 100 caracteres. |
| Imagen | un PDF | El archivo debe ser una imagen. |
| Imagen | un PNG de 3 MB | La imagen no puede pesar más de 2 MB. |
| Imagen | un GIF | La imagen debe ser JPG, PNG o WEBP. |

Además:

- Los límites exactos (año 1980 y año actual + 1, kilometraje 0 y 999 999) sí se aceptan.
- Con varios errores a la vez, `old()` conserva todo lo escrito (`assertSessionHasInput`).
- Un campo enviado como arreglo (`marca[]=x`) se rechaza con un mensaje, no con un error 500.

Se comprueba con `php artisan test --filter=VehiculoValidacionHttpTest --testdox`, que imprime cada caso con su nombre.

#### Ciclo de la foto (criterio 5)

`ImagenesCicloTest` recorre por HTTP todo el ciclo de la foto de un vehículo. Cada prueba empieza con el punto de la rúbrica que cubre:

| Punto | Qué se comprueba |
| --- | --- |
| Tipo | Se aceptan JPG, PNG y WEBP. Un PDF se rechaza con «El archivo debe ser una imagen.», también si se renombra como `foto.png`: Laravel detecta el tipo por el contenido del archivo, no por su extensión. |
| Tamaño | Una foto de 3 MB se rechaza con «La imagen no puede pesar más de 2 MB.». Una de 2 MB exactos se acepta (`max:2048`, en KB). |
| Almacenamiento | La foto se guarda en el disco `public`, dentro de `vehiculos/`, con un nombre aleatorio de 40 caracteres. No se usa el nombre que trae del cliente. En la base de datos solo queda la ruta. |
| Reemplazo | Subir otra foto en la edición borra la anterior. Editar sin elegir foto conserva la actual. |
| Eliminación | Borrar el vehículo borra su foto, a través del observer. |
| Por defecto | Sin foto, la ficha y el listado muestran `img/vehiculo-default.svg`. |
| URL pública | La ficha, el listado y la edición usan `asset('storage/...')`. Esa URL funciona gracias a `php artisan storage:link`, que crea `public/storage` apuntando a `storage/app/public`, la raíz del disco `public` (ver la sección de instalación). Sin el enlace, la foto daría 404 en el navegador. |
| Ciclo completo | Registrar con foto, reemplazarla dos veces, editar sin foto y eliminar el vehículo no deja ningún archivo huérfano. |

Las fotos JPG y WEBP de la prueba son imágenes reales de 1×1 px escritas en bytes, y se suben como lo haría el navegador. Así la prueba no depende de que la instalación de PHP sepa generar esos formatos con GD, y el tipo se detecta por el contenido. Se comprueba con `php artisan test --filter=ImagenesCicloTest --testdox`.





### Órdenes (Leo)

Módulo de la entidad relacionada: `OrdenServicioController` con sus Form Requests, la política de la regla 5 y las vistas de `resources/views/ordenes`.

#### Listado de órdenes (`/ordenes`)

El listado es público y muestra las órdenes de 15 en 15, de la fecha de ingreso más reciente a la más antigua. Cada fila trae el número, la placa con la marca y el modelo del vehículo, el tipo de servicio, las fechas de ingreso y de entrega (o **Sin entregar**), el estado con su etiqueta de color y el costo total, más el botón **Ver orden**.

- **Filtros:** `estado` y `tipo` viajan por GET y se combinan. El estado solo se usa si está en `OrdenServicio::ESTADOS`; cualquier otro valor se ignora en lugar de llegar a la consulta. El tipo solo se acepta como entero positivo.
- **Paginación:** `paginate(15)->withQueryString()` conserva los filtros en los enlaces de las demás páginas.
- **Sin N+1:** `OrdenServicio::with(['vehiculo', 'tipoServicio'])` carga las dos relaciones en dos consultas. Como `preventLazyLoading` está activo fuera de producción, olvidar una relación haría fallar la página con `LazyLoadingViolationException`.
- **Estado vacío:** sin órdenes aparece «No hay órdenes registradas.»; con filtros sin resultados, «No hay órdenes que coincidan con los filtros.» y el enlace **Quitar filtros**.
- **Búsqueda por placa:** `placa` filtra con `whereHas('vehiculo', fn ($q) => $q->where('placa', 'like', '%'.$placa.'%'))`. El texto viaja como parámetro enlazado, así que comillas o comodines escritos a mano no alteran la consulta. Buscar «P0000» devuelve solo las órdenes de esas placas.
- **Rango de fechas:** `desde` y `hasta` filtran por fecha de ingreso con el scope `delPeriodo()` de Carlos (C3). Se validan con `$request->validate()` y mensajes en español: una fecha inválida da «La fecha inicial no es una fecha válida.» y un rango invertido, «La fecha final no puede ser anterior a la fecha inicial.».
- **Combinación:** placa, fechas, estado y tipo se combinan entre sí y se conservan al paginar.

Se comprueba con `php artisan test --filter=OrdenListadoTest`.

#### Ficha de la orden (`/ordenes/{orden}`)

`show(OrdenServicio $orden)` recibe la orden por route model binding (el parámetro se llama `orden`, no `ordene`): si no existe, Laravel responde 404 antes de entrar al método. Después carga el vehículo y el servicio con `$orden->load(['vehiculo', 'tipoServicio'])`.

La ficha muestra el estado, el vehículo (con enlace a su ficha), el servicio (con enlace a la ficha del servicio), las fechas de ingreso y entrega, el kilometraje de ingreso y la descripción. Debajo va el **desglose del costo**: precio base del servicio, costo adicional y costo total. Se comprueba con `php artisan test --filter=OrdenListadoTest`.

#### Registro de órdenes (`/ordenes/create`)

Registrar requiere sesión. El formulario tiene listas desplegables de vehículos (por placa) y de tipos de servicio (con su precio base). Si se llega desde la ficha del vehículo con `/ordenes/create?vehiculo=ID`, ese vehículo queda preseleccionado.

`StoreOrdenServicioRequest` valida en el servidor, así que los errores aparecen aunque el navegador no valide (`novalidate`). Los mensajes están en español y el formulario conserva lo escrito con `old()`.

| Campo | Reglas |
| --- | --- |
| `vehiculo_id` | `required`, `integer`, `exists:vehiculos,id` |
| `tipo_servicio_id` | `required`, `integer`, `exists:tipos_servicio,id` |
| `fecha_ingreso` | `required`, `date`, `before_or_equal:today` |
| `fecha_entrega` | `nullable`, `date`, `after_or_equal:fecha_ingreso` (**regla 4**) |
| `kilometraje_ingreso` | `required`, `integer`, `min:0`, `max:999999` |
| `descripcion` | `required`, `string`, `max:1000` |
| `costo_adicional` | `nullable`, `numeric`, `min:0`, `max:99999.99` |

**Regla 4:** si la entrega es anterior al ingreso aparece «La fecha de entrega no puede ser anterior a la fecha de ingreso.» y la orden no se guarda.

`costo_total` y `estado` no están en las reglas, así que `validated()` los descarta aunque alguien los envíe a mano. Toda orden nueva empieza en `pendiente`. Al guardar, el controlador redirige a la ficha con «Orden registrada correctamente.». Se comprueba con `php artisan test --filter=OrdenRegistroTest`.

#### Costo total calculado por el sistema (criterio 4)

`OrdenServicio::calcularCostoTotal()` aplica la fórmula acordada: **costo_total = precio_base del tipo de servicio + costo_adicional**, redondeado a dos decimales. Si no hay costo adicional, cuenta como 0. El controlador la llama antes de guardar al registrar y, desde L6, también al editar.

El total nunca llega del formulario: aunque alguien envíe `costo_total=1` a mano, `validated()` lo descarta y se guarda precio base + adicional. Todo corre en el backend de Laravel de la propia aplicación, sin servicios externos. Se comprueba con `php artisan test --filter=OrdenCostoTest`.

#### Regla 3 y transacción al registrar

**Regla 3:** el kilometraje de ingreso no puede ser menor que el último registrado del vehículo. `StoreOrdenServicioRequest` agrega a `kilometraje_ingreso` la regla de Fernando, `new KilometrajeNoMenorAlUltimo($vehiculoId)`, que compara contra `Vehiculo::ultimoKilometraje()`: el mayor entre el kilometraje del vehículo y el de sus órdenes. Por ejemplo, P000002 tiene una orden con 85 000 km, así que una orden nueva con 80 000 muestra «El kilometraje no puede ser menor que el último registrado (85000 km).». Si `vehiculo_id` no es un número, la regla recibe `null` y el error lo da `integer`, sin llegar a un 500.

**Transacción:** `store` envuelve en `DB::transaction()` la creación de la orden, el cálculo del costo y la actualización del vehículo. Si el kilometraje de ingreso supera el del vehículo, el vehículo queda con el nuevo valor. Si cualquier paso lanza una excepción, Laravel revierte todo: no queda una orden guardada con el vehículo sin actualizar, ni al revés. Se comprueba con `php artisan test --filter=OrdenTransaccionTest`, que simula un fallo al actualizar el vehículo.

#### Edición y regla 5 (`/ordenes/{orden}/edit`)

**Regla 5:** una orden finalizada no puede editarse. Se aplica en el servidor con `OrdenServicioPolicy::update()`, que devuelve `Response::deny('Esta orden está finalizada y no puede editarse.')` cuando `puedeEditarse()` es falso. La regla se comprueba en tres lugares:

- `edit` llama a `Gate::authorize('update', $orden)`: abrir la URL a mano responde 403 y la página 403 de Ángel muestra el mensaje.
- `UpdateOrdenServicioRequest::authorize()` aplica la misma política. Laravel corre `authorize()` antes de validar, así que un `PUT` enviado a mano sobre una orden finalizada responde 403 y la base no cambia.
- En la ficha, el botón **Editar** está dentro de `@can('update', $orden)`. Esto solo evita ofrecerlo: la seguridad la da la política.

`UpdateOrdenServicioRequest` hereda las reglas y los mensajes del registro (`StoreOrdenServicioRequest::reglas()`), así que las **reglas 3 y 4** también se aplican al editar. La regla 3 recibe el id de la orden, `new KilometrajeNoMenorAlUltimo($vehiculoId, $this->route('orden')->id)`, para no compararla consigo misma. `update` recalcula el costo total con `calcularCostoTotal()`, sube el kilometraje del vehículo en la misma transacción si corresponde y muestra «Orden actualizada correctamente.». Se comprueba con `php artisan test --filter=OrdenEdicionTest`.

#### Cambio de estado (`PATCH /ordenes/{orden}/estado`)

El estado avanza en un solo sentido: **pendiente → en proceso → finalizada**. La ficha muestra un único botón, «Pasar a En proceso» o «Pasar a Finalizada», dentro de un formulario con `@csrf` y `@method('PATCH')`. Una orden finalizada ya no muestra ninguno.

`CambiarEstadoOrdenRequest` valida contra `OrdenServicio::TRANSICIONES` (con `siguienteEstado()`), no contra `ESTADOS`. Por eso:

- Saltar de pendiente a finalizada se rechaza con «La orden solo puede pasar a En proceso.».
- Volver de finalizada a pendiente se rechaza con «Esta orden está finalizada y ya no cambia de estado.».
- Cualquier estado inventado también se rechaza.

Al pasar a **finalizada**, si la orden no tenía fecha de entrega se registra la de hoy. Si ya tenía una, se conserva, siempre que no sea anterior al ingreso (**regla 4**). Cada cambio muestra un mensaje flash, por ejemplo «La orden pasó a Finalizada.». Se comprueba con `php artisan test --filter=OrdenEstadoTest`.

#### Eliminación (`DELETE /ordenes/{orden}`)

Solo se eliminan órdenes **pendientes**: una orden en proceso o finalizada ya es historial trabajado del vehículo. `OrdenServicioPolicy::delete()` lo decide y `destroy` llama a `Gate::authorize('delete', $orden)`. Por eso un `DELETE` sobre otra orden responde 403 con «Solo se pueden eliminar órdenes pendientes.».

El botón **Eliminar** aparece en la ficha solo si la orden se puede borrar (`@can('delete', $orden)`). Sigue el contrato de marcado: formulario `POST` con `@csrf`, `@method('DELETE')`, `data-confirmar` y `onsubmit="return confirm(...)"`. Un `GET` a la misma URL solo muestra la ficha y nunca borra. Al eliminar, se vuelve al listado con «Orden #N eliminada correctamente.». Se comprueba con `php artisan test --filter=OrdenEliminacionTest`.

#### Resumen de reglas y cálculos del módulo

| Regla o cálculo | Dónde se aplica | Prueba |
| --- | --- | --- |
| Regla 3: el kilometraje de ingreso no baja del último registrado | `KilometrajeNoMenorAlUltimo` en `StoreOrdenServicioRequest` y `UpdateOrdenServicioRequest` | `OrdenTransaccionTest`, `OrdenEdicionTest` |
| Regla 4: la entrega no es anterior al ingreso | `after_or_equal:fecha_ingreso` en los requests y comprobación al finalizar en `CambiarEstadoOrdenRequest` | `OrdenRegistroTest`, `OrdenEstadoTest` |
| Regla 5: una orden finalizada no se edita | `OrdenServicioPolicy::update()` con `Gate::authorize()` y `@can` en la vista | `OrdenEdicionTest` |
| Costo total = precio base + costo adicional | `OrdenServicio::calcularCostoTotal()`, al registrar y al editar | `OrdenCostoTest` |
| Transiciones sin saltos ni retrocesos | `CambiarEstadoOrdenRequest` contra `siguienteEstado()` | `OrdenEstadoTest` |
| Orden y kilometraje del vehículo se guardan juntos | `DB::transaction()` en `store` y `update` | `OrdenTransaccionTest` |

#### Prueba de integración del ciclo completo

`CicloOrdenTest` automatiza el recorrido de la defensa. Abre el formulario desde el vehículo (preseleccionado) y registra una orden por HTTP. Comprueba que el total lo calculó el backend y que el kilometraje del vehículo subió. Después prueba que otra orden con menos kilometraje se rechaza (regla 3), que saltar de pendiente a finalizada falla y avanza los estados uno a uno. Con la orden finalizada, comprueba que editarla, enviarla a mano o eliminarla responde 403 (regla 5). Por último, verifica que el ingreso aparece en `/reportes` (C8). Si esta prueba pasa, el recorrido en vivo funciona.

#### Comprobación rápida de mi bloque

```bash
php artisan test --filter="Orden|CicloOrdenTest"
php artisan migrate:fresh --seed
php artisan serve
```

- `/ordenes`: listado con filtros por placa, fechas, estado y servicio.
- Con sesión iniciada, abrir la ficha de P000002 en `/vehiculos` y pulsar **Registrar orden** (el vehículo queda preseleccionado): una orden con 80 000 km muestra el error de la regla 3, porque su último kilometraje es 85 000.
- En la ficha de una orden: **Pasar a En proceso** y luego **Pasar a Finalizada**. Después, abrir `/ordenes/{id}/edit` a mano responde 403.





### Interfaz y cierre (Milton)

El layout carga los estilos y el JavaScript compartidos. Los formularios señalan el primer campo con error y muestran un resumen con enlaces a los campos. Los avisos de sesión distinguen éxito y error y pueden cerrarse.

El diálogo de eliminación se abre en los formularios con `data-confirmar`. Al cancelar, el foco vuelve al botón de origen. Si el archivo `biela.js` no carga, se conserva la confirmación nativa que traen los formularios. Al elegir una foto en un formulario de vehículo, se muestra una vista previa con el límite de 2 MB para JPG, PNG y WEBP. Laravel conserva la validación definitiva.

Por debajo de 640 px, el menú se pliega y las tablas muestran las filas como tarjetas con sus etiquetas. En los listados que ordenan resultados, el control «Ordenar resultados» mantiene disponibles las opciones del encabezado. El panel reúne el total de vehículos y órdenes, los ingresos por órdenes finalizadas ingresadas en el mes, la distribución por estado y la orden abierta más antigua. El reporte incorpora barras de ingresos y el botón «Imprimir» para usar la impresora o guardar un PDF desde el navegador.

#### Comprobación del bloque

```bash
php artisan test --filter="InterfazBaseTest|AlertasTest|NavegacionTest|PanelTest|ReporteVistaTest|RecorridoDefensaTest|EtiquetasConceptoTest"
php artisan test
```

- Abrir las páginas de la defensa a 360, 768 y 1280 px y comprobar que la página no desborde.
- Iniciar sesión con el usuario de desarrollo. En vehículos, órdenes o servicios, elegir «Eliminar» y cancelar antes de repetir. Si se bloquea solo `biela.js` en el navegador, debe aparecer la confirmación nativa.
- En edición de vehículo, seleccionar una foto compatible y comprobar que aparece antes de guardar. Seleccionar luego una de más de 2 MB para ver el aviso.
- Abrir Reportes e Historial, filtrar y probar «Imprimir». La vista impresa oculta el menú, los filtros y las acciones.
- El recorrido automatizado usa los datos de prueba, comprueba las pantallas principales de los módulos y verifica el 403 de una orden finalizada.

#### Capturas

Capturas de Laravel en ejecución con datos de prueba. Las pantallas 429 y 500 se reprodujeron de forma controlada en el entorno de pruebas. La imagen de la vista previa es una ilustración de prueba seleccionada desde el formulario.

<details>
<summary>Panel con indicadores, ingresos del mes y estados</summary>

![Panel con indicadores, ingresos del mes y estados](docs/capturas/01-panel.png)

</details>

<details>
<summary>Listado de vehículos con búsqueda por placa</summary>

![Listado de vehículos con búsqueda por placa](docs/capturas/02-vehiculos-busqueda.png)

</details>

<details>
<summary>Ficha del vehículo y su historial</summary>

![Ficha del vehículo y su historial](docs/capturas/03-ficha-vehiculo.png)

</details>

<details>
<summary>Registro de vehículo con resumen de errores</summary>

![Registro de vehículo con resumen de errores](docs/capturas/04-registro-errores.png)

</details>

<details>
<summary>Edición de vehículo con vista previa de la foto</summary>

![Edición de vehículo con vista previa de la foto](docs/capturas/05-edicion-vista-previa.png)

</details>

<details>
<summary>Diálogo de confirmación de eliminación</summary>

![Diálogo de confirmación de eliminación](docs/capturas/06-confirmar-eliminacion.png)

</details>

<details>
<summary>Aviso al intentar borrar un vehículo con órdenes</summary>

![Aviso al intentar borrar un vehículo con órdenes](docs/capturas/07-vehiculo-con-ordenes.png)

</details>

<details>
<summary>Listado de órdenes y filtros</summary>

![Listado de órdenes y filtros](docs/capturas/08-ordenes.png)

</details>

<details>
<summary>Error de kilometraje inferior al último registrado</summary>

![Error de kilometraje inferior al último registrado](docs/capturas/09-orden-kilometraje.png)

</details>

<details>
<summary>Pantalla 403 al intentar editar una orden finalizada</summary>

![Pantalla 403 al intentar editar una orden finalizada](docs/capturas/10-orden-finalizada-403.png)

</details>

<details>
<summary>Reporte con ingresos por servicio y botón de impresión</summary>

![Reporte con ingresos por servicio y botón de impresión](docs/capturas/11-reporte.png)

</details>

<details>
<summary>Historial de servicios por vehículo</summary>

![Historial de servicios por vehículo](docs/capturas/12-historial.png)

</details>

<details>
<summary>Catálogo de servicios</summary>

![Catálogo de servicios](docs/capturas/13-servicios.png)

</details>

<details>
<summary>Inicio de sesión</summary>

![Inicio de sesión](docs/capturas/14-login.png)

</details>

<details>
<summary>Pantalla 419 de sesión o formulario expirado</summary>

![Pantalla 419 de sesión o formulario expirado](docs/capturas/15-sesion-expirada-419.png)

</details>

<details>
<summary>Panel a 360 px con el menú abierto</summary>

![Panel a 360 px con el menú abierto](docs/capturas/16-panel-movil.png)

</details>

<details>
<summary>Listado a 360 px con filas en formato de tarjetas</summary>

![Listado a 360 px con filas en formato de tarjetas](docs/capturas/17-vehiculos-movil.png)

</details>

<details>
<summary>Validación de una placa con formato incorrecto</summary>

![Validación de una placa con formato incorrecto](docs/capturas/18-placa-invalida.png)

</details>

<details>
<summary>Validación de un año fuera del rango permitido</summary>

![Validación de un año fuera del rango permitido](docs/capturas/19-anio-invalido.png)

</details>

<details>
<summary>Validación de la fecha de entrega anterior al ingreso</summary>

![Validación de la fecha de entrega anterior al ingreso](docs/capturas/20-fechas-orden.png)

</details>

<details>
<summary>Pantalla 404 al consultar un vehículo inexistente</summary>

![Pantalla 404 al consultar un vehículo inexistente](docs/capturas/21-no-encontrado-404.png)

</details>

<details>
<summary>Pantalla 429 de demasiados intentos</summary>

![Pantalla 429 de demasiados intentos](docs/capturas/22-demasiados-intentos-429.png)

</details>

<details>
<summary>Pantalla 500 de error inesperado</summary>

![Pantalla 500 de error inesperado](docs/capturas/23-error-500.png)

</details>

#### Reglas de negocio y verificación

| Regla | Dónde se aplica | Prueba |
| --- | --- | --- |
| Placa única con formato `P` y seis dígitos | `StoreVehiculoRequest`, `UpdateVehiculoRequest` e índice único | `VehiculoValidacionHttpTest`, `VehiculoEdicionTest` |
| Año válido entre 1980 y el año actual más uno | `StoreVehiculoRequest` y `UpdateVehiculoRequest` | `VehiculoValidacionHttpTest` |
| El kilometraje no puede bajar del último registrado | `KilometrajeNoMenorAlUltimo` y validación de vehículos y órdenes | `ReglaKilometrajeTest`, `CicloOrdenTest` |
| La entrega no puede ser anterior al ingreso | `StoreOrdenServicioRequest`, `UpdateOrdenServicioRequest` y validación de la transición final | `OrdenRegistroTest`, `OrdenEdicionTest`, `OrdenEstadoTest` |
| Una orden finalizada no se puede editar | `OrdenServicioPolicy` y autorización en el controlador | `OrdenEdicionTest`, `RecorridoDefensaTest` |
| El costo total suma el precio base y el costo adicional | `OrdenServicio::calcularCostoTotal()` | `OrdenCostoTest` |
| Un vehículo o servicio con órdenes no se puede borrar | `VehiculoController`, `TipoServicioController` y llaves foráneas | `VehiculoEliminacionTest`, `TipoServicioEliminarTest` |

#### Distribución del trabajo

Conteo del historial a la revisión base `cf79745`, correspondiente al ZIP del equipo. La columna de Fase 1 agrupa los nombres de autor que identifican a una misma persona. Los cambios M1–M10 de esta entrega se cuentan en Fase 2 cuando se registren en Git.

| Integrante | Módulo principal | Commits hasta `v1.0` | Commits de Fase 2 hasta la base |
| --- | --- | ---: | ---: |
| Carlos · `jojosenthusiast` | Base, contratos, datos de defensa y reporte | 11 | 10 |
| Ángel · `angelcoto-stack` | Autenticación, seguridad y páginas de error | 9 | 12 |
| Fernando · `fernandovalle45` | Imágenes y catálogo | 10 | 11 |
| Jorge · `JorgeRamos2001` | Vehículos y validaciones | 10 | 10 |
| Leonel · `leonelgit` | Órdenes y ciclo de servicio | 10 | 11 |
| Milton · `Milton84-toc` | Interfaz, panel, presentación del reporte y cierre | 1 | 0 |

Para actualizar la tabla después de integrar la entrega, ejecutar `git shortlog -sn v1.0..HEAD`. El historial base ya contiene commits con fecha de autor de cuatro días distintos (13, 14, 15 y 17 de noviembre de 2026).

#### Cambios respecto a la Fase 1

- Se agregaron dos migraciones independientes para el costo adicional de las órdenes y los índices de vehículos. Así, una instalación de Fase 1 puede avanzar con `php artisan migrate` y conservar sus datos.
- Vehículos pasó de listado y ficha a registro, edición y eliminación, con carga de fotos. Se ampliaron las órdenes y el catálogo y se agregó el reporte con CSV e historial.
- Se incorporó el inicio de sesión para las operaciones de escritura. La lectura pública se conserva.
- El panel y los estilos compartidos quedaron a cargo de Milton. El reporte de Carlos mantiene sus consultas y rutas, con presentación e impresión añadidas en sus vistas.
