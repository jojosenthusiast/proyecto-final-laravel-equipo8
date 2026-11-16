<?php

namespace App\Models;

use Database\Factories\TipoServicioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoServicio extends Model
{
    /** @use HasFactory<TipoServicioFactory> */
    use HasFactory;

    // Laravel pluraliza en inglés y buscaría "tipo_servicios", que no existe.
    protected $table = 'tipos_servicio';

    // [ENCAPSULAMIENTO] Solo estas columnas se pueden asignar en masa.
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_base',
    ];

    // [ENCAPSULAMIENTO] El dinero siempre se lee con dos decimales.
    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
        ];
    }

    // [ENCAPSULAMIENTO] Búsqueda por nombre con un parámetro enlazado, nunca concatenado en SQL.
    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        return $query->when(
            $texto !== null && trim($texto) !== '',
            fn (Builder $consulta) => $consulta->where('nombre', 'like', '%'.trim($texto).'%')
        );
    }

    // [RELACION] Un tipo de servicio tiene muchas órdenes de servicio.
    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }
}
