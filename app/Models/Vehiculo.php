<?php

namespace App\Models;

use Database\Factories\VehiculoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    /** @use HasFactory<VehiculoFactory> */
    use HasFactory;

    // Laravel buscaría "vehiculos" igual, pero se declara para que el contrato sea explícito.
    protected $table = 'vehiculos';

    // [ENCAPSULAMIENTO] Solo estas columnas se pueden asignar en masa.
    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'anio',
        'color',
        'kilometraje',
        'propietario_nombre',
        'propietario_telefono',
        'imagen',
    ];

    // [ENCAPSULAMIENTO] Año y kilometraje salen siempre como enteros.
    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'kilometraje' => 'integer',
        ];
    }

    // [RELACION] Un vehículo tiene muchas órdenes de servicio.
    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    // [REGLA-NEGOCIO] Regla 3: el kilometraje de una orden nueva no puede ser menor que este valor.
    // Es el mayor entre el kilometraje del vehículo y el de sus órdenes. Al editar una orden se pasa
    // su id en $ignorarOrdenId para no compararla consigo misma.
    public function ultimoKilometraje(?int $ignorarOrdenId = null): int
    {
        $maximoOrdenes = $this->ordenes()
            ->when($ignorarOrdenId, fn ($consulta, $id) => $consulta->whereKeyNot($id))
            ->max('kilometraje_ingreso');

        return max((int) $this->kilometraje, (int) $maximoOrdenes);
    }

    // [ENCAPSULAMIENTO] Suma del costo de las órdenes finalizadas, calculada por la base de datos.
    public function totalGastado(): float
    {
        return (float) $this->ordenes()->estado('finalizada')->sum('costo_total');
    }

    // [ENCAPSULAMIENTO] Las vistas piden $vehiculo->imagen_url y nunca arman la ruta a mano:
    // si no hay foto subida a storage, se usa la imagen por defecto de Biela.
    protected function imagenUrl(): Attribute
    {
        return Attribute::get(fn () => $this->imagen
            ? asset('storage/'.$this->imagen)
            : asset('img/vehiculo-default.svg'));
    }
}
