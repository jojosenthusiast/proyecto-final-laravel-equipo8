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

### Reglas de negocio (pendientes para la Fase 2)

Se validarán en el servidor durante la Fase 2:

1. Placa única con formato definido por el equipo (`P` y seis dígitos, por ejemplo `P123456`).
2. Año del vehículo entre 1980 y el año actual + 1.
3. El kilometraje de ingreso no puede ser menor que el último registrado para el vehículo.
4. La fecha de entrega no puede ser anterior a la de ingreso.
5. Una orden finalizada no puede editarse.

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
