<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenServicio extends Model
{
    // Estados válidos de una orden; coinciden con el enum de la migración.
    public const ESTADOS = ['pendiente', 'en_proceso', 'finalizada'];

    // Laravel buscaría "orden_servicios", que no existe.
    protected $table = 'ordenes_servicio';

    // [ENCAPSULAMIENTO] Solo estas columnas se pueden asignar en masa.
    protected $fillable = [
        'vehiculo_id',
        'tipo_servicio_id',
        'fecha_ingreso',
        'fecha_entrega',
        'kilometraje_ingreso',
        'descripcion',
        'costo_total',
        'estado',
    ];

    // [ENCAPSULAMIENTO] Fechas como Carbon, dinero con dos decimales y kilometraje entero.
    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_entrega' => 'date',
            'kilometraje_ingreso' => 'integer',
            'costo_total' => 'decimal:2',
        ];
    }
}
