<?php

namespace App\Models;

use Database\Factories\OrdenServicioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenServicio extends Model
{
    /** @use HasFactory<OrdenServicioFactory> */
    use HasFactory;

    // Estados válidos de una orden; coinciden con el enum de la migración.
    public const ESTADOS = ['pendiente', 'en_proceso', 'finalizada'];

    // [REGLA-NEGOCIO] Única transición permitida desde cada estado. Una orden finalizada no avanza más.
    public const TRANSICIONES = [
        'pendiente' => 'en_proceso',
        'en_proceso' => 'finalizada',
    ];

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

    // [ENCAPSULAMIENTO] Filtra por un estado. El valor viaja como parámetro, nunca concatenado.
    public function scopeEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    // [ENCAPSULAMIENTO] Órdenes que siguen en el taller: pendientes o en proceso.
    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereIn('estado', ['pendiente', 'en_proceso']);
    }

    // [ENCAPSULAMIENTO] Órdenes cuya fecha de ingreso cae en el período. Cada límite es opcional.
    public function scopeDelPeriodo(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($desde, fn (Builder $q, string $fecha) => $q->whereDate('fecha_ingreso', '>=', $fecha))
            ->when($hasta, fn (Builder $q, string $fecha) => $q->whereDate('fecha_ingreso', '<=', $fecha));
    }

    // [REGLA-NEGOCIO] Regla 5: una orden finalizada queda cerrada.
    public function estaFinalizada(): bool
    {
        return $this->estado === 'finalizada';
    }

    // [REGLA-NEGOCIO] Regla 5: solo se edita una orden que no está finalizada.
    public function puedeEditarse(): bool
    {
        return ! $this->estaFinalizada();
    }

    // [REGLA-NEGOCIO] Estado al que puede pasar la orden; null si ya no admite cambios.
    public function siguienteEstado(): ?string
    {
        return self::TRANSICIONES[$this->estado] ?? null;
    }
}
