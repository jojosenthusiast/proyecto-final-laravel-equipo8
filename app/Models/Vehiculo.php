<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
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
}
