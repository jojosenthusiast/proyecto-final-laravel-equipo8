<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'costo_adicional',
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
            // [ENCAPSULAMIENTO] Fase 2: el recargo sobre el precio base también es dinero.
            'costo_adicional' => 'decimal:2',
        ];
    }

    // [RELACION] Cada orden pertenece a un vehículo.
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    // [RELACION] Cada orden pertenece a un tipo de servicio.
    public function tipoServicio(): BelongsTo
    {
        return $this->belongsTo(TipoServicio::class);
    }
}
