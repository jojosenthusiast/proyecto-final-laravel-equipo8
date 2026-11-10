<?php

namespace App\Models;

use Database\Factories\VehiculoFactory;
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
}
